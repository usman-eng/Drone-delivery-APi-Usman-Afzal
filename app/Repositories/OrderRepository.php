<?php

namespace App\Repositories;

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
