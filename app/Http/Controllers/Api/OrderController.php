<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Http\Responses\ApiResponse;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Order;
use Throwable;

class OrderController extends Controller
{
    // Submit order
    public function store(OrderRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('actor');
            $order = OrderRepository::createOrder(
                $request->only([
                    'origin_address',
                    'origin_lat',
                    'origin_lng',
                    'destination_address',
                    'destination_lat',
                    'destination_lng',
                ]),
                $user->id
            );
         
            DB::commit();
            $order->refresh();
            return ApiResponse::success($order, 'Order created successfully', 201);

        } catch (Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to create order', [
                'exception' => $e->getMessage(),
            ], 500);
        }
    }

    // Withdraw order (if not picked up yet)
    public function withdraw(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('actor');
            $order = Order::find($id);

            if (!$order) {
                return ApiResponse::error('Order not found', null, 404);
            }

            if ($order->user_id !== $user->id) {
                return ApiResponse::error('Forbidden', null, 403);
            }

            if (!in_array($order->status, ['pending', 'reserved', 'handoff_pending'])) {
                return ApiResponse::error('Cannot withdraw an in-progress order', null, 400);
            }

            // Update order
            $order = OrderRepository::withdrawOrder($order);

            DB::commit();

            return ApiResponse::success($order, 'Order withdrawn successfully');

        } catch (Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to withdraw order', [
                'exception' => $e->getMessage()
            ], 500);
        }
    }

    // Get details + ETA simulation
    public function show(Request $request)
    {
        $user = $request->attributes->get('actor');
        // Fetch orders for the current user with drone info
        $orders = Order::with('drone')
            ->where('user_id', $user->id)
            ->get();

        // Transform orders for API response
        $orders = $orders->map(function ($order) {
            $eta = null;
            if ($order->drone && $order->drone->lat && $order->drone->lng) {
                $dx = $order->destination_lat - $order->drone->lat;
                $dy = $order->destination_lng - $order->drone->lng;
                $distKm = sqrt($dx * $dx + $dy * $dy) * 111; // 1° ≈ 111km
                $eta = round($distKm / 30 * 60); // drone speed ~30km/h
            }

            return [
                'id' => $order->id,
                'origin' => $order->origin_address,
                'destination' => $order->destination_address,
                'status' => $order->status,
                'drone' => $order->drone ? [
                    'id' => $order->drone->id,
                    'name' => $order->drone->identifier,
                    'lat' => $order->drone->lat,
                    'lng' => $order->drone->lng,
                ] : null,
                'eta_minutes' => $eta,
            ];
        });

        return ApiResponse::success($orders, 'Orders fetched successfully');
    }

}
