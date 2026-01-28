<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientCodeCreateRequest;
use App\Http\Requests\ClientCodeDeleteRequest;
use App\Http\Requests\ClientCodeUpdateRequest;
use App\Models\ClientCode;

class ClientCodeController extends Controller
{
    public function store(ClientCodeCreateRequest $request)
    {
        $data = $request->validated();
        $client_code = ClientCode::create($data);

        return [
            'client_code' => $client_code
        ];
    }

    public function update(ClientCodeUpdateRequest $request, ClientCode $client_code)
    {
        $data = $request->validated();
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

    public function show(string $code)
    {
        $client_code = ClientCode::where('code', $code)->canBeUsed()->first();

        return [
            'client_code' => $client_code
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
