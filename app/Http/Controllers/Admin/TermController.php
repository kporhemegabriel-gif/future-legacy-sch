<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTermRequest;
use App\Http\Requests\Admin\UpdateTermRequest;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TermController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Term::class);

        $terms = Term::with('academicYear')->orderByDesc('academic_year_id')->orderBy('sequence')->paginate(15);

        return view('admin.terms.index', compact('terms'));
    }

    public function create(): View
    {
        Gate::authorize('create', Term::class);

        return view('admin.terms.create', ['academicYears' => AcademicYear::orderByDesc('start_date')->get()]);
    }

    public function store(StoreTermRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_current'] = (bool) ($data['is_current'] ?? false);

        $this->applyCurrentFlag($data);

        return redirect()->route('admin.terms.index')->with('success', 'Term created.');
    }

    public function edit(Term $term): View
    {
        Gate::authorize('update', $term);

        return view('admin.terms.edit', [
            'term' => $term,
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
        ]);
    }

    public function update(UpdateTermRequest $request, Term $term): RedirectResponse
    {
        $data = $request->validated();
        $data['is_current'] = (bool) ($data['is_current'] ?? false);

        $this->applyCurrentFlag($data, $term);

        return redirect()->route('admin.terms.index')->with('success', 'Term updated.');
    }

    public function destroy(Term $term): RedirectResponse
    {
        Gate::authorize('delete', $term);

        // terms is restrictOnDelete from assessments and term_results —
        // a term with any recorded academic activity can't be deleted.
        if ($term->assessments()->exists() || $term->termResults()->exists()) {
            return back()->withErrors(['term' => 'This term has assessments or results recorded against it and cannot be deleted.']);
        }

        $term->delete();

        return redirect()->route('admin.terms.index')->with('success', 'Term removed.');
    }

    /** Only one term can be "current" at a time — same pattern as academic_years.is_current would need if it enforced it, done properly here. */
    private function applyCurrentFlag(array $data, ?Term $term = null): void
    {
        DB::transaction(function () use ($data, $term) {
            if (! empty($data['is_current'])) {
                Term::where('id', '!=', $term?->id ?? 0)->update(['is_current' => false]);
            }

            if ($term) {
                $term->update($data);
            } else {
                Term::create($data);
            }
        });
    }
}
