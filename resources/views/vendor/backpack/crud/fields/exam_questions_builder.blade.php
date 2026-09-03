@php
    use App\Models\Question;

    $field['value'] = collect($field['value'] ?? [])->values()->all();
    if (empty($field['value'])) {
        $field['value'] = [[
            'id' => null,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'prompt' => '',
            'help_text' => '',
            'points' => '1',
            'time_limit_seconds' => '',
            'allows_multiple_selection' => false,
            'accepted_answers' => '',
            'question_options' => [['id' => null, 'label' => '', 'is_correct' => false]],
        ]];
    }
    $fieldName = $field['name'];
    $fieldId = 'exam-questions-builder-'.str_replace(['[', ']'], '-', $fieldName);
@endphp

<style>
    #{{ $fieldId }} {
        border: 0 !important;
        background: transparent;
        padding: 0 !important;
    }

    #{{ $fieldId }} [data-question-row] {
        background: #ffffff;
        border: 0 !important;
        border-radius: 1.25rem !important;
        box-shadow: 0 14px 34px rgba(0, 0, 0, 0.12);
        color: var(--bs-body-color, inherit);
        padding: 1.5rem !important;
    }

    #{{ $fieldId }} [data-options-panel] {
        background: #f8f9fb;
        border: 0 !important;
        border-radius: 1rem !important;
        padding: 1.25rem !important;
    }

    #{{ $fieldId }} [data-option-row] {
        background: transparent;
        border: 0 !important;
        border-radius: 0.875rem !important;
        padding: 0.75rem 0 !important;
    }

    #{{ $fieldId }} [data-option-row] + [data-option-row] {
        border-top: 1px solid color-mix(in srgb, var(--bs-border-color, #dee2e6) 75%, transparent) !important;
    }

    #{{ $fieldId }} [data-bottom-add-question] {
        border-radius: 1rem;
        border-style: dashed;
        min-height: 4rem;
    }

    #{{ $fieldId }} [data-question-row] {
        color: var(--bs-body-color, inherit);
    }

    #{{ $fieldId }} .text-muted {
        color: var(--bs-secondary-color, inherit) !important;
    }

    #{{ $fieldId }} .form-label,
    #{{ $fieldId }} .form-check-label,
    #{{ $fieldId }} .fw-semibold {
        color: var(--bs-body-color, inherit) !important;
    }

    #{{ $fieldId }} [data-question-title] {
        font-size: 1.05rem;
        letter-spacing: -0.01em;
    }

    #{{ $fieldId }} [data-options-wrapper] .fw-semibold {
        font-size: 0.95rem;
    }

    [data-bs-theme="dark"] #{{ $fieldId }} [data-question-row] {
        background: #2b2531;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28);
    }

    [data-bs-theme="dark"] #{{ $fieldId }} [data-options-panel] {
        background: #241f29;
    }

    [data-bs-theme="dark"] #{{ $fieldId }} [data-option-row] + [data-option-row] {
        border-top-color: rgba(255, 255, 255, 0.08) !important;
    }
