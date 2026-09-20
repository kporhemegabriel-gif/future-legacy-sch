<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Services\AttendanceSummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceSummaryService $summaries)
    {
    }

    public function index(Request $request): View
    {
        // Derived from the authenticated user's own record — never a
        // route parameter — same shape as every other student-facing
        // controller in this app.
        $student = $request->user()->student()->firstOrFail();

        $termId = $request->input('term_id');
        $term = $termId ? Term::find($termId) : null;

        $summary = $this->summaries->summaryFor($student, $term);
        $history = $this->summaries->historyFor($student, $term);

        return view('student.attendance.index', [
            'summary' => $summary,
            'history' => $history,
            'terms' => Term::with('academicYear')->orderByDesc('id')->get(),
            'selectedTerm' => $term,
        ]);
    }
}
