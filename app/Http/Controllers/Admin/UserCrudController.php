<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreUserRequest;
use App\Models\Department;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\RedirectResponse;

class UserCrudController extends CrudController
{
    use CreateOperation { store as traitStore; }
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation { update as traitUpdate; }

    public function setup(): void
    {
        CRUD::setModel(User::class);
        CRUD::setRoute(backpack_url('user'));
        CRUD::setEntityNameStrings('user', 'users');

        if (Tenancy::enabled() && ! backpack_user()->isPlatformAdmin()) {
            CRUD::addClause('where', 'organization_id', backpack_user()->organization_id);

            if (backpack_user()->isDepartmentAdmin()) {
                CRUD::addClause('where', 'department_id', backpack_user()->department_id);
            }
        }
    }

    public function setupListOperation(): void
    {
        CRUD::column('name');
        CRUD::column('email');
        if (Tenancy::enabled()) {
            CRUD::column('department_id')->label('Department')->type('select')->entity('department')->attribute('name');
            CRUD::column('is_department_admin')->label('Department admin')->type('boolean');
        }
        CRUD::addColumn([
            'name' => 'role',
            'label' => 'Role',
            'type' => 'text',
        ]);
        CRUD::column('created_at')->type('datetime');
    }

    public function setupCreateOperation(): void
    {
        CRUD::setValidation(StoreUserRequest::class);
        $this->addFields();
    }

    public function setupUpdateOperation(): void
    {
        CRUD::setValidation(StoreUserRequest::class);
        $this->addFields();
    }

    public function setupShowOperation(): void
    {
        CRUD::column('name');
        CRUD::column('email');
        CRUD::column('role');
        CRUD::column('created_at')->type('datetime');
        CRUD::column('updated_at')->type('datetime');
    }

    public function store(): RedirectResponse
    {
        CRUD::setRequest($this->normalizeTenantAssignment($this->sanitizePasswordField(CRUD::validateRequest())));

        return $this->traitStore();
    }

    public function update(): RedirectResponse
    {
        CRUD::setRequest($this->normalizeTenantAssignment($this->sanitizePasswordField(CRUD::validateRequest())));

        return $this->traitUpdate();
    }

    protected function addFields(): void
    {
        CRUD::field('name')->type('text')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('email')->type('email')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::addField([
            'name' => 'role',
            'label' => 'Role',
            'type' => 'select_from_array',
            'options' => [
                User::ROLE_ADMIN => 'Admin',
                User::ROLE_EXAMINER => 'Examiner',
            ],
            'allows_null' => false,
            'default' => User::ROLE_EXAMINER,
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);
        if (Tenancy::enabled()) {
            $actor = backpack_user();
            $organizationId = $actor->isPlatformAdmin() ? null : $actor->organization_id;
            $departmentOptions = Department::query()
                ->when($organizationId !== null, fn ($query) => $query->where('organization_id', $organizationId))
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();

            if ($actor->isPlatformAdmin()) {
                CRUD::addField([
                    'name' => 'organization_id',
                    'label' => 'Organisation',
                    'type' => 'select_from_array',
                    'options' => Organization::query()->orderBy('name')->pluck('name', 'id')->all(),
                    'wrapper' => ['class' => 'form-group col-md-6'],
                ]);
            } else {
                CRUD::addField(['name' => 'organization_id', 'type' => 'hidden', 'value' => $actor->organization_id]);
            }

            CRUD::addField([
                'name' => 'department_id',
                'label' => 'Department',
                'type' => 'select_from_array',
                'options' => $departmentOptions,
                'allows_null' => $actor->isOrganizationAdmin() || $actor->isPlatformAdmin(),
                'wrapper' => ['class' => 'form-group col-md-6'],
            ]);
            CRUD::addField([
                'name' => 'is_department_admin',
                'label' => 'Department administrator',
                'type' => 'checkbox',
                'wrapper' => ['class' => 'form-group col-md-6'],
            ]);
        }
        CRUD::addField([
            'name' => 'password',
            'label' => 'Password',
            'type' => 'password',
            'wrapper' => ['class' => 'form-group col-md-6'],
            'hint' => CRUD::getCurrentOperation() === 'update'
                ? 'Leave blank to keep the current password.'
                : null,
        ]);
    }

    protected function sanitizePasswordField(StoreUserRequest $request): StoreUserRequest
    {
        if (! filled($request->input('password'))) {
            $request->request->remove('password');
        }

        return $request;
    }

    protected function normalizeTenantAssignment(StoreUserRequest $request): StoreUserRequest
    {
        if (! Tenancy::enabled()) {
            return $request;
        }

        $actor = backpack_user();

        if ($actor->isPlatformAdmin()) {
            return $request;
        }

        $request->merge(['organization_id' => $actor->organization_id]);

        if ($actor->isDepartmentAdmin()) {
            $request->merge([
                'department_id' => $actor->department_id,
                'role' => User::ROLE_EXAMINER,
                'is_department_admin' => false,
            ]);
        }

        return $request;
    }
}
