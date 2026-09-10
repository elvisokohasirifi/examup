<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use App\Models\ExamAccessLink;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ImportMicrosoftFormsResultsAction
{
    private const IMPORTED_METADATA_KEY = 'imported_from_microsoft_forms';

    private const ATTEMPT_BATCH_SIZE = 100;

    private const INSERT_BATCH_SIZE = 80;

    /**
     * @return array{attempt_count: int, question_count: int}
     */
    public function preview(UploadedFile $file): array
    {
        $import = $this->inspectImport($file);

        return [
            'attempt_count' => $import['attempt_count'],
            'question_count' => count($import['questions']),
        ];
    }

    public function handle(UploadedFile $file, string $title, User $creator): Exam
    {
        $import = $this->inspectImport($file);

        return DB::transaction(function () use ($file, $import, $title, $creator): Exam {
            $importedAt = now();
            $exam = Exam::create([
                'created_by' => $creator->id,
                'title' => $title,
                'description' => 'Historical results imported from Microsoft Forms.',
                'instructions' => 'This exam is an imported historical record and is not available to candidates.',
                'display_mode' => 'all',
                'allow_back_navigation' => true,
                'shuffle_questions' => false,
                'questions_per_attempt' => null,
                'time_limit_minutes' => null,
                'autosave_interval_seconds' => 15,
                'show_score_to_student' => false,
                'show_correct_answers_to_student' => false,
                'show_index_number_field' => true,
                'disable_copy_paste' => false,
                'require_fullscreen' => false,
                'is_published' => false,
                'settings' => [
                    self::IMPORTED_METADATA_KEY => true,
                    'imported_at' => $importedAt->toIso8601String(),
                    'source' => 'Microsoft Forms CSV',
                ],
            ]);

            $questionIds = [];
            $questionRows = [];

            foreach ($import['questions'] as $index => $question) {
                $questionId = (string) Str::uuid();
                $questionIds[] = $questionId;
                $questionRows[] = [
                    'id' => $questionId,
                    'exam_id' => $exam->id,
                    'type' => Question::TYPE_FILL_IN,
                    'position' => $index + 1,
                    'prompt' => $question['prompt'],
                    'points' => $question['points'],
                    'allows_multiple_selection' => false,
                    'accepted_answers' => json_encode($question['accepted_answers'], JSON_THROW_ON_ERROR),
                    'settings' => json_encode([self::IMPORTED_METADATA_KEY => true], JSON_THROW_ON_ERROR),
                    'created_at' => $importedAt,
                    'updated_at' => $importedAt,
                ];
            }

            DB::table((new Question)->getTable())->insert($questionRows);

            $this->insertAttempts($file, $import, $questionIds, $exam, $creator, $importedAt);

            return $exam;
        });
    }

    /**
     * @return array{headers: array<int, string>, questions: array<int, array{prompt: string, points: float, accepted_answers: array<int, string>, points_column: string}>, attempt_count: int, maximum_score: float}
     */
    private function inspectImport(UploadedFile $file): array
    {
        $headers = $this->headers($file);
        $questionHeaders = array_values(array_filter($headers, fn (string $header): bool => $this->isQuestionHeader($header)));

        if ($questionHeaders === []) {
            throw new InvalidArgumentException('No question columns were found. Export the Responses workbook as a Microsoft Forms CSV.');
        }

        $questions = array_map(function (string $header): array {
            return [
                'prompt' => $header,
                'points' => 0.0,
                'accepted_answers' => [],
                'points_column' => 'Points - '.$header,
            ];
        }, $questionHeaders);

        $attemptCount = 0;

        foreach ($this->responseRows($file, $headers) as $row) {
            $attemptCount++;

            foreach ($questions as $questionIndex => $question) {
                $score = $this->numericValue($row[$question['points_column']] ?? null);
                $answer = trim((string) ($row[$question['prompt']] ?? ''));

                if ($score > $question['points']) {
                    $questions[$questionIndex]['points'] = $score;
                    $questions[$questionIndex]['accepted_answers'] = $answer === '' ? [] : [$answer];
                } elseif ($score > 0 && $score === $question['points'] && $answer !== '' && count($question['accepted_answers']) < 25) {
                    $questions[$questionIndex]['accepted_answers'][] = $answer;
                }
            }
        }

        if ($attemptCount === 0) {
            throw new InvalidArgumentException('The Microsoft Forms CSV does not contain any responses to import.');
        }

        foreach ($questions as $questionIndex => $question) {
            $questions[$questionIndex]['points'] = $question['points'] > 0 ? $question['points'] : 1.0;
            $questions[$questionIndex]['accepted_answers'] = array_values(array_unique($question['accepted_answers']));
        }

        return [
            'headers' => $headers,
            'questions' => $questions,
            'attempt_count' => $attemptCount,
            'maximum_score' => array_sum(array_column($questions, 'points')),
        ];
    }

    /**
     * @param  array{headers: array<int, string>, questions: array<int, array{prompt: string, points: float, accepted_answers: array<int, string>, points_column: string}>, attempt_count: int, maximum_score: float}  $import
     * @param  array<int, string>  $questionIds
     */
    private function insertAttempts(UploadedFile $file, array $import, array $questionIds, Exam $exam, User $creator, CarbonInterface $importedAt): void
    {
        $accessLinks = [];
        $attempts = [];
        $answers = [];

        foreach ($this->responseRows($file, $import['headers']) as $rowIndex => $row) {
            $startedAt = $this->parseDate($row['Start time'] ?? null);
            $submittedAt = $this->parseDate($row['Completion time'] ?? null);
            $answerScores = array_map(
                fn (array $question): float => $this->numericValue($row[$question['points_column']] ?? null),
                $import['questions'],
            );
            $score = $this->numericValue($row['Total points'] ?? null);

            if ($score === 0.0 && array_sum($answerScores) > 0) {
                $score = array_sum($answerScores);
            }

            $accessLinkId = (string) Str::uuid();
            $attemptId = (string) Str::uuid();
            $sourceRow = $rowIndex + 2;
            $studentEmail = $this->studentEmail($row);
            $submittedOrStartedAt = $submittedAt ?? $startedAt;

            $accessLinks[] = [
                'id' => $accessLinkId,
                'exam_id' => $exam->id,
                'created_by' => $creator->id,
                'public_key' => (string) Str::uuid(),
                'access_token' => Str::random(48),
                'email' => $studentEmail,
                'max_attempts' => 1,
                'send_email' => false,
                'is_active' => false,
                'meta' => json_encode([
                    self::IMPORTED_METADATA_KEY => true,
                    'source_row' => $sourceRow,
                ], JSON_THROW_ON_ERROR),
                'created_at' => $importedAt,
                'updated_at' => $importedAt,
            ];

            $attempts[] = [
                'id' => $attemptId,
                'exam_id' => $exam->id,
                'exam_access_link_id' => $accessLinkId,
                'status' => ExamAttempt::STATUS_SUBMITTED,
                'student_name' => $this->studentName($row, $sourceRow - 1),
                'student_email' => $studentEmail,
                'student_index_number' => $this->valueForHeaderNames($row, [
                    'student id number', 'student id', 'student index number', 'student index no', 'index number', 'index no', 'candidate id number', 'candidate id',
                ]),
                'started_at' => $startedAt,
                'submitted_at' => $submittedAt,
                'duration_seconds' => $startedAt !== null && $submittedAt !== null
                    ? max($startedAt->diffInSeconds($submittedAt, false), 0)
                    : null,
                'score' => min($score, $import['maximum_score']),
                'max_score' => $import['maximum_score'],
                'score_percentage' => $import['maximum_score'] > 0
                    ? round((min($score, $import['maximum_score']) / $import['maximum_score']) * 100, 2)
                    : 0,
                'meta' => json_encode([
                    self::IMPORTED_METADATA_KEY => true,
                    'source_row' => $sourceRow,
                ], JSON_THROW_ON_ERROR),
                'created_at' => $importedAt,
                'updated_at' => $importedAt,
            ];

            foreach ($import['questions'] as $questionIndex => $question) {
                $responseScore = $answerScores[$questionIndex];

                $answers[] = [
                    'id' => (string) Str::uuid(),
                    'exam_attempt_id' => $attemptId,
                    'question_id' => $questionIds[$questionIndex],
                    'answer_text' => filled($row[$question['prompt']] ?? null) ? (string) $row[$question['prompt']] : null,
                    'is_correct' => $responseScore >= $question['points'],
                    'score' => $responseScore,
                    'answered_at' => $submittedOrStartedAt,
                    'graded_payload' => json_encode([
                        self::IMPORTED_METADATA_KEY => true,
                        'microsoft_forms_awarded_score' => $responseScore,
                    ], JSON_THROW_ON_ERROR),
                    'created_at' => $importedAt,
                    'updated_at' => $importedAt,
                ];
            }

            if (count($attempts) >= self::ATTEMPT_BATCH_SIZE) {
                $this->insertBatch($accessLinks, $attempts, $answers);
                $accessLinks = [];
                $attempts = [];
                $answers = [];
            }
        }

        $this->insertBatch($accessLinks, $attempts, $answers);
    }

    /**
     * @param  array<int, array<string, mixed>>  $accessLinks
     * @param  array<int, array<string, mixed>>  $attempts
     * @param  array<int, array<string, mixed>>  $answers
     */
    private function insertBatch(array $accessLinks, array $attempts, array $answers): void
    {
        foreach (array_chunk($accessLinks, self::INSERT_BATCH_SIZE) as $rows) {
            DB::table((new ExamAccessLink)->getTable())->insert($rows);
        }

        foreach (array_chunk($attempts, self::INSERT_BATCH_SIZE) as $rows) {
            DB::table((new ExamAttempt)->getTable())->insert($rows);
        }

        foreach (array_chunk($answers, self::INSERT_BATCH_SIZE) as $rows) {
            DB::table((new ExamAnswer)->getTable())->insert($rows);
        }
    }

    /**
     * @return array<int, string>
     */
    private function headers(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException('The Microsoft Forms CSV could not be read.');
        }

        try {
            $headers = fgetcsv($handle);
        } finally {
            fclose($handle);
        }

        if (! is_array($headers)) {
            throw new InvalidArgumentException('The Microsoft Forms CSV is empty.');
        }

        return array_map(fn (string $header): string => $this->normalizeHeader($header), $headers);
    }

    /**
     * @param  array<int, string>  $headers
     * @return \Generator<int, array<string, string>>
     */
    private function responseRows(UploadedFile $file, array $headers): \Generator
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException('The Microsoft Forms CSV could not be read.');
        }

        try {
            fgetcsv($handle);

            while (($values = fgetcsv($handle)) !== false) {
                if ($values === [null] || $values === []) {
                    continue;
                }

                $values = array_pad($values, count($headers), null);
                $values = array_map(fn (mixed $value): string => $this->normalizeText($value), $values);

                yield array_combine($headers, array_slice($values, 0, count($headers)));
            }
        } finally {
            fclose($handle);
        }
    }

    private function isQuestionHeader(string $header): bool
    {
        $normalizedHeader = $this->normalizedHeaderName($header);

        return ! in_array($normalizedHeader, [
            'id', 'start time', 'completion time', 'email', 'name', 'total points', 'quiz feedback', 'last modified time',
            'students full name', 'student full name', 'full name', 'student name', 'candidate name', 'student first name', 'first name',
            'student id number', 'student id', 'student index number', 'student index no', 'index number', 'index no', 'candidate id number', 'candidate id',
        ], true)
            && ! str_starts_with($normalizedHeader, 'points ')
            && ! str_starts_with($normalizedHeader, 'feedback ');
    }

    private function normalizeHeader(string $header): string
    {
        return trim(Str::replace("\xEF\xBB\xBF", '', $this->normalizeText($header)));
    }

    private function normalizeText(mixed $value): string
    {
        $value = (string) $value;

        if (! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        return $value;
    }

    private function numericValue(mixed $value): float
    {
        $value = trim((string) $value);

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function studentName(array $row, int $rowNumber): string
    {
        return $this->valueForHeaderNames($row, [
            'students full name',
            'student full name',
            'full name',
            'student name',
            'candidate name',
            'student first name',
            'first name',
            'name',
        ])
            ?? "Imported candidate {$rowNumber}";
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function studentEmail(array $row): ?string
    {
        $email = $this->valueForHeaderNames($row, ['student email', 'candidate email', 'email']);

        return $email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) ? Str::lower($email) : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $names
     */
    private function valueForHeaderNames(array $row, array $names): ?string
    {
        $normalizedRow = collect($row)->mapWithKeys(
            fn (mixed $value, string $header): array => [$this->normalizedHeaderName($header) => $value],
        );

        foreach ($names as $name) {
            $value = trim((string) $normalizedRow->get($this->normalizedHeaderName($name)));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizedHeaderName(string $header): string
    {
        return Str::of($header)
            ->lower()
            ->replaceMatches('/[^\p{L}\p{N}]+/u', ' ')
            ->squish()
            ->toString();
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('n/j/y H:i:s', $value);
        } catch (\Throwable) {
            try {
                return CarbonImmutable::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }
    }
}
