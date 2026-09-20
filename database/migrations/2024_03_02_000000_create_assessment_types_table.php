<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configurable per §3 of the Phase 3 spec — a school might call
        // these "Continuous Assessment / Test / Assignment / Examination",
        // or something else entirely. Never hard-coded in controllers.
        Schema::create('assessment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. "Continuous Assessment"
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_types');
    }
};
