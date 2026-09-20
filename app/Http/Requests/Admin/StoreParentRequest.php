<?php

namespace App\Http\Requests\Admin;

use App\Models\ParentGuardian;
use Illuminate\Foundation\Http\FormRequest;

class StoreParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ParentGuardian::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],

            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],

            // Multiple children can be linked at creation time — a parent
            // account must support more than one student from day one.
            'links' => ['nullable', 'array'],
            'links.*.student_id' => ['required', 'exists:students,id'],
            'links.*.relationship' => ['nullable', 'string', 'max:50'],
            'links.*.is_primary' => ['nullable', 'boolean'],
        ];
    }
}
