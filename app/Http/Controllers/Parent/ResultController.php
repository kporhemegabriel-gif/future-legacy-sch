<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\TermResult;
use App\Services\ResultCalculationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ResultController extends Controller
{
    public function __construct(private readonly ResultCalculationService $results)
    {
    }

    public function index(Student $student): View
    {
        // Re-derived from the StudentPolicy (own linked children only) —
        // the same check ChildController::show already uses.
        Gate::authorize('view', $student);

        $termResults = TermResult::where('student_id', $student->id)
            ->where('status', 'published')
            ->with(['term.academicYear'])
            ->orderByDesc('id')
            ->get();

        return view('parent.children.results-index', compact('student', 'termResults'));
    }

    public function show(Student $student, TermResult $termResult): View
    {
        abort_unless($termResult->student_id === $student->id, 404);
        Gate::authorize('view', $termResult);

        $termResult->load(['student', 'schoolClass', 'academicYear', 'term']);
        $breakdown = $this->results->studentTermBreakdown($termResult->student, $termResult->schoolClass, $termResult->term);

        return view('parent.children.results-show', compact('student', 'termResult', 'breakdown'));
    }
}
