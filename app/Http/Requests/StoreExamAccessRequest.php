<?php

namespace App\Http\Requests;

use App\Models\Exam;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExamAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $exam = $this->route('exam');

        return $exam instanceof Exam && (bool) $this->user()?->can('update', $exam);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['shareable_link', 'email_list'])],
            'emails' => ['nullable', 'array'],
            'emails.*' => ['required', 'email', 'max:255', 'distinct:strict'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $emails = $this->input('emails', []);

        if (is_string($emails)) {
            $emails = preg_split('/\r\n|\r|\n/', $emails) ?: [];
        }

        $this->merge([
            'emails' => collect($emails)
                ->map(fn ($email) => trim((string) $email))
                ->filter()
                ->values()
                ->all(),
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('action') === 'email_list' && empty($this->input('emails', []))) {
                    $validator->errors()->add('emails', 'Please provide at least one email address.');
                }
            },
        ];
    }
}
