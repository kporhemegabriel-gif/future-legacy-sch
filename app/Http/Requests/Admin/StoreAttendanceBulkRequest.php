<?php

namespace App\Http\Requests\Admin;

use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates one class's worth of attendance in a single submission.
 * class_id and term_id are real inputs (the admin genuinely picks them,
 * same as Assessment creation in Phase 3) but academic_year_id is NOT —
 * it's derived server-side from the chosen class in the controller, so
 * there's nothing for a manually crafted request to override there.
 * Every student row is re-checked against the class's actual current
 * roster; a forged student_id for a student in a different class is
 * rejected outright.
 */
class StoreAttendanceBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Attendance::class);
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'exists:school_classes,id'],
            'term_id' => [
                'required',
                'exists:terms,id',
                function ($attribute, $value, $fail) {
                    $class = SchoolClass::find($this->input('class_id'));
                    $term = Term::find($value);

                    if ($class && $term && $term->academic_year_id !== $class->academic_year_id) {
                        $fail('The selected term and class belong to different academic years.');
                    }
                },
            ],
            'attendance_date' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $class = SchoolClass::find($this->input('class_id'));
                    $year = $class?->academicYear;

                    if (! $year) {
                        return;
                    }

                    if ($value < $year->start_date->format('Y-m-d') || $value > $year->end_date->format('Y-m-d')) {
                        $fail("This date falls outside {$year->name}, the selected class's academic year.");
                    }
                },
            ],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'distinct', 'exists:students,id'],
            'records.*.status' => ['required', 'in:' . implode(',', Attendance::STATUSES)],
            'records.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $classId = (int) $this->input('class_id');
            $records = $this->input('records', []);

            foreach ($records as $index => $record) {
                $studentId = $record['student_id'] ?? null;
                if (! $studentId) {
                    continue; // already reported by the student_id rule above
                }

                // Re-derived from the database, never trusted from the
                // request — a student's CURRENT class must match the
                // class this submission is for.
                $currentClassId = Student::where('id', $studentId)->value('class_id');

                if ($currentClassId !== $classId) {
                    $validator->errors()->add(
                        "records.{$index}.student_id",
                        'This student does not belong to the selected class.'
                    );
                }
            }
        });
    }
}
