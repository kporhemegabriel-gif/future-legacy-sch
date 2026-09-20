<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TermResult;
use App\Services\ResultCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function __construct(private readonly ResultCalculationService $results)
    {
    }

    public function index(Request $request): View
    {
        // Derived from the authenticated user's own record — never a route
        // parameter — same shape as every other student-facing controller
        // in this app.
        $student = $request->user()->student()->firstOrFail();

        $termResults = TermResult::where('student_id', $student->id)
            ->where('status', 'published')
            ->with(['term.academicYear'])
            ->orderByDesc('id')
            ->get();

        return view('student.results.index', compact('termResults'));
    }

    public function show(Request $request, TermResult $termResult): View
    {
        Gate::authorize('view', $termResult);

        $termResult->load(['student', 'schoolClass', 'academicYear', 'term']);
        $breakdown = $this->results->studentTermBreakdown($termResult->student, $termResult->schoolClass, $termResult->term);

        return view('student.results.show', compact('termResult', 'breakdown'));
    }
}
