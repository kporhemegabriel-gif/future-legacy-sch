<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        // Same authorization shape as the Phase 1 dashboard: derived from
        // the authenticated user's own relation, never a route parameter.
        $student = $request->user()->student()
            ->with(['schoolClass.academicYear', 'academicYear', 'guardians', 'enrollments.subject'])
            ->firstOrFail();

        return view('student.profile', compact('student'));
    }
}
