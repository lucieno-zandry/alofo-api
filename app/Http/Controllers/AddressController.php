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

        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (array_key_exists('is_default', $data)) {
            unset($data['is_default']);
        }

        $address = Address::create($data);

        if ($request->has('is_default') && $request->is_default) {
            $user->address_id = $address->id;
            $user->save();
        }

        return [
            'address' => $address,
            'user' => $user,
        ];
    }

    public function update(AddressUpdateRequest $request, Address $address)
    {
        $data = $request->validated();

        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (array_key_exists('is_default', $data)) {
            unset($data['is_default']);
        }

        $address->update($data);

        if ($request->has('is_default')) {
            if ($request->is_default) {
                $user->address_id = $address->id;
            } else {
                $user->address_id = null;
            }
            $user->save();
        }

        return [
            'address' => $address,
            'user' => $user,
        ];
    }

    public function index()
    {
        $defaultAddressId = auth()->user()->address_id;

        $addresses = Address::applyFilters()
            ->where('user_id', auth()->id())
            ->get()
            ->map(function ($address) use ($defaultAddressId) {
                // Add a dynamic attribute
                $address->is_default = $address->id === $defaultAddressId;
                return $address;
            });

        return [
            'addresses' => $addresses
        ];
    }

    public function destroy(AddressDeleteRequest $request)
    {
        $ids = explode(',', $request->address_ids);
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (in_array($user->address_id, $ids)) {
            $user->address_id = null;
            $user->save();
        }

        $deleted = Address::whereIn('id', $ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }
}
