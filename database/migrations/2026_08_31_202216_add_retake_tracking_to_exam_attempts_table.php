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
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->timestamp('superseded_at')->nullable()->index()->after('submitted_at');
            $table->uuid('superseded_by_attempt_id')->nullable()->index()->after('superseded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex(['superseded_at']);
            $table->dropIndex(['superseded_by_attempt_id']);
            $table->dropColumn(['superseded_at', 'superseded_by_attempt_id']);
        });
    }
};
