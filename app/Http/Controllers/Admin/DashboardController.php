<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentGuardian;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Phase 1 shows real counts for what already exists (students,
        // parents, classes). Attendance / fees / results summaries plug
        // into the same cards once those phases land — see the README's
        // "Admin dashboard" section for the full target list.
        $stats = [
            'total_students' => Student::count(),
            'total_parents' => ParentGuardian::count(),
            'total_classes' => SchoolClass::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
