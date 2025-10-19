<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array {
        return [
            'title'       => ['nullable','string','max:120'],
            'type'        => ['required','in:income,expense'],
            'amount'      => ['required','numeric','min:0.01'],
            'occurred_on' => ['required','date'],
            'category_id' => ['nullable','exists:categories,id'],
            'is_salary'   => ['sometimes','boolean'],
            'note'        => ['nullable','string','max:1000'],
        ];
    }
}
