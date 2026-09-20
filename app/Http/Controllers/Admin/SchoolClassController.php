<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignClassSubjectsRequest;
use App\Http\Requests\Admin\StoreSchoolClassRequest;
use App\Http\Requests\Admin\UpdateSchoolClassRequest;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchoolClassController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SchoolClass::class);

        $classes = SchoolClass::with('academicYear')
            ->withCount('students')
            ->orderByDesc('academic_year_id')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.classes.index', compact('classes'));
    }

    public function create(): View
    {
        Gate::authorize('create', SchoolClass::class);

        return view('admin.classes.create', ['academicYears' => AcademicYear::orderByDesc('start_date')->get()]);
    }

    public function store(StoreSchoolClassRequest $request): RedirectResponse
    {
        $class = SchoolClass::create($request->validated());

        return redirect()->route('admin.classes.show', $class)->with('success', 'Class created.');
    }

    public function show(SchoolClass $class): View
    {
        Gate::authorize('view', $class);

        $class->load(['academicYear', 'students', 'subjects']);

        return view('admin.classes.show', compact('class'));
    }

    public function edit(SchoolClass $class): View
    {
        Gate::authorize('update', $class);

        return view('admin.classes.edit', [
            'class' => $class,
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
        ]);
    }

    public function update(UpdateSchoolClassRequest $request, SchoolClass $class): RedirectResponse
    {
        $class->update($request->validated());

        return redirect()->route('admin.classes.show', $class)->with('success', 'Class updated.');
    }

    public function destroy(SchoolClass $class): RedirectResponse
    {
        Gate::authorize('delete', $class);

        // Phase 1's students.class_id FK is nullOnDelete, not restrictOnDelete
        // — deleting a class would otherwise silently orphan (null out) every
        // assigned student rather than fail. Guard against that explicitly
        // here instead of relying on a DB constraint we don't control.
        if ($class->students()->exists()) {
            return back()->withErrors(['class' => 'This class still has students assigned to it. Reassign or remove them first.']);
        }

        try {
            $class->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            // enrollments.class_id (Phase 2) IS restrictOnDelete — this
            // catches a class with enrollment history but no currently
            // assigned students.
            return back()->withErrors(['class' => 'This class has enrollment history attached and cannot be deleted. Set it to inactive instead.']);
        }

        return redirect()->route('admin.classes.index')->with('success', 'Class removed.');
    }

    public function editSubjects(SchoolClass $class): View
    {
        Gate::authorize('manageSubjects', $class);

        $class->load('subjects');

        return view('admin.classes.subjects', [
            'class' => $class,
            'subjects' => Subject::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function updateSubjects(AssignClassSubjectsRequest $request, SchoolClass $class): RedirectResponse
    {
        $sync = collect($request->validated()['subject_ids'] ?? [])
            ->mapWithKeys(fn ($subjectId) => [$subjectId => ['academic_year_id' => $class->academic_year_id]])
            ->all();

        $class->subjects()->sync($sync);

        return redirect()->route('admin.classes.show', $class)->with('success', 'Subjects updated for this class.');
    }
}
