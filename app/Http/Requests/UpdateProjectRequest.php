<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'number' => ['required', 'string', Rule::unique('projects', 'number')->ignore($this->route('project'))],
            'family_id' => ['required', 'integer', 'exists:families,id'],
            'observations' => ['nullable', 'string'],
            'section_ids' => ['array'],
            'section_ids.*' => ['integer', 'exists:sections,id'],
            'description_tag_ids' => ['array'],
            'description_tag_ids.*' => ['integer', 'exists:description_tags,id'],
        ];
    }
}
