<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlueprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'tone' => ['sometimes', 'string', 'max:255'],
            'max_hashtags' => ['sometimes', 'integer', 'min:0', 'max:10'],
            'max_characters' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'regles_supplementaires' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
