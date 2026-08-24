<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDefectRequest extends FormRequest
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
            'equipment_id' => ['required', 'integer', 'exists:equipment,id'],
            'answer_id' => ['nullable', 'integer', 'exists:answers,id'],
            'tipo' => ['required', 'string'],
            'observation' => ['nullable', 'string'],
            'responsibility' => ['nullable', 'string'],
            'actions' => ['nullable', 'string'],
        ];
    }
}
