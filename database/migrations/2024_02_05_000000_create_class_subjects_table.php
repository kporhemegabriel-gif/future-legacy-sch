<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which subjects are taught in which class, for a given academic
        // year. `academic_year_id` is stored redundantly alongside
        // `class_id` (rather than derived from school_classes.academic_year_id)
        // so a class-subject assignment's year is always explicit even if
        // a class record is ever reused or its year edited later.
        Schema::create('class_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['class_id', 'subject_id', 'academic_year_id'], 'class_subject_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_subjects');
    }
};
