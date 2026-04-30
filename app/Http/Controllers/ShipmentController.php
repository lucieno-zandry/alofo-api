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


class ShipmentController extends Controller
{
    public function destroy(ShipmentDeleteRequest $request)
    {
        $shipment_ids = explode(',', $request->shipment_ids);

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

    public function index(Request $request)
    {
        // Base query with default scopes
        $query = Shipment::withRelations()->customerFilterable();

        // Apply filters
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Search across multiple fields
        if ($request->filled('search')) {
            $lowerSearchTerm = '%' . mb_strtolower($request->input('search')) . '%';

            $query->where(function ($q) use ($lowerSearchTerm) {
                $q->whereRaw('LOWER(data->>"$.tracking_number") LIKE ?', [$lowerSearchTerm])
                    ->orWhereHas('order', function ($orderQuery) use ($lowerSearchTerm) {
                        $orderQuery->whereRaw('LOWER(uuid) LIKE ?', [$lowerSearchTerm]);
                    })
                    ->orWhereRaw('LOWER(data->>"$.carrier") LIKE ?', [$lowerSearchTerm]);
            });
        }

        // Date range filters (assuming created_at is the date field; adjust if needed)
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'id'); // default to 'id'
        $sortOrder = $request->input('sort_order', 'desc'); // default to 'desc' (newest first)
        // Ensure the sort column is valid to avoid SQL injection
        $allowedSortColumns = ['id', 'status', 'created_at', 'updated_at']; // add more as needed
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            // Fallback to default sorting
            $query->orderBy('id', 'desc');
        }

        // Pagination
        $perPage = $request->input('per_page', 15); // default 15 per page
        $shipments = $query->paginate($perPage);

        // Return as JSON (Laravel's paginator already includes meta in the response structure)
        return response()->json($shipments);
    }

    public function bulkUpdateShipment(BulkUpdateShipmentRequest $request)
    {
        $validated = $request->validated();
        $orderUuids = $validated['order_uuids'];
        $newStatus = ShipmentStatus::from($validated['status']);
        $extraData = $validated['data'] ?? [];

        $updated = 0;
        $errors = [];

        $orders = Order::with('user')->whereIn('uuid', $orderUuids)->get();

        DB::beginTransaction();

        try {
            foreach ($orders as $order) {
                $uuid = $order->uuid;

                // Get the latest shipment (by id) – we'll use this for comparison
                $latestShipment = $order->shipments()->latest('id')->first();

                // If no shipment exists, create the first one as active
                if (!$latestShipment) {
                    $shipment = $order->shipments()->create([
                        'status'    => $newStatus->value,
                        'data'      => $extraData,
                        'is_active' => true, // first and only
                    ]);

                    $updated++;
                    $this->notifyUser($order, $shipment);
                    continue;
                }

                $currentStatus = ShipmentStatus::from($latestShipment->status);

                // Prevent backward transitions
                if ($this->isBackwardTransition($currentStatus, $newStatus)) {
                    $errors[] = "Order {$uuid} cannot go from {$currentStatus->value} to {$newStatus->value}.";
                    continue;
                }

                if ($currentStatus === $newStatus) {
                    // Same status: just update the data of the latest shipment
                    $latestShipment->update([
                        'data' => array_merge($latestShipment->data ?? [], $extraData),
                    ]);

                    $shipment = $latestShipment;
                } else {
                    // Status changed: create a new shipment as the active one
                    // First, deactivate all existing shipments for this order
                    $order->shipments()->update(['is_active' => false]);

                    // Create the new active shipment
                    $shipment = $order->shipments()->create([
                        'status'    => $newStatus->value,
                        'data'      => $extraData,
                        'is_active' => true,
                    ]);
                }

                $this->notifyUser($order, $shipment);
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

    // Helper to send notification (extracted for clarity)
    private function notifyUser(Order $order, Shipment $shipment): void
    {
        $order->user?->notify(new ShipmentStatusUpdated(
            shipment: $shipment,
            order: $order,
            order_detail_url: Functions::get_order_detail_page_url($order->uuid),
        ));
    }

    /**
     * Define the allowed progression order.
     */
    private function isBackwardTransition(ShipmentStatus $current, ShipmentStatus $new): bool
    {
        $order = [
            ShipmentStatus::PENDING->value => 0,
            ShipmentStatus::PROCESSING->value => 1,
            ShipmentStatus::SHIPPED->value    => 2,
            ShipmentStatus::DELIVERED->value  => 3,
        ];

        return $order[$new->value] < $order[$current->value];
    }
}
