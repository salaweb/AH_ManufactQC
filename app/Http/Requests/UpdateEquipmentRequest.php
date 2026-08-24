<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
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
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'order_fabrication_id' => [
                'required', 'integer',
                Rule::exists('order_fabrications', 'id')->where('project_id', $this->input('project_id')),
            ],
            'serie_number' => [
                'required', 'string',
                Rule::unique('equipment')
                    ->where('project_id', $this->input('project_id'))
                    ->ignore($this->route('equipment')),
            ],
            'observations' => ['nullable', 'string'],
        ];
    }
}
