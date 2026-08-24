<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'email' => [
                Rule::requiredIf(in_array($this->input('role'), ['admin', 'qc'], true)),
                'nullable', 'email', Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'username' => [
                Rule::requiredIf($this->input('role') === 'operari'),
                'nullable', 'string', Rule::unique('users', 'username')->ignore($this->route('user')),
            ],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
