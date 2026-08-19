<?php

namespace App\Http\Requests;

use App\Models\ExamAccessLink;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExamAccessLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $link = $this->route('id') instanceof ExamAccessLink ? $this->route('id') : null;

        return $link === null
            ? (bool) $this->user()?->can('create', ExamAccessLink::class)
            : (bool) $this->user()?->can('update', $link);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exam_id' => ['required', 'uuid', 'exists:exams,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'emails' => ['nullable', 'array', 'min:1'],
            'emails.*' => ['required', 'email', 'max:255', 'distinct:strict'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'send_email' => ['boolean'],
            'is_active' => ['boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $emails = $this->input('emails', []);

        if (is_string($emails)) {
            $emails = preg_split('/\r\n|\r|\n/', $emails) ?: [];
        }

        $emails = collect($emails)
            ->map(fn ($email) => trim((string) $email))
            ->filter()
            ->values()
            ->all();

        $this->merge([
            'emails' => $emails,
            'email' => filled($this->input('email')) ? trim((string) $this->input('email')) : null,
        ]);
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasEmails = ! empty($this->input('emails', []));
                $hasEmail = filled($this->input('email'));

                if (! $hasEmails && ! $hasEmail) {
                    $validator->errors()->add('emails', 'Please provide at least one email address.');
                }
            },
        ];
    }
}
