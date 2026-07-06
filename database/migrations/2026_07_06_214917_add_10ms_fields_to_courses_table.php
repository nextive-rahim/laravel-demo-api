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
        Schema::table('courses', function (Blueprint $table) {
            $table->string('thumbnail_path')->nullable()->after('description');
            $table->string('instructor_name')->nullable()->after('thumbnail_path');
            $table->string('instructor_title')->nullable()->after('instructor_name');
            $table->string('instructor_image_path')->nullable()->after('instructor_title');
            $table->unsignedInteger('price')->nullable()->after('instructor_image_path');
            $table->unsignedInteger('discount_price')->nullable()->after('price');
            $table->decimal('rating', 2, 1)->nullable()->after('discount_price');
            $table->unsignedInteger('rating_count')->default(0)->after('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'thumbnail_path', 'instructor_name', 'instructor_title',
                'instructor_image_path', 'price', 'discount_price', 'rating', 'rating_count',
            ]);
        });
    }
};
