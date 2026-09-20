<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\TermResult;
use App\Services\ResultCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TermResultController extends Controller
{
    public function __construct(private readonly ResultCalculationService $results)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', TermResult::class);

        $classes = SchoolClass::orderBy('name')->get();
        $terms = Term::with('academicYear')->orderByDesc('id')->get();

        $termResults = collect();
        $selectedClass = null;
        $selectedTerm = null;

        if ($request->filled('class_id') && $request->filled('term_id')) {
            $selectedClass = SchoolClass::findOrFail($request->input('class_id'));
            $selectedTerm = Term::findOrFail($request->input('term_id'));

            $termResults = TermResult::where('class_id', $selectedClass->id)
                ->where('term_id', $selectedTerm->id)
                ->with('student')
                ->orderBy('position')
                ->get();
        }

        return view('admin.term-results.index', compact('classes', 'terms', 'termResults', 'selectedClass', 'selectedTerm'));
    }

    public function compute(Request $request): RedirectResponse
    {
        Gate::authorize('compute', TermResult::class);

        $request->validate([
            'class_id' => ['required', 'exists:school_classes,id'],
            'term_id' => ['required', 'exists:terms,id'],
        ]);

        $class = SchoolClass::findOrFail($request->input('class_id'));
        $term = Term::findOrFail($request->input('term_id'));

        $computed = $this->results->computeForClassTerm($class, $term);

        return redirect()
            ->route('admin.term-results.index', ['class_id' => $class->id, 'term_id' => $term->id])
            ->with('success', "Computed results for {$computed->count()} student(s).");
    }

    public function show(TermResult $termResult): View
    {
        Gate::authorize('view', $termResult);

        $termResult->load(['student', 'schoolClass', 'academicYear', 'term']);
        $breakdown = $this->results->studentTermBreakdown($termResult->student, $termResult->schoolClass, $termResult->term);

        return view('admin.term-results.show', compact('termResult', 'breakdown'));
    }

    public function publish(TermResult $termResult): RedirectResponse
    {
        Gate::authorize('publish', $termResult);

        $termResult->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by' => request()->user()->id,
        ]);

        return back()->with('success', 'Result published.');
    }

    public function unpublish(TermResult $termResult): RedirectResponse
    {
        Gate::authorize('unpublish', $termResult);

        $termResult->update(['status' => 'draft', 'published_at' => null, 'published_by' => null]);

        return back()->with('success', 'Result unpublished.');
    }
}
