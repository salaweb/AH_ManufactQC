<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDefectRequest extends FormRequest
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
            'tipo' => ['required', 'string'],
            'observation' => ['nullable', 'string'],
            'responsibility' => ['nullable', 'string'],
            'responsible_user_id' => [
                Rule::requiredIf($this->input('responsibility') === 'produccio'),
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('role', UserRole::OperariProduccio->value),
            ],
            'actions' => ['nullable', 'string'],
        ];
    }
}
