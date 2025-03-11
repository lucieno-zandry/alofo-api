<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddressCreateRequest;
use App\Http\Requests\AddressDeleteRequest;
use App\Http\Requests\AddressUpdateRequest;
use App\Models\Address;

class AddressController extends Controller
{
    public function store(AddressCreateRequest $request): array
    {
        $data = $request->validated();
        $address = Address::create($data);

        return [
            'address' => $address
        ];
    }

    public function update(AddressUpdateRequest $request, Address $address)
    {
        $data = $request->validated();

        $address->update($data);

        return [
            'address' => $address
        ];
    }

    public function index()
    {
        $addresses = Address::applyFilters()
            ->where('user_id', auth()->id())
            ->get();

        return [
            'addresses' => $addresses
        ];
    }

    public function destroy(AddressDeleteRequest $request)
    {
        $ids = explode(',', $request->address_ids);
        $deleted = Address::whereIn('id', $ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }
}
