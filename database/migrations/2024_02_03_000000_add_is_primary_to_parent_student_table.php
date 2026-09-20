<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Marks which guardian is the primary contact when a student has
        // more than one linked parent/guardian. Not enforced as "exactly
        // one primary per student" at the DB level (MySQL has no partial
        // unique index) — enforced in the application layer instead, see
        // ParentGuardian::syncStudent().
        Schema::table('parent_student', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('relationship');
        });
    }

    public function down(): void
    {
        Schema::table('parent_student', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
