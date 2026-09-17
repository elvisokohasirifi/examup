<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
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
        $user = $this->route('id');

        if (is_string($user)) {
            $user = User::query()->find($user);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_EXAMINER])],
            'password' => array_filter([
                $user instanceof User ? 'nullable' : 'required',
                'string',
                'min:8',
            ]),
        ];

        if (Tenancy::enabled()) {
            $rules['organization_id'] = ['nullable', 'uuid', Rule::exists('organizations', 'id')];
            $rules['department_id'] = ['nullable', 'uuid', Rule::exists('departments', 'id')];
            $rules['is_department_admin'] = ['boolean'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! Tenancy::enabled() || ! filled($this->input('department_id'))) {
                    return;
                }

                $department = Department::query()->find($this->input('department_id'));

                $organizationId = $this->user()?->isPlatformAdmin()
                    ? $this->input('organization_id')
                    : $this->user()?->organization_id;

                if ($department === null || $department->organization_id !== $organizationId) {
                    $validator->errors()->add('department_id', 'The department must belong to the selected organisation.');
                }
            },
        ];
    }
}
