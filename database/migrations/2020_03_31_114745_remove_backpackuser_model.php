<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RemoveBackpackuserModel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // establish the table names
        $model_has_roles = config('permission.table_names.model_has_roles');
        $model_has_permissions = config('permission.table_names.model_has_permissions');

        // replace the BackpackUser model with User
        if (Schema::hasTable($model_has_roles)) {
            $this->replaceModels($model_has_roles);
        }
        if (Schema::hasTable($model_has_permissions)) {
            $this->replaceModels($model_has_permissions);
        }
    }

    public function replaceModels($table_name)
    {
        $rolePivot = config('permission.column_names.role_pivot_key') ?? 'role_id';
        $modelKey = config('permission.column_names.model_morph_key') ?? 'model_id';

        Log::info('Replacing BackpackUser model in '.$table_name);

        // if you've ended up with duplicate entries (both for App\User and App\Models\BackpackUser)
        // we can just delete them
        $userEntries = DB::table($table_name)
            ->where('model_type', "App\User")
            ->get();

        foreach ($userEntries as $entry) {
            DB::table($table_name)
                ->where($rolePivot, $entry->{$rolePivot})
                ->where('model_type', 'App\Models\BackpackUser')
                ->where($modelKey, $entry->{$modelKey})
                ->delete();
        }

        // for the rest of them, we can just replace the BackpackUser model with User
        DB::table($table_name)
            ->where('model_type', "App\Models\BackpackUser")
            ->update([
                'model_type' => "App\User",
            ]);
    }
}
