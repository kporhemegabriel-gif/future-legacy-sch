<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignClassSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageSubjects', $this->route('class'));
    }

    public function rules(): array
    {
        return [
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['exists:subjects,id'],
        ];
    }
}
