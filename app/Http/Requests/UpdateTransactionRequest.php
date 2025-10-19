<?php 
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
          'title'       => ['sometimes','nullable','string','max:150'],
            'note'        => ['sometimes','nullable','string','max:255'],
            'amount'      => ['sometimes','nullable','numeric','min:0'],
            'type'        => ['sometimes','nullable', Rule::in(['income','expense'])],
            'category_id' => ['sometimes','nullable','exists:categories,id'],
            'occurred_on' => ['sometimes','nullable','date_format:Y-m-d'],
            'is_salary'   => ['sometimes','nullable','boolean'],
        ];
    }
}
