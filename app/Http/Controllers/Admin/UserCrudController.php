<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
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
    }

    public function setupListOperation(): void
    {
        CRUD::column('name');
        CRUD::column('email');
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
        CRUD::setRequest($this->sanitizePasswordField(CRUD::validateRequest()));

        return $this->traitStore();
    }

    public function update(): RedirectResponse
    {
        CRUD::setRequest($this->sanitizePasswordField(CRUD::validateRequest()));

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
}
