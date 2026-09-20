<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssessmentRequest;
use App\Http\Requests\Admin\UpdateAssessmentRequest;
use App\Models\Assessment;
use App\Models\AssessmentType;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Assessment::class);

        $assessments = Assessment::query()
            ->with(['assessmentType', 'term', 'schoolClass', 'subject'])
            ->when($request->filled('term_id'), fn ($q) => $q->where('term_id', $request->input('term_id')))
            ->when($request->filled('class_id'), fn ($q) => $q->where('class_id', $request->input('class_id')))
            ->withCount('scores')
            ->orderByDesc('assessment_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.assessments.index', [
            'assessments' => $assessments,
            'terms' => Term::orderByDesc('id')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Assessment::class);

        return view('admin.assessments.create', $this->formOptions());
    }

    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['academic_year_id'] = SchoolClass::findOrFail($data['class_id'])->academic_year_id;

        $assessment = Assessment::create($data);

        return redirect()->route('admin.assessments.show', $assessment)->with('success', 'Assessment created.');
    }

    public function show(Assessment $assessment): View
    {
        Gate::authorize('view', $assessment);

        $assessment->load(['assessmentType', 'academicYear', 'term', 'schoolClass', 'subject']);

        return view('admin.assessments.show', compact('assessment'));
    }

    public function edit(Assessment $assessment): View
    {
        Gate::authorize('update', $assessment);

        return view('admin.assessments.edit', array_merge(['assessment' => $assessment], $this->formOptions()));
    }

    public function update(UpdateAssessmentRequest $request, Assessment $assessment): RedirectResponse
    {
        $data = $request->validated();
        $data['academic_year_id'] = SchoolClass::findOrFail($data['class_id'])->academic_year_id;

        $assessment->update($data);

        return redirect()->route('admin.assessments.show', $assessment)->with('success', 'Assessment updated.');
    }

    public function destroy(Assessment $assessment): RedirectResponse
    {
        Gate::authorize('delete', $assessment);

        // "delete assessments where safe" — never delete one that already
        // has recorded scores; those are real academic records.
        if ($assessment->scores()->exists()) {
            return back()->withErrors(['assessment' => 'This assessment has recorded scores and cannot be deleted. Mark it inactive instead.']);
        }

        $assessment->delete();

        return redirect()->route('admin.assessments.index')->with('success', 'Assessment removed.');
    }

    private function formOptions(): array
    {
        $classes = SchoolClass::with('subjects:id')->orderBy('name')->get();

        return [
            'assessmentTypes' => AssessmentType::where('status', 'active')->orderBy('name')->get(),
            'terms' => Term::with('academicYear')->orderByDesc('id')->get(),
            'classes' => $classes,
            'subjects' => Subject::where('status', 'active')->orderBy('name')->get(),
            // For client-side filtering only — the real enforcement is
            // server-side in Store/UpdateAssessmentRequest regardless.
            'classSubjectMap' => $classes->mapWithKeys(fn ($class) => [$class->id => $class->subjects->pluck('id')]),
            'termYearMap' => Term::pluck('academic_year_id', 'id'),
        ];
    }
}
