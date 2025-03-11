<?php

namespace App\Http\Controllers;

use App\Helpers\Functions;
use App\Http\Requests\ClientCodeCreateRequest;
use App\Http\Requests\ClientCodeDeleteRequest;
use App\Http\Requests\ClientCodeUpdateRequest;
use App\Models\ClientCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClientCodeController extends Controller
{
    public function store(ClientCodeCreateRequest $request)
    {
        $data = $request->validated();

        $client_code = ClientCode::create($data);

        if (!empty($data['user_id'])) {
            // Update user's client_code_id
            User::find($data['user_id'])->update(['client_code_id' => $client_code->id]);
        }

        return [
            'client_code' => $client_code
        ];
    }

    public function update(ClientCodeUpdateRequest $request, ClientCode $client_code)
    {
        $data = $request->validated();

        if (key_exists('user_id', $data)) {
            $user = User::find($data['user_id'] ?? $client_code->user_id);

            if (!empty($data['user_id'])) {
                // Update user's client_code_id
                $user->update(['client_code_id' => $client_code->id]);
            } else {
                $user->update(['client_code_id' => null]);
            }
        }

        $client_code->update($data);

        return [
            'client_code' => $client_code
        ];
    }

    public function destroy(ClientCodeDeleteRequest $request)
    {
        $ids = explode(',', $request->client_code_ids);
        $deleted = ClientCode::whereIn('id', $ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    public function index()
    {
        $client_codes = ClientCode::applyFilters()->get();

        return [
            'client_codes' => $client_codes
        ];
    }
}
