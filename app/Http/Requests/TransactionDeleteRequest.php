<?php
// app/Http/Requests/TransactionDeleteRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->roleIsAdmin();
    }

    public function rules(): array
    {
        return [
            'transaction_ids'   => ['required', 'array', 'min:1'],
            'transaction_ids.*' => ['required', 'numeric', 'exists:transactions,id'],
            // Optional explanation stored in the audit log
            'reason'            => ['nullable', 'string', 'max:1000'],
        ];
    }
}