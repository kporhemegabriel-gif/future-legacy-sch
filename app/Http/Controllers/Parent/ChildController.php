<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ChildController extends Controller
{
    public function index(Request $request): View
    {
        $parentProfile = $request->user()->parentProfile()->with(['students.schoolClass'])->first();
        $children = $parentProfile?->students ?? collect();

        return view('parent.children.index', compact('children'));
    }

    public function show(Request $request, Student $student): View
    {
        // Re-derived server-side via the Policy, exactly the pattern
        // flagged as required in REVIEW.md — never trust the {student}
        // route parameter alone for a parent-facing route.
        Gate::authorize('view', $student);

        $student->load(['schoolClass.academicYear', 'academicYear', 'enrollments.subject']);

        return view('parent.children.show', compact('student'));
    }
}
