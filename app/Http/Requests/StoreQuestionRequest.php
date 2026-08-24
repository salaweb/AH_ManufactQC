<?php

namespace App\Http\Requests;

use App\Enums\QuestionCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
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
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'text' => ['required', 'string'],
            'category' => ['required', Rule::enum(QuestionCategory::class)],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['boolean'],
        ];
    }
}
