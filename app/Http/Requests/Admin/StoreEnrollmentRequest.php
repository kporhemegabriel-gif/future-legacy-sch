<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Enforces the Basic School enrollment rule: a student may only be
 * enrolled in a subject that is assigned to their *current* class
 * (Class → subjects assigned to the class → students in the class →
 * enrollments). `class_id` and `academic_year_id` are deliberately NOT
 * form inputs here — they're derived server-side from the student's own
 * record below and in the controller, so there is nothing for a manually
 * crafted request to override. See EnrollmentController::store().
 */
class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageEnrollments', $this->route('student'));
    }

    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'subject_id' => [
                'bail',
                'required',
                'exists:subjects,id',
                function ($attribute, $value, $fail) use ($student) {
                    if (! $student->class_id) {
                        $fail('This student must be assigned to a class before they can be enrolled in a subject.');

                        return;
                    }

                    $subjectIsAssignedToClass = $student->schoolClass
                        ?->subjects()
                        ->where('subjects.id', $value)
                        ->exists();

                    if (! $subjectIsAssignedToClass) {
                        $fail('This subject is not assigned to the student\'s current class. Assign it to the class first, or double-check the student\'s class.');
                    }
                },
                // Friendly validation error instead of a raw DB constraint
                // violation when the same student/subject/year combo is
                // submitted twice — the `enrollment_unique` DB index is the
                // backstop, this is the UX layer in front of it. Scoped to
                // the student's current class's academic year, the same
                // year the enrollment will actually be recorded under.
                Rule::unique('enrollments', 'subject_id')->where(function ($query) use ($student) {
                    return $query->where('student_id', $student->id)
                        ->where('academic_year_id', $student->schoolClass?->academic_year_id);
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_id.unique' => 'This student is already enrolled in that subject for their class\'s academic year.',
        ];
    }
}

