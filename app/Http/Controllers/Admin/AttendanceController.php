<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceBulkRequest;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Services\AttendanceSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceSummaryService $summaries)
    {
    }

    /** Attendance history/dashboard with filtering — §5/§16 of the Phase 4 spec. */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Attendance::class);

        $attendances = Attendance::query()
            ->with(['student', 'schoolClass', 'term', 'academicYear'])
            ->when($request->filled('academic_year_id'), fn ($q) => $q->where('academic_year_id', $request->input('academic_year_id')))
            ->when($request->filled('term_id'), fn ($q) => $q->where('term_id', $request->input('term_id')))
            ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', $request->input('class_id')))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->input('student_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('attendance_date', $request->input('date')))
            ->orderByDesc('attendance_date')
            ->paginate(25)
            ->withQueryString();

        return view('admin.attendance.index', [
            'attendances' => $attendances,
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
            'terms' => Term::with('academicYear')->orderByDesc('id')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
            'statuses' => Attendance::STATUSES,
        ]);
    }

    /** The class-attendance / mark-attendance page — one roster, one submission. */
    public function mark(Request $request): View
    {
        Gate::authorize('create', Attendance::class);

        $class = $request->filled('class_id') ? SchoolClass::find($request->input('class_id')) : null;
        $term = $request->filled('term_id') ? Term::find($request->input('term_id')) : null;
        $date = $request->input('attendance_date');

        $roster = collect();
        $existing = collect();

        if ($class && $term && $date) {
            $roster = $class->students()->where('status', 'active')->orderBy('last_name')->get();
            $existing = Attendance::where('class_id', $class->id)
                ->whereDate('attendance_date', $date)
                ->get()
                ->keyBy('student_id');
        }

        return view('admin.attendance.mark', [
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
            'terms' => Term::with('academicYear')->orderByDesc('id')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
            'statuses' => Attendance::STATUSES,
            'selectedClass' => $class,
            'selectedTerm' => $term,
            'selectedDate' => $date,
            'roster' => $roster,
            'existing' => $existing,
        ]);
    }

    public function store(StoreAttendanceBulkRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $class = SchoolClass::findOrFail($data['class_id']);
        $recordedBy = $request->user()->id;

        DB::transaction(function () use ($data, $class, $recordedBy) {
            foreach ($data['records'] as $record) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $record['student_id'],
                        'attendance_date' => $data['attendance_date'],
                    ],
                    [
                        'class_id' => $class->id,
                        'academic_year_id' => $class->academic_year_id,
                        'term_id' => $data['term_id'],
                        'status' => $record['status'],
                        'note' => $record['note'] ?? null,
                        'recorded_by' => $recordedBy,
                    ]
                );
            }
        });

        return redirect()
            ->route('admin.attendance.mark', ['class_id' => $class->id, 'term_id' => $data['term_id'], 'attendance_date' => $data['attendance_date']])
            ->with('success', 'Attendance saved for ' . count($data['records']) . ' student(s).');
    }

    /** One student's full attendance history + summary, for an admin reviewing a specific student. */
    public function forStudent(Request $request, Student $student): View
    {
        Gate::authorize('view', $student);

        $termId = $request->input('term_id');
        $term = $termId ? Term::find($termId) : null;

        $summary = $this->summaries->summaryFor($student, $term);
        $history = $this->summaries->historyFor($student, $term);

        return view('admin.attendance.student', [
            'student' => $student,
            'summary' => $summary,
            'history' => $history,
            'terms' => Term::with('academicYear')->orderByDesc('id')->get(),
            'selectedTerm' => $term,
        ]);
    }
}
