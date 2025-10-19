<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';
        return [
            'name' => ['required','string','max:80'],
            'type' => ['required','in:income,expense'],
        ];
    }
}
