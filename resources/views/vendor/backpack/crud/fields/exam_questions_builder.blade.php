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
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted small">Add and order the questions directly inside the exam form.</div>
            <button type="button" class="btn btn-sm btn-outline-primary" data-add-question-row>Add question</button>
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
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select name="{{ $fieldName }}[{{ $questionIndex }}][type]" class="form-control" data-question-type>
                                <option value="{{ Question::TYPE_MULTIPLE_CHOICE }}" @selected(($question['type'] ?? '') === Question::TYPE_MULTIPLE_CHOICE)>Multiple choice</option>
                                <option value="{{ Question::TYPE_FILL_IN }}" @selected(($question['type'] ?? '') === Question::TYPE_FILL_IN)>Fill in</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Points</label>
                            <input type="number" step="0.25" min="0.25" name="{{ $fieldName }}[{{ $questionIndex }}][points]" value="{{ $question['points'] ?? '1' }}" class="form-control">
                        </div>
                        <div class="col-md-4">
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
        const addQuestionButton = container.querySelector('[data-add-question-row]');
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
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="{{ $fieldName }}[${index}][type]" class="form-control" data-question-type>
                            <option value="${multipleChoiceType}">Multiple choice</option>
                            <option value="${fillInType}">Fill in</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Points</label>
                        <input type="number" step="0.25" min="0.25" name="{{ $fieldName }}[${index}][points]" value="1" class="form-control">
                    </div>
                    <div class="col-md-4">
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

        addQuestionButton?.addEventListener('click', () => {
            const index = questionRows.querySelectorAll('[data-question-row]').length;
            questionRows.insertAdjacentHTML('beforeend', questionRowTemplate(index));
            const newRow = questionRows.querySelectorAll('[data-question-row]')[index];
            ensureOptionRow(newRow);
            syncQuestionType(newRow);
            renumberQuestions();
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
    })();
</script>
