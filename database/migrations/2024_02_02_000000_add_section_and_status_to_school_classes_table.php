<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            // e.g. "Grade 9A" (name) + "Blue" (section) for schools that
            // split one grade into named streams. Nullable — most schools
            // in Phase 1's seed data don't use this.
            $table->string('section')->nullable()->after('name');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('academic_year_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('school_classes', function (Blueprint $table) {
            $table->dropColumn(['section', 'status']);
        });
    }
};
