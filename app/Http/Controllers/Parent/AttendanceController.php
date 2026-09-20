<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Term;
use App\Services\AttendanceSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceSummaryService $summaries)
    {
    }

    public function index(Request $request, Student $student): View
    {
        // Re-derived via the StudentPolicy (own linked children only) —
        // the same check ChildController::show and ParentResultController
        // already use. Changing {student} in the URL to another child is
        // rejected here, not just hidden in Blade.
        Gate::authorize('view', $student);

        $termId = $request->input('term_id');
        $term = $termId ? Term::find($termId) : null;

        $summary = $this->summaries->summaryFor($student, $term);
        $history = $this->summaries->historyFor($student, $term);

        return view('parent.children.attendance', [
            'student' => $student,
            'summary' => $summary,
            'history' => $history,
            'terms' => Term::with('academicYear')->orderByDesc('id')->get(),
            'selectedTerm' => $term,
        ]);
    }
}
