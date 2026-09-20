<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEnrollmentRequest;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class EnrollmentController extends Controller
{
    public function store(StoreEnrollmentRequest $request, Student $student): RedirectResponse
    {
        // class_id and academic_year_id are never taken from the request —
        // they're derived from the student's own current class, which is
        // exactly what StoreEnrollmentRequest validated subject_id against.
        // This is what makes the class-subject rule unbypassable by a
        // manually crafted request: there's no class_id field for an
        // attacker to substitute in the first place.
        $student->enrollments()->create([
            'subject_id' => $request->validated()['subject_id'],
            'class_id' => $student->class_id,
            'academic_year_id' => $student->schoolClass->academic_year_id,
        ]);

        return redirect()->route('admin.students.show', $student)->with('success', 'Student enrolled in subject.');
    }

    public function destroy(Student $student, Enrollment $enrollment): RedirectResponse
    {
        Gate::authorize('delete', $enrollment);

        abort_unless($enrollment->student_id === $student->id, 404);

        // Phase 3's scores.enrollment_id is restrictOnDelete — a student
        // with recorded scores against this enrollment can't be silently
        // un-enrolled, since that would orphan real academic records.
        if ($enrollment->scores()->exists()) {
            return back()->withErrors(['enrollment' => 'This enrollment has recorded scores and cannot be removed.']);
        }

        $enrollment->delete();

        return redirect()->route('admin.students.show', $student)->with('success', 'Enrollment removed.');
    }
}
