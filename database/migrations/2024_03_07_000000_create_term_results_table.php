<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per student per term — a computed-and-published summary.
        // Per-subject breakdown is deliberately NOT stored here; it's
        // computed live from scores/assessments every time (by
        // ResultCalculationService), so there is exactly one source of
        // truth for subject-level numbers. This table only holds what
        // genuinely needs to persist: the publish/draft status and the
        // term-level aggregates (average, position) that require the
        // whole class to be computed together.
        Schema::create('term_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('term_id')->constrained('terms')->restrictOnDelete();
            $table->enum('status', ['draft', 'published'])->default('draft')->index();
            $table->unsignedTinyInteger('total_subjects')->default(0);
            $table->decimal('average_percentage', 5, 2)->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_results');
    }
};
