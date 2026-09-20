<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Student::class);

        $students = Student::query()
            ->with(['schoolClass', 'academicYear', 'user'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = $request->string('search');
                $query->where(function ($q) use ($term) {
                    $q->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('admission_number', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_id', $request->input('class_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        $classes = SchoolClass::orderBy('name')->get();

        return view('admin.students.index', compact('students', 'classes'));
    }

    public function create(): View
    {
        Gate::authorize('create', Student::class);

        return view('admin.students.create', [
            'classes' => SchoolClass::orderBy('name')->get(),
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // hashed automatically via User's 'hashed' cast
                'role' => 'student', // never taken from the request — see StoreStudentRequest
                'status' => 'active',
            ]);

            $photoPath = $request->hasFile('profile_photo')
                ? $request->file('profile_photo')->store('students', 'public')
                : null;

            Student::create([
                'user_id' => $user->id,
                'admission_number' => $data['admission_number'],
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'class_id' => $data['class_id'] ?? null,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'admission_date' => $data['admission_date'],
                'profile_photo' => $photoPath,
                'status' => 'active',
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Student created.');
    }

    public function show(Student $student): View
    {
        Gate::authorize('view', $student);

        $student->load(['user', 'schoolClass', 'academicYear', 'guardians', 'enrollments.subject', 'enrollments.academicYear']);

        // Only subjects actually assigned to this student's current class
        // are offered for enrollment — the class-subject rule is enforced
        // server-side in StoreEnrollmentRequest regardless, but the picker
        // shouldn't even list subjects that would just get rejected.
        $enrollableSubjects = $student->schoolClass
            ? $student->schoolClass->subjects()->where('status', 'active')->orderBy('name')->get()
            : collect();

        return view('admin.students.show', [
            'student' => $student,
            'enrollableSubjects' => $enrollableSubjects,
        ]);
    }

    public function edit(Student $student): View
    {
        Gate::authorize('update', $student);

        $student->load('user');

        return view('admin.students.edit', [
            'student' => $student,
            'classes' => SchoolClass::orderBy('name')->get(),
            'academicYears' => AcademicYear::orderByDesc('start_date')->get(),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $student) {
            $userUpdate = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            if (! empty($data['password'])) {
                $userUpdate['password'] = $data['password'];
            }

            $student->user()->update($userUpdate);

            $photoPath = $student->profile_photo;
            if ($request->hasFile('profile_photo')) {
                $photoPath = $request->file('profile_photo')->store('students', 'public');
            }

            $student->update([
                'admission_number' => $data['admission_number'],
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'class_id' => $data['class_id'] ?? null,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'admission_date' => $data['admission_date'],
                'profile_photo' => $photoPath,
                'status' => $data['status'],
            ]);
        });

        return redirect()->route('admin.students.show', $student)->with('success', 'Student updated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        // Deletes the linked User too (students.user_id cascadeOnDelete),
        // which in turn removes parent_student links and enrollments for
        // this student (all cascadeOnDelete). Prefer toggleAccountStatus()
        // for routine deactivation — this is for genuine record removal.
        $student->user()->delete();

        return redirect()->route('admin.students.index')->with('success', 'Student removed.');
    }

    /**
     * Flips the linked User's login-access flag (active/inactive). Kept
     * separate from the main update form on purpose — see REVIEW.md's
     * Phase-1 note on why account status must never ride along with a
     * general-purpose edit form.
     */
    public function toggleAccountStatus(Student $student): RedirectResponse
    {
        Gate::authorize('update', $student);

        $user = $student->user;
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', "Account {$user->status}.");
    }
}
