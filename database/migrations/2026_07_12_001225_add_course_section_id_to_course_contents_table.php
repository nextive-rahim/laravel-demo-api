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
        Schema::table('course_contents', function (Blueprint $table) {
            // Nullable so existing (ungrouped) content keeps working; a deleted
            // section detaches its content rather than destroying it.
            $table->foreignId('course_section_id')->nullable()->after('course_id')
                ->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_contents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_section_id');
        });
    }
};
