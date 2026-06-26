<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RepurposeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'blueprint_id' => ['required', 'integer', 'exists:blueprints,id,user_id,' . auth()->id()],
            'contenu_brut' => ['required', 'string'],
        ];
    }
}
