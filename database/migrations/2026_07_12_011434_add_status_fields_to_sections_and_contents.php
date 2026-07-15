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
            $table->boolean('is_active')->default(true)->after('title');
        });

        Schema::table('course_contents', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('title');
            $table->boolean('is_paid')->default(false)->after('is_active'); // false = free preview
            $table->timestamp('available_from')->nullable()->after('is_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_sections', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('course_contents', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'is_paid', 'available_from']);
        });
    }
};
