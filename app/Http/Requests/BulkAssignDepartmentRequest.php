<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkAssignDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageUsers();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'uuid', 'exists:departments,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'uuid', 'exists:users,id'],
            'make_department_admins' => ['boolean'],
        ];
    }
}
