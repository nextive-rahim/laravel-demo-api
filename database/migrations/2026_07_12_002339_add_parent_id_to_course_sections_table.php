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
        Schema::table('course_sections', function (Blueprint $table) {
            // Self-reference: a sub-section points at its parent section.
            // Deleting a parent cascades to its sub-sections.
            $table->foreignId('parent_id')->nullable()->after('course_id')
                ->constrained('course_sections')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_sections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
