<?php

namespace App\Http\Requests\Admin;

use App\Models\SchoolClass;
use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('assessment'));
    }

    public function rules(): array
    {
        $assessment = $this->route('assessment');
        $hasScores = $assessment->scores()->exists();

        return [
            'name' => ['required', 'string', 'max:150'],
            'assessment_type_id' => ['required', 'exists:assessment_types,id'],
            'term_id' => [
                'required',
                'exists:terms,id',
                function ($attribute, $value, $fail) use ($assessment, $hasScores) {
                    if ($hasScores && (int) $value !== $assessment->term_id) {
                        $fail('This assessment already has recorded scores — the term cannot be changed.');
                    }
                },
            ],
            'class_id' => [
                'required',
                'exists:school_classes,id',
                function ($attribute, $value, $fail) use ($assessment, $hasScores) {
                    if ($hasScores && (int) $value !== $assessment->class_id) {
                        $fail('This assessment already has recorded scores — the class cannot be changed.');
                    }
                },
            ],
            'subject_id' => [
                'required',
                'exists:subjects,id',
                function ($attribute, $value, $fail) use ($assessment, $hasScores) {
                    if ($hasScores && (int) $value !== $assessment->subject_id) {
                        $fail('This assessment already has recorded scores — the subject cannot be changed.');

                        return;
                    }

                    $class = SchoolClass::find($this->input('class_id'));
                    $term = Term::find($this->input('term_id'));

                    if (! $class || ! $term) {
                        return;
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
            'max_score' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
                function ($attribute, $value, $fail) use ($assessment, $hasScores) {
                    if (! $hasScores) {
                        return;
                    }

                    $highestRecorded = $assessment->scores()->max('score');
                    if ($highestRecorded !== null && $value < $highestRecorded) {
                        $fail("This assessment has a recorded score of {$highestRecorded} — the maximum score cannot be set lower than that.");
                    }
                },
            ],
            'status' => ['required', 'in:active,inactive'],
            'assessment_date' => ['nullable', 'date'],
        ];
    }
}
