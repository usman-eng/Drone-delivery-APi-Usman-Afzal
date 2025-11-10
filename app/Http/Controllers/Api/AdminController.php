<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminRequest;
use App\Http\Responses\ApiResponse;
use App\Repositories\DroneRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Drone;
use Illuminate\Support\Facades\DB;
use Throwable;

class AdminController extends Controller
{
    // Bulk get orders
    public function bulkOrders(Request $request)
    {
        $orders = Order::with(['user:id,name', 'drone:id,identifier'])
            ->when($request->has('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->get();
        return ApiResponse::success($orders, 'Orders fetched successfully');
    }

    // Change origin/destination
    public function updateOrderLocation(AdminRequest $request, $id, OrderRepository $orderRepository)
    {

        // DB::beginTransaction();

        try {
            $order = Order::find($id);

            if (!$order) {
                return ApiResponse::error('Order not found', null, 404);
            }

            // Update only the allowed fields
            $order = $orderRepository->updateLocation($order, $request->only([
                'origin_address',
                'origin_lat',
                'origin_lng',
                'destination_address',
                'destination_lat',
                'destination_lng'
            ]));


            // Load related user and drone info
            $order->load(['user:id,name', 'drone:id,identifier']);

            // DB::commit();

            return ApiResponse::success($order, 'Order location updated successfully');

        } catch (Throwable $e) {
            // DB::rollBack();
            return ApiResponse::error('Failed to update order location', [
                'exception' => $e->getMessage()
            ], 500);
        }
    }

    // List drones
    public function listDrones(Request $request)
    {
        $drones = Drone::get();
        return ApiResponse::success($drones, 'All dones get successfully');
    }

    // Mark drone broken/fixed (when marking broken ensure handoff)
    public function markDrone(Request $request, $id, DroneRepository $droneRepository)
    {

        $request->validate([
            'action' => 'required|in:broken,fixed'
        ]);

        // DB::beginTransaction();

        try {
            $drone = Drone::find($id);

            if (!$drone) {
                return ApiResponse::error('Drone not found', null, 404);
            }

            $result = $droneRepository->updateStatus($drone, $request->action);

            // DB::commit();

            return ApiResponse::success($result, 'Drone status updated successfully');

        } catch (Throwable $e) {
            // DB::rollBack();
            return ApiResponse::error('Failed to update drone status', [
                'exception' => $e->getMessage()
            ], 500);
        }
    }

}
