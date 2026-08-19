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
        Schema::create('suspicious_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_attempt_id');
            $table->string('event_type');
            $table->string('severity')->default('low');
            $table->text('details')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->foreign('exam_attempt_id')->references('id')->on('exam_attempts')->cascadeOnDelete();
            $table->index(['exam_attempt_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspicious_activities');
    }
};
