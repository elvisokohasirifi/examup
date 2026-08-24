<div class="mt-3 d-flex justify-content-between align-items-center">
    <a class="btn btn-outline-secondary" href="#exam-step-details" data-exam-builder-prev>Back to details</a>
    <div class="text-muted small">Save the exam when both steps are complete.</div>
    <a class="btn btn-primary" href="#exam-step-questions" data-exam-builder-next>Continue to questions</a>
</div>

<script>
    (function () {
        if (window.examBuilderWizardInitialized) {
            return;
        }

        window.examBuilderWizardInitialized = true;

        const detailFields = () => document.querySelectorAll('.js-exam-step-1');
        const questionFields = () => document.querySelectorAll('.js-exam-step-2');
        const stepTriggers = () => document.querySelectorAll('[data-step-trigger]');
        const nextButton = document.querySelector('[data-exam-builder-next]');
        const prevButton = document.querySelector('[data-exam-builder-prev]');
        const form = document.getElementById('crudForm');
        const examCheckboxFields = [
            'allow_back_navigation',
            'shuffle_questions',
            'show_score_to_student',
            'show_correct_answers_to_student',
            'show_index_number_field',
            'disable_copy_paste',
            'is_published',
        ];

        const syncExamCheckboxes = () => {
            examCheckboxFields.forEach((fieldName) => {
                const hiddenInput = form?.querySelector(`input[type="hidden"][name="${fieldName}"]`);
                const checkbox = hiddenInput?.parentElement?.querySelector('input[type="checkbox"]');

                if (!hiddenInput || !checkbox) {
                    return;
                }

                hiddenInput.value = checkbox.checked ? '1' : '0';
            });
        };

        const setStep = (step) => {
            detailFields().forEach((field) => {
                field.style.display = step === 1 ? '' : 'none';
            });
            questionFields().forEach((field) => {
                field.style.display = step === 2 ? '' : 'none';
            });

            stepTriggers().forEach((trigger) => {
                const isActive = Number(trigger.dataset.stepTrigger) === step;
                trigger.classList.toggle('btn-primary', isActive);
                trigger.classList.toggle('btn-outline-secondary', !isActive);
            });

            if (nextButton) {
                nextButton.classList.toggle('d-none', step === 2);
            }

            if (prevButton) {
                prevButton.classList.toggle('d-none', step === 1);
            }
        };

        stepTriggers().forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                setStep(Number(trigger.dataset.stepTrigger));
            });
        });

        nextButton?.addEventListener('click', (event) => {
            event.preventDefault();
            setStep(2);
            document.querySelector('#exam-step-questions')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        prevButton?.addEventListener('click', (event) => {
            event.preventDefault();
            setStep(1);
            document.querySelector('#exam-step-details')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        form?.addEventListener('change', (event) => {
            if (event.target instanceof HTMLInputElement && event.target.type === 'checkbox') {
                syncExamCheckboxes();
            }
        });
        form?.addEventListener('submit', syncExamCheckboxes);

        syncExamCheckboxes();
        setStep(1);
    })();
</script>
