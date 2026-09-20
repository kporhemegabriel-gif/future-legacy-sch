<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGradeBandRequest;
use App\Http\Requests\Admin\UpdateGradeBandRequest;
use App\Models\AcademicYear;
use App\Models\GradeBand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class GradeBandController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', GradeBand::class);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $selectedYear = $request->filled('academic_year_id')
            ? AcademicYear::findOrFail($request->input('academic_year_id'))
            : ($academicYears->firstWhere('is_current', true) ?? $academicYears->first());

        $gradeBands = $selectedYear
            ? GradeBand::where('academic_year_id', $selectedYear->id)->orderByDesc('min_score')->get()
            : collect();

        return view('admin.grade-bands.index', compact('gradeBands', 'academicYears', 'selectedYear'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', GradeBand::class);

        return view('admin.grade-bands.create', [
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
            'preselectedYearId' => $request->integer('academic_year_id') ?: null,
        ]);
    }

    public function store(StoreGradeBandRequest $request): RedirectResponse
    {
        GradeBand::create($request->validated());

        return redirect()
            ->route('admin.grade-bands.index', ['academic_year_id' => $request->input('academic_year_id')])
            ->with('success', 'Grade band created.');
    }

    public function edit(GradeBand $gradeBand): View
    {
        Gate::authorize('update', $gradeBand);

        return view('admin.grade-bands.edit', [
            'gradeBand' => $gradeBand,
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
        ]);
    }

    public function update(UpdateGradeBandRequest $request, GradeBand $gradeBand): RedirectResponse
    {
        $gradeBand->update($request->validated());

        return redirect()
            ->route('admin.grade-bands.index', ['academic_year_id' => $gradeBand->academic_year_id])
            ->with('success', 'Grade band updated.');
    }

    public function destroy(GradeBand $gradeBand): RedirectResponse
    {
        Gate::authorize('delete', $gradeBand);

        $yearId = $gradeBand->academic_year_id;
        $gradeBand->delete();

        return redirect()
            ->route('admin.grade-bands.index', ['academic_year_id' => $yearId])
            ->with('success', 'Grade band removed.');
    }
}
