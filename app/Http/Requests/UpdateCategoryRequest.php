<?php

// app/Http/Requests/UpdateCategoryRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['sometimes','required','string','max:100'],
            'type' => ['sometimes','required', Rule::in(['income','expense'])],
        ];
    }
}
