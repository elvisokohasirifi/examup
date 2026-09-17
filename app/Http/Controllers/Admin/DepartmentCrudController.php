<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreDepartmentRequest;
use App\Models\Department;
use App\Models\Organization;
use App\Support\Tenancy;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class DepartmentCrudController extends CrudController
{
    use CreateOperation { store as traitStore; }
    use DeleteOperation;
    use ListOperation;
    use UpdateOperation { update as traitUpdate; }

    public function setup(): void
    {
        abort_unless(Tenancy::enabled() && backpack_user()->canManageDepartments(), 403);

        CRUD::setModel(Department::class);
        CRUD::setRoute(backpack_url('department'));
        CRUD::setEntityNameStrings('department', 'departments');

        if (! backpack_user()->isPlatformAdmin()) {
            CRUD::addClause('where', 'organization_id', backpack_user()->organization_id);
        }
    }

    public function setupListOperation(): void
    {
        CRUD::column('name');
        CRUD::column('slug');
        CRUD::column('created_at')->type('datetime');
    }

    public function setupCreateOperation(): void
    {
        CRUD::setValidation(StoreDepartmentRequest::class);
        $this->addFields();
    }

    public function setupUpdateOperation(): void
    {
        CRUD::setValidation(StoreDepartmentRequest::class);
        $this->addFields();
    }

    public function store(): RedirectResponse
    {
        CRUD::setRequest($this->departmentRequest(CRUD::validateRequest()));

        return $this->traitStore();
    }

    public function update(): RedirectResponse
    {
        CRUD::setRequest($this->departmentRequest(CRUD::validateRequest()));

        return $this->traitUpdate();
    }

    private function addFields(): void
    {
        CRUD::field('name')->type('text')->wrapper(['class' => 'form-group col-md-6']);
        CRUD::field('slug')->type('text')->hint('Leave blank to generate from the department name.')->wrapper(['class' => 'form-group col-md-6']);
        if (backpack_user()->isPlatformAdmin()) {
            CRUD::addField([
                'name' => 'organization_id',
                'label' => 'Organisation',
                'type' => 'select_from_array',
                'options' => Organization::query()->orderBy('name')->pluck('name', 'id')->all(),
            ]);
        } else {
            CRUD::field('organization_id')->type('hidden')->value(backpack_user()->organization_id);
        }
    }

    private function departmentRequest(StoreDepartmentRequest $request): StoreDepartmentRequest
    {
        $request->merge([
            'organization_id' => backpack_user()->isPlatformAdmin()
                ? $request->input('organization_id')
                : backpack_user()->organization_id,
            'slug' => Str::slug($request->input('slug') ?: $request->input('name')),
        ]);

        return $request;
    }
}
