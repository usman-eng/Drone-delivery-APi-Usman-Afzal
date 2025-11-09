<?php

namespace App\Repositories;

use App\Models\Drone;
use App\Models\Order;

class OrderRepository
{
    /**
     * Create an order.
     */
    public static function createOrder(array $data, int $userId): Order
    {
        return Order::create(array_merge($data, [
            'user_id' => $userId
        ]));
    }

    /**
     * Withdraw an order.
     */
    public static function withdrawOrder(Order $order): Order
    {
        // If drone was reserved for this order, free it up
        if ($order->drone_id) {
            $drone = Drone::find($order->drone_id);

            if ($drone) {
                $drone->update(['status' => 'idle']);
            }

        }
        $order->update([
            'status' => 'withdrawn',
            'drone_id' => null
        ]);
        return $order;
    }

    /**
     * update location an order.
     */
    public static function updateLocation(Order $order, array $data): Order
    {
        $order->update($data);
        return $order->load(['user:id,name', 'drone:id,identifier']);
    }
}
