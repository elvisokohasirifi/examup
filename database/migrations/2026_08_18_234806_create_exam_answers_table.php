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
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_attempt_id');
            $table->uuid('question_id');
            $table->text('answer_text')->nullable();
            $table->json('selected_option_ids')->nullable();
            $table->json('graded_payload')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->decimal('score', 8, 2)->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->foreign('exam_attempt_id')->references('id')->on('exam_attempts')->cascadeOnDelete();
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
            $table->unique(['exam_attempt_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};