</style>

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <input type="hidden" name="{{ $fieldName }}_present" value="1" bp-field-main-input>

    <div id="exam-step-questions" class="mb-2"></div>
    <div id="{{ $fieldId }}" class="border rounded-4 p-3">
        <div class="mb-3">
            <div class="text-muted small">Add and order the questions directly inside the exam form.</div>
        </div>

        <div class="d-flex flex-column gap-4" data-question-rows>
            @foreach ($field['value'] as $questionIndex => $question)
                @php
                    $options = collect($question['question_options'] ?? [])->values()->all();
                    if (empty($options)) {
                        $options = [['id' => null, 'label' => '', 'is_correct' => false]];
                    }
                @endphp
                <div data-question-row>
                    <input type="hidden" name="{{ $fieldName }}[{{ $questionIndex }}][id]" value="{{ $question['id'] ?? '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="fw-semibold" data-question-title>Question {{ $questionIndex + 1 }}</div>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-question-row>Remove question</button>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="{{ $fieldName }}[{{ $questionIndex }}][type]" class="form-control" data-question-type>
                                <option value="{{ Question::TYPE_MULTIPLE_CHOICE }}" @selected(($question['type'] ?? '') === Question::TYPE_MULTIPLE_CHOICE)>Multiple choice</option>
                                <option value="{{ Question::TYPE_FILL_IN }}" @selected(($question['type'] ?? '') === Question::TYPE_FILL_IN)>Fill in</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Points</label>
                            <input type="number" step="0.25" min="0.25" name="{{ $fieldName }}[{{ $questionIndex }}][points]" value="{{ $question['points'] ?? '1' }}" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Time (seconds)</label>
                            <input type="number" min="1" max="3600" name="{{ $fieldName }}[{{ $questionIndex }}][time_limit_seconds]" value="{{ $question['time_limit_seconds'] ?? '' }}" class="form-control">
                            <div class="form-text">When blank, the per-question timer uses the exam time divided by questions shown per attempt.</div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4 pt-2" data-multiple-selection-wrapper>
                                <input type="hidden" name="{{ $fieldName }}[{{ $questionIndex }}][allows_multiple_selection]" value="0">
                                <input type="checkbox" name="{{ $fieldName }}[{{ $questionIndex }}][allows_multiple_selection]" value="1" class="form-check-input" @checked(!empty($question['allows_multiple_selection']))>
                                <label class="form-check-label">Allow multiple correct choices</label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Prompt</label>
                            <textarea name="{{ $fieldName }}[{{ $questionIndex }}][prompt]" rows="3" class="form-control">{{ $question['prompt'] ?? '' }}</textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Help text</label>
                            <textarea name="{{ $fieldName }}[{{ $questionIndex }}][help_text]" rows="2" class="form-control">{{ $question['help_text'] ?? '' }}</textarea>
                        </div>

                        <div class="col-md-12" data-fill-in-wrapper>
                            <label class="form-label">Accepted answers (one per line)</label>
                            <textarea name="{{ $fieldName }}[{{ $questionIndex }}][accepted_answers]" rows="4" class="form-control">{{ $question['accepted_answers'] ?? '' }}</textarea>
                        </div>

                        <div class="col-md-12" data-options-wrapper>
                            <div data-options-panel>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="fw-semibold">Answer options</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-add-option-row>Add option</button>
                                </div>
                                <div class="d-flex flex-column gap-3" data-option-rows>
                                    @foreach ($options as $optionIndex => $option)
                                        <div data-option-row>
                                            <input type="hidden" name="{{ $fieldName }}[{{ $questionIndex }}][question_options][{{ $optionIndex }}][id]" value="{{ $option['id'] ?? '' }}">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-9">
                                                    <label class="form-label">Label</label>
                                                    <input type="text" name="{{ $fieldName }}[{{ $questionIndex }}][question_options][{{ $optionIndex }}][label]" value="{{ $option['label'] ?? '' }}" class="form-control">
                                                </div>
                                                <div class="col-md-1">
                                                    <div class="form-check mt-4">
                                                        <input type="hidden" name="{{ $fieldName }}[{{ $questionIndex }}][question_options][{{ $optionIndex }}][is_correct]" value="0">
                                                        <input type="checkbox" name="{{ $fieldName }}[{{ $questionIndex }}][question_options][{{ $optionIndex }}][is_correct]" value="1" class="form-check-input" @checked(!empty($option['is_correct']))>
                                                        <label class="form-check-label">Correct</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-1 text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-option-row>Remove</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 d-grid">
            <button type="button" class="btn btn-outline-primary btn-lg fw-semibold" data-add-question-row data-bottom-add-question>
                Add another question
            </button>
        </div>
    </div>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

