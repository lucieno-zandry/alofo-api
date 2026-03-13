<?php

namespace App\Http\Controllers;

use App\Enums\ShipmentStatus;
use App\Helpers\Functions;
use App\Http\Requests\BulkUpdateShipmentRequest;
use App\Http\Requests\ShipmentCreateRequest;
use App\Http\Requests\ShipmentDeleteRequest;
use App\Http\Requests\ShipmentUpdateRequest;
use App\Models\Order;
use App\Models\Shipment;
use App\Notifications\ShipmentStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShipmentController extends Controller
{
    public function store(ShipmentCreateRequest $request): array
    {
        $data = $request->validated();

        $shipment = Shipment::create($data);
        $order_detail_url = Functions::get_frontend_url("CUSTOMER_ORDER_DETAILS_PATHNAME");

        $shipment->order?->user?->notify(new ShipmentStatusUpdated(
            shipment: $shipment,
            order: $shipment->order,
            order_detail_url: $order_detail_url
        ));

        return [
            'shipment' => $shipment
        ];
    }

    public function update(ShipmentUpdateRequest $request, Shipment $shipment): array
    {
        $data = $request->validated();

        $shipment->update($data);
        $order_detail_url = Functions::get_frontend_url("CUSTOMER_ORDER_DETAILS_PATHNAME");

        $shipment->order?->user?->notify(new ShipmentStatusUpdated(
            shipment: $shipment,
            order: $shipment->order,
            order_detail_url: $order_detail_url
        ));

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

    public function bulkUpdateShipment(BulkUpdateShipmentRequest $request)
    {
        $validated = $request->validated();
        $orderUuids = $validated['order_uuids'];
        $newStatus = ShipmentStatus::from($validated['status']); // enum
        $extraData = $validated['data'] ?? []; // optional: carrier, tracking_number, etc.

        $updated = 0;
        $errors = [];

        /** @var Collection */
        $orders = Order::with('user')->whereIn('uuid', $orderUuids)->get();

        DB::beginTransaction();

        try {
            foreach ($orders as $order) {
                $uuid = $order->uuid;

                /** @var ?Shipment */
                $shipment = null;

                if (!$order) {
                    $errors[] = "Order {$uuid} not found.";
                    continue;
                }

                // Get latest shipment
                $latestShipment = $order->shipments()->latest('id')->first();

                // If no shipment exists, create the first one (should be PROCESSING by default)
                if (!$latestShipment) {
                    $shipment = $order->shipments()->create([
                        'status' => $newStatus->value,
                        'data'   => $extraData,
                    ]);

                    $updated++;
                    continue;
                }

                $currentStatus = ShipmentStatus::from($latestShipment->status);

                // Prevent backward transitions
                if ($this->isBackwardTransition($currentStatus, $newStatus)) {
                    $errors[] = "Order {$uuid} cannot go from {$currentStatus->value} to {$newStatus->value}.";
                    continue;
                }

                if ($currentStatus === $newStatus) {
                    $latestShipment->update([
                        'data' => array_merge($latestShipment->data ?? [], $extraData),
                    ]);

                    $shipment = $latestShipment; // still the model
                } else {
                    // Forward transition: create new shipment record
                    $shipment = $order->shipments()->create([
                        'status' => $newStatus->value,
                        'data'   => $extraData,
                    ]);
                }

                $order->user?->notify(new ShipmentStatusUpdated(
                    shipment: $shipment,
                    order: $order,
                    order_detail_url: Functions::get_order_detail_page_url($uuid),
                ));

                $updated++;
            }

            DB::commit();

            $message = "Shipment status updated for {$updated} order(s).";
            if (!empty($errors)) {
                $message .= " " . implode(' ', $errors);
            }

            return response()->json([
                'message' => $message,
                'updated' => $updated,
                'errors'  => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk shipment update failed: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to update shipments. Please try again.',
            ], 500);
        }
    }

    /**
     * Define the allowed progression order.
     */
    private function isBackwardTransition(ShipmentStatus $current, ShipmentStatus $new): bool
    {
        $order = [
            ShipmentStatus::PROCESSING->value => 0,
            ShipmentStatus::SHIPPED->value    => 1,
            ShipmentStatus::DELIVERED->value  => 2,
        ];

        return $order[$new->value] < $order[$current->value];
    }
}
