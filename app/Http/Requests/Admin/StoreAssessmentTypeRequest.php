<?php

namespace App\Http\Requests\Admin;

use App\Models\AssessmentType;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AssessmentType::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:assessment_types,name'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
