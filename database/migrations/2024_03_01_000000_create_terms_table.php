<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A term belongs to exactly one academic year (First Term of
        // 2025/2026 is a different row from First Term of 2026/2027) —
        // mirrors the existing academic_years.is_current convention rather
        // than inventing a new one.
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('name'); // e.g. "First Term"
            $table->unsignedTinyInteger('sequence')->default(1); // 1/2/3 — for ordering, not display
            $table->boolean('is_current')->default(false)->index();
            $table->timestamps();

            $table->unique(['academic_year_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
