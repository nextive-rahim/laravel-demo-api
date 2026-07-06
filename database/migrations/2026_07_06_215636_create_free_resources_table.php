<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('free_resources', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('note')->index(); // note | pdf | book
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('file_url')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('free_resources');
    }
};
