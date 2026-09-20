<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAssessmentTypeRequest;
use App\Http\Requests\Admin\UpdateAssessmentTypeRequest;
use App\Models\AssessmentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssessmentTypeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', AssessmentType::class);

        $assessmentTypes = AssessmentType::withCount('assessments')->orderBy('name')->paginate(15);

        return view('admin.assessment-types.index', compact('assessmentTypes'));
    }

    public function create(): View
    {
        Gate::authorize('create', AssessmentType::class);

        return view('admin.assessment-types.create');
    }

    public function store(StoreAssessmentTypeRequest $request): RedirectResponse
    {
        AssessmentType::create($request->validated());

        return redirect()->route('admin.assessment-types.index')->with('success', 'Assessment type created.');
    }

    public function edit(AssessmentType $assessmentType): View
    {
        Gate::authorize('update', $assessmentType);

        return view('admin.assessment-types.edit', compact('assessmentType'));
    }

    public function update(UpdateAssessmentTypeRequest $request, AssessmentType $assessmentType): RedirectResponse
    {
        $assessmentType->update($request->validated());

        return redirect()->route('admin.assessment-types.index')->with('success', 'Assessment type updated.');
    }

    public function destroy(AssessmentType $assessmentType): RedirectResponse
    {
        Gate::authorize('delete', $assessmentType);

        if ($assessmentType->assessments()->exists()) {
            return back()->withErrors(['assessment_type' => 'This type is used by existing assessments and cannot be deleted. Mark it inactive instead.']);
        }

        $assessmentType->delete();

        return redirect()->route('admin.assessment-types.index')->with('success', 'Assessment type removed.');
    }
}
