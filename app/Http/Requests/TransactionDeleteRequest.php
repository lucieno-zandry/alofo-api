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
            'transaction_uuids'   => ['required', 'array', 'min:1'],
            'transaction_uuids.*' => ['required', 'numeric', 'exists:transactions,id'],
            // Optional explanation stored in the audit log
            'reason'            => ['nullable', 'string', 'max:1000'],
        ];
    }
}