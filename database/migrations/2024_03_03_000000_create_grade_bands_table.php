<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configurable grading scale (§6), scoped to one Academic Year:
        // Academic Year → Grading Scale → Grade Ranges. This lets a school
        // change its scale in a future year while historical results keep
        // using the scale that was actually in force for their own year —
        // see ResultCalculationService::gradeBandFor(), which always looks
        // up bands by the result's own academic_year_id, never "whatever
        // is currently active". No scale is hard-coded anywhere in
        // application code; overlap between active bands *within the same
        // year* is prevented in GradeBandRequest, not here (MySQL has no
        // clean way to express "no overlapping ranges" as a table
        // constraint) — bands in different years are free to overlap,
        // since they're independent scales.
        Schema::create('grade_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->unsignedTinyInteger('min_score');
            $table->unsignedTinyInteger('max_score');
            $table->string('grade', 10); // e.g. "A"
            $table->string('remark', 100); // e.g. "Excellent"
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_bands');
    }
};
