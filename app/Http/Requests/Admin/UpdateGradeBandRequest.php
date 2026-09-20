<?php

namespace App\Http\Requests\Admin;

use App\Models\GradeBand;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeBandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('grade_band'));
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'min_score' => ['required', 'integer', 'min:0', 'max:100', 'lte:max_score'],
            'max_score' => ['required', 'integer', 'min:0', 'max:100', 'gte:min_score'],
            'grade' => ['required', 'string', 'max:10'],
            'remark' => ['required', 'string', 'max:100'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('status') !== 'active') {
                return;
            }

            $min = (int) $this->input('min_score');
            $max = (int) $this->input('max_score');
            $currentId = $this->route('grade_band')->id;

            $overlaps = GradeBand::where('academic_year_id', $this->input('academic_year_id'))
                ->where('status', 'active')
                ->where('id', '!=', $currentId)
                ->where('min_score', '<=', $max)
                ->where('max_score', '>=', $min)
                ->exists();

            if ($overlaps) {
                $validator->errors()->add('min_score', 'This range overlaps an existing active grade band for this academic year. Adjust the range or deactivate the conflicting one first.');
            }
        });
    }
}
