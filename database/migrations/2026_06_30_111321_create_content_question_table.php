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
        // Pivot: the store questions attached to an exam content item.
        Schema::create('content_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('marks')->nullable(); // optional per-exam override of the question's marks
            $table->timestamps();

            $table->unique(['course_content_id', 'question_id']);
            $table->index(['course_content_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_question');
    }
};
