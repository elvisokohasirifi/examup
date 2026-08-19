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
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_id');
            $table->string('type');
            $table->unsignedInteger('position');
            $table->text('prompt');
            $table->text('help_text')->nullable();
            $table->decimal('points', 8, 2)->default(1);
            $table->boolean('allows_multiple_selection')->default(false);
            $table->json('accepted_answers')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->foreign('exam_id')->references('id')->on('exams')->cascadeOnDelete();
            $table->unique(['exam_id', 'position']);
            $table->index(['exam_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
