<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Repositories\DroneRepository;
use Illuminate\Http\Request;
use App\Models\Drone;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Throwable;

class DroneController extends Controller
{
    //Reserve a order
    public function reserve(Request $request, $orderId, DroneRepository $droneRepository)
    {
        $actor = $request->attributes->get('actor');
        $order = Order::find($orderId);
        if (!($actor instanceof Drone)) {
            return ApiResponse::error('Forbidden, only drones can reserve orders', null, 403);
        }

        // Prevent broken or unavailable drones from reserving
        if (in_array($actor->status, ['broken', 'maintenance'])) {
            return ApiResponse::error('Drone is broken or under maintenance. Cannot reserve jobs.', null, 403);
        }

        if (in_array($actor->status, ['reserved', 'busy'])) {
            return ApiResponse::error('Drone is already handling another job.', null, 400);
        }

        if (!$order) {
            return ApiResponse::error('Order not found', null, 404);
        }

        DB::beginTransaction();

        try {
            $result = $droneRepository->reserveOrder($actor, $order);
            if (isset($result['error'])) {
                return ApiResponse::error($result['error'], null, $result['code']);
            }
            DB::commit();

            return ApiResponse::success($result, 'Order reserved successfully');

        } catch (Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to reserve order', [
                'exception' => $e->getMessage()
            ], 500);
        }
    }

    //grab order
    public function grab(Request $request, $orderId, DroneRepository $repo)
    {
        $actor = $request->attributes->get('actor');
        if (!($actor instanceof Drone)) {
            return ApiResponse::error('Forbidden, only drones can grab orders', null, 403);
        }

        DB::beginTransaction();

        try {
            $order = Order::find($orderId);
            if (!$order) {
                return ApiResponse::error('Order not found', null, 404);
            }
            $result = $repo->grabOrder($actor, $order);
            if (isset($result['error'])) {
                return ApiResponse::error($result['error'], null, $result['code']);
            }
            DB::commit();

            return ApiResponse::success($result, 'Order grabbed successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to grab order', ['exception' => $e->getMessage()], 500);
        }
    }

    //mark delivered order
    public function delivered(Request $request, $orderId, DroneRepository $repo)
    {
        $actor = $request->attributes->get('actor');
        if (!($actor instanceof Drone)) {
            return ApiResponse::error('Forbidden, only drones can mark delivered', null, 403);
        }

        DB::beginTransaction();

        try {
            $order = Order::find($orderId);
            if (!$order) {
                return ApiResponse::error('Order not found', null, 404);
            }
            $result = $repo->markDelivered($actor, $order);
            if (isset($result['error'])) {
                return ApiResponse::error($result['error'], null, $result['code']);
            }
            DB::commit();

            return ApiResponse::success($result, 'Order delivered successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to mark order as delivered', ['exception' => $e->getMessage()], 500);
        }
    }

    //mark failed order
    public function failed(Request $request, $orderId, DroneRepository $repo)
    {
        $actor = $request->attributes->get('actor');
        if (!($actor instanceof Drone)) {
            return ApiResponse::error('Forbidden, only drones can mark orders as failed', null, 403);
        }

        DB::beginTransaction();

        try {
            $order = Order::find($orderId);
            if (!$order) {
                return ApiResponse::error('Order not found', null, 404);
            }
            $result = $repo->markFailed($actor, $order);
            if (isset($result['error'])) {
                return ApiResponse::error($result['error'], null, $result['code']);
            }
            DB::commit();

            return ApiResponse::success($result, 'Order marked as failed successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to mark order as failed', ['exception' => $e->getMessage()], 500);
        }
    }

    //mark broken of drone
    public function markBroken(Request $request, DroneRepository $repo)
    {
        $actor = $request->attributes->get('actor');
        if (!($actor instanceof Drone)) {
            return ApiResponse::error('Forbidden, only drones can mark themselves as broken', null, 403);
        }

        DB::beginTransaction();

        try {
            $result = $repo->markBroken($actor);
            DB::commit();

            return ApiResponse::success($result, 'Drone marked as broken and active orders released successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to mark drone as broken', ['exception' => $e->getMessage()], 500);
        }
    }

    //get heartbeat of drone
    public function heartbeat(Request $request, DroneRepository $repo)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'battery_level' => 'nullable|numeric'
        ]);

        $actor = $request->attributes->get('actor');
        if (!($actor instanceof Drone)) {
            return ApiResponse::error('Forbidden, only drones can send heartbeat', null, 403);
        }

        DB::beginTransaction();

        try {
            $result = $repo->heartbeat($actor, $request->only(['lat', 'lng', 'battery_level']));
            DB::commit();

            return ApiResponse::success($result, 'Heartbeat received successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to process heartbeat', ['exception' => $e->getMessage()], 500);
        }
    }

    //Get current assigned order
    public function assignedOrder(Request $request)
    {
        $actor = $request->attributes->get('actor');
        if (!($actor instanceof Drone))
            return ApiResponse::error('Forbidden', null, 403);

        $order = $actor->assignedOrder()->first();
        if ($order) {
            $order->load(['user:id,name', 'drone:id,identifier,status']);
        }

        return ApiResponse::success($order, 'Assigned Orders get successfully');
    }
}
