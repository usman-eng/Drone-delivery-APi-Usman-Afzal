<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Drone;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DroneDeliveryFullTest extends TestCase
{
    use RefreshDatabase;

    // -------------------
    // Drone Tests
    // -------------------

    public function testDroneCanReserveGrabAndDeliverOrder()
    {
        $drone = Drone::factory()->create();
        $order = Order::factory()->create();

        // Reserve
        $res = $this->withHeader('Authorization', $this->jwtFor($drone))
            ->postJson("/api/drones/{$order->id}/reserve");
        $res->assertStatus(200)->assertJsonFragment(['status' => 'reserved']);

        // Grab
        $res = $this->withHeader('Authorization', $this->jwtFor($drone))
            ->postJson("/api/drones/{$order->id}/grab");
        $res->assertStatus(200)->assertJsonFragment(['status' => 'picked_up']);

        // Deliver
        $res = $this->withHeader('Authorization', $this->jwtFor($drone))
            ->postJson("/api/drones/{$order->id}/delivered");
        $res->assertStatus(200)->assertJsonFragment(['status' => 'delivered']);
    }

    public function testDroneCannotReserveIfBrokenOrBusy()
    {
        $broken = Drone::factory()->broken()->create();
        $busy = Drone::factory()->busy()->create();
        $order = Order::factory()->create();

        $res = $this->withHeader('Authorization', $this->jwtFor($broken))
            ->postJson("/api/drones/{$order->id}/reserve");
        $res->assertStatus(403);

        $res = $this->withHeader('Authorization', $this->jwtFor($busy))
            ->postJson("/api/drones/{$order->id}/reserve");
        $res->assertStatus(400);
    }

    public function testDroneMarkBrokenTriggersHandoff()
    {
        $drone = Drone::factory()->create();
        $order = Order::factory()->reserved($drone)->create();

        $res = $this->withHeader('Authorization', $this->jwtFor($drone))
            ->postJson("/api/drones/broken");

        $res->assertStatus(200)->assertJsonFragment(['status' => 'broken']);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'handoff_pending',
            'drone_id' => null,
            'handoff_from_drone_id' => $drone->id
        ]);
    }

    public function testDroneHeartbeatReturnsAssignedOrder()
    {
        $drone = Drone::factory()->create();
        $order = Order::factory()->reserved($drone)->create();

        $res = $this->withHeader('Authorization', $this->jwtFor($drone))
            ->postJson("/api/drones/heartbeat", [
                'lat' => 10.5,
                'lng' => 20.5,
                'battery_level' => 90
            ]);

        // Check status and structure
        $res->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Heartbeat received successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'drone' => [
                        'id',
                        'identifier',
                        'battery_level',
                        'status',
                        'lat',
                        'lng'
                    ],
                    'assigned_order' => [
                        'id',
                        'status',
                        'user' => ['id', 'name']
                    ]
                ]
            ]);

        //Check specific values
        $res->assertJsonPath('data.drone.id', $drone->id);
        $res->assertJsonPath('data.assigned_order.id', $order->id);
    }

    public function testDroneAssignedOrderEndpoint()
    {
        $drone = Drone::factory()->create();
        $order = Order::factory()->reserved($drone)->create();

        $res = $this->withHeader('Authorization', $this->jwtFor($drone))
            ->getJson("/api/drones/assigned-order");

        $res->assertStatus(200)->assertJsonFragment(['status' => 'reserved']);
    }

    // -------------------
    // Enduser Tests
    // -------------------
    public function testEnduserCanSubmitWithdrawAndViewOrders()
    {
        $user = User::factory()->create(['type' => 'enduser']);

        $payload = [
            "origin_address" => "Warehouse A, Industrial Area, Riyadh",
            "origin_lat" => 24.7136,
            "origin_lng" => 46.6753,
            "destination_address" => "Customer House, Al Olaya, Riyadh",
            "destination_lat" => 24.7060,
            "destination_lng" => 46.6840
        ];

        // Submit a new order
        $res = $this->withHeader('Authorization', $this->jwtFor($user))
            ->postJson("/api/orders", $payload);

        $res->assertStatus(201)
            ->assertJsonPath('data.origin_address', $payload['origin_address'])
            ->assertJsonPath('data.status', 'pending');

        $orderId = $res->json('data.id');

        // Withdraw the order (before pickup)
        $res = $this->withHeader('Authorization', $this->jwtFor($user))
            ->postJson("/api/orders/{$orderId}/withdraw");

        $res->assertStatus(200)
            ->assertJsonPath('data.status', 'withdrawn');

        // Fetch user orders
        $res = $this->withHeader('Authorization', $this->jwtFor($user))
            ->getJson("/api/orders");

        $res->assertStatus(200)
            ->assertJsonFragment(['id' => $orderId, 'status' => 'withdrawn']);
    }


    public function testEnduserCannotWithdrawPickedUpOrder()
    {
        $user = User::factory()->create(['type' => 'enduser']);
        $drone = Drone::factory()->create();
        $order = Order::factory()->pickedUp($drone)->create(['user_id' => $user->id]);

        $res = $this->withHeader('Authorization', $this->jwtFor($user))
            ->postJson("/api/orders/{$order->id}/withdraw");

        $res->assertStatus(400)
            ->assertJsonFragment(['message' => 'Cannot withdraw an in-progress order']);
    }
}
