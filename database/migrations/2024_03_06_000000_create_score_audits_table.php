<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only audit trail (§13). `score_id` is nullable and
        // nullOnDelete rather than restrict — scores are never actually
        // deleted by this application (see ScoreController), but if one
        // ever were, its history should survive as an orphaned record
        // rather than blocking the deletion or vanishing with it.
        Schema::create('score_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('score_id')->nullable()->constrained('scores')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('old_score', 5, 2)->nullable(); // null = this was the first time a score was recorded
            $table->decimal('new_score', 5, 2);
            $table->timestamp('changed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_audits');
    }
};
