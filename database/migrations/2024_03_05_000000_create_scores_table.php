<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // subject_id/class_id/academic_year_id/term_id are denormalized
        // from the assessment at write time (same pattern Phase 2 used for
        // enrollments.class_id/academic_year_id) — makes term-result
        // queries a straightforward filter instead of a join through
        // assessments every time, and they're never trusted from the
        // request: see StoreScoreRequest / ScoreController.
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->restrictOnDelete();
            // Proof the student is legitimately enrolled for this subject
            // in this class/year — no enrollment, no score. See §4/§12 of
            // the Phase 3 spec.
            $table->foreignId('enrollment_id')->constrained('enrollments')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('term_id')->constrained('terms')->restrictOnDelete();
            $table->decimal('score', 5, 2);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['assessment_id', 'student_id'], 'score_per_assessment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
