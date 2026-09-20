<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A student's enrollment in one subject for one academic year.
        // `class_id` is captured at enrollment time (not just derived from
        // the student's *current* class) so a student's Phase-2 enrollment
        // history stays accurate even if they're moved to a different
        // class later — Phase 3's per-term assessment scores read this
        // table, not the student's live class_id.
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->enum('status', ['enrolled', 'dropped', 'completed'])->default('enrolled')->index();
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'academic_year_id'], 'enrollment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