<script>
    (function () {
        const container = document.getElementById(@js($fieldId));

        if (!container || container.dataset.initialized === 'true') {
            return;
        }

        container.dataset.initialized = 'true';

        const questionRows = container.querySelector('[data-question-rows]');
        const addQuestionButtons = container.querySelectorAll('[data-add-question-row]');
        const importInput = document.querySelector('[data-question-import-input]');
        const importText = document.querySelector('[data-question-import-text]');
        const importTextButton = document.querySelector('[data-question-import-text-button]');
        const importFeedback = document.querySelector('[data-question-import-feedback]');
        const multipleChoiceType = @js(Question::TYPE_MULTIPLE_CHOICE);
        const fillInType = @js(Question::TYPE_FILL_IN);

        const questionRowTemplate = (index) => `
            <div data-question-row>
                <input type="hidden" name="{{ $fieldName }}[${index}][id]" value="">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="fw-semibold" data-question-title>Question ${index + 1}</div>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-question-row>Remove question</button>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="{{ $fieldName }}[${index}][type]" class="form-control" data-question-type>
                            <option value="${multipleChoiceType}">Multiple choice</option>
                            <option value="${fillInType}">Fill in</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Points</label>
                        <input type="number" step="0.25" min="0.25" name="{{ $fieldName }}[${index}][points]" value="1" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Time (seconds)</label>
                        <input type="number" min="1" max="3600" name="{{ $fieldName }}[${index}][time_limit_seconds]" value="" class="form-control">
                        <div class="form-text">When blank, the per-question timer uses the exam time divided by questions shown per attempt.</div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-4 pt-2" data-multiple-selection-wrapper>
                            <input type="hidden" name="{{ $fieldName }}[${index}][allows_multiple_selection]" value="0">
                            <input type="checkbox" name="{{ $fieldName }}[${index}][allows_multiple_selection]" value="1" class="form-check-input">
                            <label class="form-check-label">Allow multiple correct choices</label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Prompt</label>
                        <textarea name="{{ $fieldName }}[${index}][prompt]" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Help text</label>
                        <textarea name="{{ $fieldName }}[${index}][help_text]" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="col-md-12 d-none" data-fill-in-wrapper>
                        <label class="form-label">Accepted answers (one per line)</label>
                        <textarea name="{{ $fieldName }}[${index}][accepted_answers]" rows="4" class="form-control"></textarea>
                    </div>
                    <div class="col-md-12" data-options-wrapper>
                        <div data-options-panel>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="fw-semibold">Answer options</div>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-add-option-row>Add option</button>
                            </div>
                            <div class="d-flex flex-column gap-3" data-option-rows></div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const optionRowTemplate = (questionIndex, optionIndex) => `
            <div data-option-row>
                <input type="hidden" name="{{ $fieldName }}[${questionIndex}][question_options][${optionIndex}][id]" value="">
                <div class="row g-3 align-items-end">
                    <div class="col-md-9">
                        <label class="form-label">Label</label>
                        <input type="text" name="{{ $fieldName }}[${questionIndex}][question_options][${optionIndex}][label]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <div class="form-check mt-4">
                            <input type="hidden" name="{{ $fieldName }}[${questionIndex}][question_options][${optionIndex}][is_correct]" value="0">
                            <input type="checkbox" name="{{ $fieldName }}[${questionIndex}][question_options][${optionIndex}][is_correct]" value="1" class="form-check-input">
                            <label class="form-check-label">Correct</label>
                        </div>
                    </div>
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-option-row>Remove</button>
                    </div>
                </div>
            </div>
        `;

        const renumberQuestions = () => {
            [...questionRows.querySelectorAll('[data-question-row]')].forEach((row, questionIndex) => {
                row.querySelector('[data-question-title]').textContent = `Question ${questionIndex + 1}`;
                row.querySelectorAll('input, textarea, select').forEach((input) => {
                    input.name = input.name.replace(/questions\[\d+]/, `questions[${questionIndex}]`);
                });

                [...row.querySelectorAll('[data-option-row]')].forEach((optionRow, optionIndex) => {
                    optionRow.querySelectorAll('input').forEach((input) => {
                        input.name = input.name.replace(/question_options\]\[\d+]/, `question_options][${optionIndex}]`);
                    });
                });
            });
        };

        const syncQuestionType = (row) => {
            const type = row.querySelector('[data-question-type]')?.value;
            const isMultipleChoice = type === multipleChoiceType;
            row.querySelector('[data-options-wrapper]')?.classList.toggle('d-none', !isMultipleChoice);
            row.querySelector('[data-multiple-selection-wrapper]')?.classList.toggle('d-none', !isMultipleChoice);
            row.querySelector('[data-fill-in-wrapper]')?.classList.toggle('d-none', isMultipleChoice);
        };

        const ensureOptionRow = (row) => {
            const optionsHolder = row.querySelector('[data-option-rows]');
            if (!optionsHolder || optionsHolder.querySelectorAll('[data-option-row]').length > 0) {
                return;
            }

            const questionIndex = [...questionRows.querySelectorAll('[data-question-row]')].indexOf(row);
            optionsHolder.insertAdjacentHTML('beforeend', optionRowTemplate(questionIndex, 0));
        };

        const addQuestion = () => {
            const index = questionRows.querySelectorAll('[data-question-row]').length;
            questionRows.insertAdjacentHTML('beforeend', questionRowTemplate(index));
            const newRow = questionRows.querySelectorAll('[data-question-row]')[index];
            ensureOptionRow(newRow);
            syncQuestionType(newRow);
            renumberQuestions();
            newRow.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        const parseBoolean = (value, questionNumber) => {
            const normalizedValue = String(value ?? 'false').trim().toLowerCase();

            if (['true', '1', 'yes'].includes(normalizedValue)) {
                return true;
            }

            if (['false', '0', 'no'].includes(normalizedValue)) {
                return false;
            }

            throw new Error(`Question ${questionNumber} has an invalid MULTIPLE_CORRECT value.`);
        };

        const parseImportedQuestions = (contents) => {
            if (!contents.trim()) {
                throw new Error('The question import file is empty.');
            }

            const questionBlocks = contents
                .trim()
                .split(/^\s*---\s*$/m)
                .map((block) => block.trim())
                .filter(Boolean);

            if (!questionBlocks.length) {
                throw new Error('The question import file does not contain any question blocks.');
            }

            return questionBlocks.map((block, index) => {
                const questionNumber = index + 1;
                const fields = { options: [], correct: [] };
                let activeList = null;

                block.split(/\r\n|\r|\n/).forEach((rawLine) => {
                    const line = rawLine.trim();

                    if (!line) {
                        return;
                    }

                    const listMatch = line.match(/^(OPTIONS|CORRECT):\s*$/i);
                    if (listMatch) {
                        activeList = listMatch[1].toLowerCase();

                        return;
                    }

                    if (activeList && line.startsWith('-')) {
                        const value = line.slice(1).trim();

                        if (!value) {
                            throw new Error(`Question ${questionNumber} contains an empty ${activeList} entry.`);
                        }

                        fields[activeList].push(value);

                        return;
                    }

                    const fieldMatch = line.match(/^(TYPE|POINTS|MULTIPLE_CORRECT|PROMPT|HELP):\s*(.+)$/i);
                    if (fieldMatch) {
                        fields[fieldMatch[1].toLowerCase()] = fieldMatch[2].trim();
                        activeList = null;

                        return;
                    }

                    throw new Error(`Question ${questionNumber} has an unrecognized line: ${line}`);
                });

                if (![multipleChoiceType, fillInType].includes(fields.type)) {
                    throw new Error(`Question ${questionNumber} must declare TYPE as multiple_choice or fill_in.`);
                }

                if (!fields.prompt) {
                    throw new Error(`Question ${questionNumber} must include a PROMPT.`);
                }

                if (!Number.isFinite(Number(fields.points)) || Number(fields.points) < 0.25) {
                    throw new Error(`Question ${questionNumber} must include POINTS of at least 0.25.`);
                }

                const allowsMultipleSelection = parseBoolean(fields.multiple_correct, questionNumber);

                if (fields.type === fillInType) {
                    if (!fields.correct.length) {
                        throw new Error(`Question ${questionNumber} needs at least one CORRECT answer.`);
                    }

                    return {
                        type: fields.type,
                        prompt: fields.prompt,
                        helpText: fields.help ?? '',
                        points: fields.points,
                        allowsMultipleSelection: false,
                        acceptedAnswers: fields.correct,
                        options: [],
                    };
                }

                if (!fields.options.length) {
                    throw new Error(`Question ${questionNumber} needs at least one OPTIONS entry.`);
                }

                if (!fields.correct.length) {
                    throw new Error(`Question ${questionNumber} needs at least one CORRECT entry.`);
                }

                if (fields.correct.some((answer) => !fields.options.includes(answer))) {
                    throw new Error(`Question ${questionNumber} has a CORRECT answer that is not listed in OPTIONS.`);
                }

                return {
                    type: fields.type,
                    prompt: fields.prompt,
                    helpText: fields.help ?? '',
                    points: fields.points,
                    allowsMultipleSelection,
                    acceptedAnswers: [],
                    options: fields.options.map((label) => ({
                        label,
                        isCorrect: fields.correct.includes(label),
                    })),
                };
            });
        };

        const populateImportedQuestions = (questions) => {
            questionRows.innerHTML = '';

            questions.forEach((question, questionIndex) => {
                questionRows.insertAdjacentHTML('beforeend', questionRowTemplate(questionIndex));

                const row = questionRows.querySelectorAll('[data-question-row]')[questionIndex];
                const questionType = row.querySelector('[data-question-type]');
                const numericInputs = row.querySelectorAll('input[type="number"]');
                const points = numericInputs[0];
                const timeLimitSeconds = numericInputs[1];
                const textareas = row.querySelectorAll('textarea');
                const multipleSelection = row.querySelector('[data-multiple-selection-wrapper] input[type="checkbox"]');
                const optionsHolder = row.querySelector('[data-option-rows]');

                questionType.value = question.type;
                points.value = question.points;
                timeLimitSeconds.value = question.timeLimitSeconds ?? '';
                textareas[0].value = question.prompt;
                textareas[1].value = question.helpText;
                textareas[2].value = question.acceptedAnswers.join('\n');
                multipleSelection.checked = question.allowsMultipleSelection;
                optionsHolder.innerHTML = '';

                question.options.forEach((option, optionIndex) => {
                    optionsHolder.insertAdjacentHTML('beforeend', optionRowTemplate(questionIndex, optionIndex));

                    const optionRow = optionsHolder.querySelectorAll('[data-option-row]')[optionIndex];
                    optionRow.querySelector('input[type="text"]').value = option.label;
                    optionRow.querySelector('input[type="checkbox"]').checked = option.isCorrect;
                });

                syncQuestionType(row);
            });

            renumberQuestions();
            questionRows.firstElementChild?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        const setImportFeedback = (message, isError = false) => {
            if (!importFeedback) {
                return;
            }

            importFeedback.textContent = message;
            importFeedback.classList.toggle('text-danger', isError);
            importFeedback.classList.toggle('text-success', !isError && message !== '');
        };

        addQuestionButtons.forEach((button) => {
            button.addEventListener('click', addQuestion);
        });

        questionRows.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            if (target.matches('[data-remove-question-row]')) {
                const rows = questionRows.querySelectorAll('[data-question-row]');
                if (rows.length === 1) {
                    return;
                }

                target.closest('[data-question-row]')?.remove();
                renumberQuestions();
                return;
            }

            if (target.matches('[data-add-option-row]')) {
                const row = target.closest('[data-question-row]');
                const optionsHolder = row?.querySelector('[data-option-rows]');
                if (!row || !optionsHolder) {
                    return;
                }

                const questionIndex = [...questionRows.querySelectorAll('[data-question-row]')].indexOf(row);
                const optionIndex = optionsHolder.querySelectorAll('[data-option-row]').length;
                optionsHolder.insertAdjacentHTML('beforeend', optionRowTemplate(questionIndex, optionIndex));
                renumberQuestions();
                return;
            }

            if (target.matches('[data-remove-option-row]')) {
                const row = target.closest('[data-question-row]');
                const options = row?.querySelectorAll('[data-option-row]') ?? [];
                if (options.length === 1) {
                    options[0].querySelectorAll('input[type="text"]').forEach((input) => input.value = '');
                    options[0].querySelectorAll('input[type="checkbox"]').forEach((input) => input.checked = false);
                    return;
                }

                target.closest('[data-option-row]')?.remove();
                renumberQuestions();
            }
        });

        questionRows.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement) || !target.matches('[data-question-type]')) {
                return;
            }

            const row = target.closest('[data-question-row]');
            if (row) {
                ensureOptionRow(row);
                syncQuestionType(row);
            }
        });

        questionRows.querySelectorAll('[data-question-row]').forEach((row) => {
            ensureOptionRow(row);
            syncQuestionType(row);
        });

        renumberQuestions();

        importInput?.addEventListener('change', async () => {
            const file = importInput.files?.[0];

            if (!file) {
                setImportFeedback('');

                return;
            }

            if (file.size > 1024 * 1024) {
                importInput.value = '';
                setImportFeedback('The question import file must not be larger than 1 MB.', true);

                return;
            }

            try {
                const questions = parseImportedQuestions(await file.text());

                populateImportedQuestions(questions);
                importInput.classList.remove('is-invalid');
                setImportFeedback(`${questions.length} question${questions.length === 1 ? '' : 's'} loaded. Review or edit them below, then save the exam.`);
            } catch (error) {
                importInput.value = '';
                importInput.classList.add('is-invalid');
                setImportFeedback(error instanceof Error ? error.message : 'Unable to read this question import file.', true);
            }
        });

        importTextButton?.addEventListener('click', () => {
            try {
                const questions = parseImportedQuestions(importText?.value ?? '');

                populateImportedQuestions(questions);
                importText?.classList.remove('is-invalid');
                if (importText) {
                    importText.value = '';
                }
                setImportFeedback(`${questions.length} question${questions.length === 1 ? '' : 's'} loaded. Review or edit them below, then save the exam.`);
            } catch (error) {
                importText?.classList.add('is-invalid');
                setImportFeedback(error instanceof Error ? error.message : 'Unable to read the pasted questions.', true);
            }
        });
    })();
</script>
