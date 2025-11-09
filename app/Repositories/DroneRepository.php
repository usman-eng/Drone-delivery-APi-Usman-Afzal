<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\Drone;

class DroneRepository
{
    public function updateStatus(Drone $drone, string $action): array
    {
        $affectedOrders = [];

        if ($action === 'broken') {
            // Mark drone as broken
            $drone->update([
                'status' => 'broken',
                'handoff_triggered' => true
            ]);

            // Update all active orders assigned to this drone
            $orders = Order::where('drone_id', $drone->id)
                ->whereIn('status', ['reserved', 'picked_up', 'in_transit'])
                ->get();

            foreach ($orders as $order) {
                $order->update([
                    'status' => 'handoff_pending',
                    'handoff_from_drone_id' => $drone->id,
                    'drone_id' => null
                ]);
            }

            $affectedOrders = $orders;

        } else { // action = fixed
            $drone->update(['status' => 'idle']);
        }

        return [
            'drone' => $drone,
            'affected_orders' => $affectedOrders
        ];
    }

    /**
     * Reserve an order for a drone.
     */
    public function reserveOrder(Drone $drone, Order $order)
    {
        if ($order->handoff_from_drone_id === $drone->id) {
            return ['error' => 'You cannot reclaim a handoff job you dropped.', 'code' => 403];
        }

        if (!in_array($order->status, ['pending', 'handoff_pending'])) {
            return ['error' => 'Order not reservable.', 'code' => 400];
        }

        $order->update([
            'drone_id' => $drone->id,
            'status' => 'reserved',
            'handoff_from_drone_id' => null,
        ]);

        $drone->update(['status' => 'reserved']);

        return $order->load(['user:id,name', 'drone:id,identifier,status']);
    }

    public function grabOrder(Drone $drone, Order $order)
    {
        if ($order->handoff_from_drone_id === $drone->id) {
            return ['error' => 'You cannot pick up a handoff job you dropped.', 'code' => 403];
        }

        if ($order->status == 'picked_up') {
            return ['error' => 'Order already picked up.', 'code' => 400];
        }

        if ($order->drone_id !== $drone->id || $order->status !== 'reserved') {
            return ['error' => 'Order must be reserved by you before grab.', 'code' => 400];
        }

        $order->update(['status' => 'picked_up']);
        $drone->update(['status' => 'busy']);

        return $order->load(['user:id,name', 'drone:id,identifier,status']);
    }

    public function markDelivered(Drone $drone, Order $order)
    {
        if ($order->drone_id !== $drone->id) {
            return ['error' => 'Order is not assigned to you.', 'code' => 403];
        }

        if ($order->status !== 'picked_up') {
            return ['error' => 'Order must be picked up before delivery.', 'code' => 400];
        }

        $order->update(['status' => 'delivered']);
        $drone->update(['status' => 'idle']);

        return $order->load(['user:id,name', 'drone:id,identifier']);
    }

    public function markFailed(Drone $drone, Order $order)
    {
        if ($order->drone_id !== $drone->id) {
            return ['error' => 'Order is not assigned to you.', 'code' => 403];
        }

        if ($order->status !== 'picked_up') {
            return ['error' => 'Order must be picked up before marking as failed.', 'code' => 400];
        }

        $order->update(['status' => 'failed']);
        $drone->update(['status' => 'idle']);

        return $order->load(['user:id,name', 'drone:id,identifier']);
    }


    /**
     * Mark drone as broken and release orders.
     */
    public function markBroken(Drone $drone): array
    {
        $drone->update([
            'status' => 'broken',
            'handoff_triggered' => true,
        ]);

        $orders = Order::where('drone_id', $drone->id)
            ->whereIn('status', ['reserved', 'picked_up', 'in_transit'])
            ->get();

        foreach ($orders as $order) {
            $order->update([
                'status' => 'handoff_pending',
                'handoff_from_drone_id' => $drone->id,
                'drone_id' => null,
            ]);
        }

        $orders->load(['user:id,name']);
        return ['drone' => $drone, 'affected_orders' => $orders];
    }

    /**
     * Update drone heartbeat and get assigned order.
     */
    public function heartbeat(Drone $drone, array $data): array
    {
        $drone->update([
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'battery_level' => $data['battery_level'] ?? $drone->battery_level,
        ]);

        $assigned = $drone->assignedOrder()->with('user:id,name')->first();

        return ['drone' => $drone, 'assigned_order' => $assigned];
    }

}
