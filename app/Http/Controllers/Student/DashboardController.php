<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // A student can only ever see their *own* profile — there is no
        // route parameter here to spoof, deliberately. Broader academic
        // data (grades, attendance, assessment results) attaches to this
        // same record in Phase 3/4 without changing this authorization shape.
        $student = $request->user()->student()->with(['schoolClass', 'academicYear'])->first();

        return view('student.dashboard', compact('student'));
    }
}
