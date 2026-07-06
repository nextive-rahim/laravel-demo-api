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
        Schema::create('student_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('institute');
            $table->string('roll')->nullable();
            $table->string('batch')->nullable();
            $table->text('review')->nullable();
            $table->string('image_path')->nullable();
            $table->string('video_url')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_reviews');
    }
};
