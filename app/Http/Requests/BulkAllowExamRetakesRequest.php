<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class BulkAllowExamRetakesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $exam instanceof Exam && (bool) $this->user()?->can('update', $exam);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'emails' => ['required', 'array', 'min:1', 'max:100'],
            'emails.*' => ['required', 'email', 'max:255', 'distinct:strict'],
            'email_file' => ['nullable', 'file', 'mimes:txt,csv', 'max:1024'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $emailList = $this->input('emails', '');
        $emailList = is_string($emailList) ? $emailList : '';
        $uploadedFile = $this->file('email_file');

        if ($uploadedFile !== null) {
            $emailList .= "\n".((string) file_get_contents($uploadedFile->getRealPath()));
        }

        $emails = collect(preg_split('/\r\n|\r|\n/', $emailList) ?: [])
            ->map(fn (string $line): string => trim((string) (str_getcsv($line)[0] ?? '')))
            ->reject(fn (string $email): bool => Str::lower($email) === 'email')
            ->map(fn (string $email): string => Str::lower($email))
            ->filter()
            ->values()
            ->all();

        $this->merge(['emails' => $emails]);
    }

    /** @return array<int, string> */
    public function emails(): array
    {
        return $this->validated('emails');
    }
}
