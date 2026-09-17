<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->index();
            $table->uuid('department_id')->nullable()->index();
            $table->boolean('is_platform_admin')->default(false)->index();
            $table->boolean('is_department_admin')->default(false);
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });

        Schema::table('exams', function (Blueprint $table): void {
            $table->uuid('organization_id')->nullable()->index();
            $table->uuid('department_id')->nullable()->index();
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });

        Schema::table('departments', function (Blueprint $table): void {
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['organization_id', 'department_id', 'is_platform_admin', 'is_department_admin']);
        });

        Schema::table('exams', function (Blueprint $table): void {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['organization_id', 'department_id']);
        });

        Schema::table('departments', function (Blueprint $table): void {
            $table->dropForeign(['organization_id']);
        });
    }
};
