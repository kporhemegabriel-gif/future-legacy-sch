<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreParentRequest;
use App\Http\Requests\Admin\UpdateParentRequest;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ParentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ParentGuardian::class);

        $parents = ParentGuardian::query()
            ->with(['user', 'students'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = $request->string('search');
                $query->where(function ($q) use ($term) {
                    $q->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                });
            })
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.parents.index', compact('parents'));
    }

    public function create(): View
    {
        Gate::authorize('create', ParentGuardian::class);

        return view('admin.parents.create', [
            'students' => Student::orderBy('last_name')->get(),
        ]);
    }

    public function store(StoreParentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'parent',
                'status' => 'active',
            ]);

            $parentGuardian = ParentGuardian::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            foreach ($data['links'] ?? [] as $link) {
                $parentGuardian->syncStudent(
                    Student::findOrFail($link['student_id']),
                    $link['relationship'] ?? null,
                    (bool) ($link['is_primary'] ?? false)
                );
            }
        });

        return redirect()->route('admin.parents.index')->with('success', 'Parent created.');
    }

    public function show(ParentGuardian $parent): View
    {
        Gate::authorize('view', $parent);

        $parent->load(['user', 'students.schoolClass']);

        return view('admin.parents.show', ['parentGuardian' => $parent]);
    }

    public function edit(ParentGuardian $parent): View
    {
        Gate::authorize('update', $parent);

        $parent->load(['user', 'students']);

        return view('admin.parents.edit', [
            'parentGuardian' => $parent,
            'students' => Student::orderBy('last_name')->get(),
        ]);
    }

    public function update(UpdateParentRequest $request, ParentGuardian $parent): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $parent) {
            $userUpdate = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            if (! empty($data['password'])) {
                $userUpdate['password'] = $data['password'];
            }

            $parent->user()->update($userUpdate);

            $parent->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            // Replace the full link set with what the form submitted —
            // simplest correct behavior for an admin edit screen (as
            // opposed to a partial patch), and every write still goes
            // through syncStudent() so the "one primary guardian" rule
            // holds either way.
            $submittedStudentIds = collect($data['links'] ?? [])->pluck('student_id')->all();
            $idsToDetach = $parent->students()->whereNotIn('students.id', $submittedStudentIds)->pluck('students.id');
            $parent->students()->detach($idsToDetach);

            foreach ($data['links'] ?? [] as $link) {
                $parent->syncStudent(
                    Student::findOrFail($link['student_id']),
                    $link['relationship'] ?? null,
                    (bool) ($link['is_primary'] ?? false)
                );
            }
        });

        return redirect()->route('admin.parents.show', $parent)->with('success', 'Parent updated.');
    }

    public function destroy(ParentGuardian $parent): RedirectResponse
    {
        Gate::authorize('delete', $parent);

        // Cascades: users.id -> parents.user_id (cascadeOnDelete) ->
        // parent_student rows (cascadeOnDelete). Students themselves are
        // untouched — deleting a guardian never deletes a child's record.
        $parent->user()->delete();

        return redirect()->route('admin.parents.index')->with('success', 'Parent removed.');
    }

    public function toggleAccountStatus(ParentGuardian $parent): RedirectResponse
    {
        Gate::authorize('update', $parent);

        $user = $parent->user;
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', "Account {$user->status}.");
    }
}
