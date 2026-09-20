<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Additive column on the existing `students` table (Phase 1 already
        // shipped this table) — deactivating/graduating/withdrawing a
        // student must not delete their historical academic records, so
        // this is a status flag, not a soft-delete.
        Schema::table('students', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive', 'graduated', 'withdrawn'])
                ->default('active')
                ->after('profile_photo')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
