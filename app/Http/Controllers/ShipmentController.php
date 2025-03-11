<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShipmentCreateRequest;
use App\Http\Requests\ShipmentDeleteRequest;
use App\Http\Requests\ShipmentUpdateRequest;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function store(ShipmentCreateRequest $request): array
    {
        $data = $request->validated();

        $shipment = Shipment::create($data);

        return [
            'shipment' => $shipment
        ];
    }

    public function update(ShipmentUpdateRequest $request, Shipment $shipment): array
    {
        $data = $request->validated();

        $shipment->update($data);

        return [
            'shipment' => $shipment
        ];
    }

    public function destroy(ShipmentDeleteRequest $request)
    {
        $shipment_ids = implode(',', $request->shipment_ids);

        $deleted = Shipment::whereIn('id', $shipment_ids)->delete();

        return [
            'deleted' => $deleted
        ];
    }

    public function show(int $shipment_id)
    {
        $shipment = Shipment::withRelations()->find($shipment_id);

        return [
            'shipment' => $shipment
        ];
    }

    public function index()
    {
        $shipments = Shipment::applyFilters()->get();

        return [
            'shipments' => $shipments
        ];
    }
}
