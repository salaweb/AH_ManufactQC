<?php

namespace App\Http\Requests;

use App\Enums\AnswerResponse;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnswerRequest extends FormRequest
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
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'response' => ['required', Rule::enum(AnswerResponse::class)],
            'language_chosen' => ['nullable', 'string'],
        ];
    }
}
