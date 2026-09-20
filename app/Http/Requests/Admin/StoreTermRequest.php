<?php

namespace App\Http\Requests\Admin;

use App\Models\Term;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Term::class);
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('terms', 'name')->where(fn ($q) => $q->where('academic_year_id', $this->input('academic_year_id'))),
            ],
            'sequence' => ['required', 'integer', 'min:1', 'max:3'],
            'is_current' => ['nullable', 'boolean'],
        ];
    }
}
