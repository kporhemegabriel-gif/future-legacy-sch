<?php

namespace App\Http\Requests\Admin;

use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;

/**
 * academic_year_id is deliberately NOT a form field — it's derived from
 * the chosen class (class.academic_year_id) in the controller, the same
 * pattern used for enrollments. The subject-must-belong-to-class rule is
 * the same rule enforced for enrollments, applied here for consistency:
 * an assessment for a subject a class doesn't even teach makes no sense.
 */
class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Assessment::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'assessment_type_id' => ['required', 'exists:assessment_types,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'class_id' => ['required', 'exists:school_classes,id'],
            'subject_id' => [
                'required',
                'exists:subjects,id',
                function ($attribute, $value, $fail) {
                    $class = SchoolClass::find($this->input('class_id'));
                    $term = Term::find($this->input('term_id'));

                    if (! $class || ! $term) {
                        return; // exists: rules on class_id/term_id already report this
                    }

                    if ($term->academic_year_id !== $class->academic_year_id) {
                        $fail('The selected term and class belong to different academic years.');

                        return;
                    }

                    $subjectIsAssignedToClass = $class->subjects()->where('subjects.id', $value)->exists();

                    if (! $subjectIsAssignedToClass) {
                        $fail('This subject is not assigned to the selected class.');
                    }
                },
            ],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'status' => ['required', 'in:active,inactive'],
            'assessment_date' => ['nullable', 'date'],
        ];
    }
}
