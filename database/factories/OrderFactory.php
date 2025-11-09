<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\User;
use App\Models\Drone;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'origin_address' => $this->faker->streetAddress,
            'origin_lat' => $this->faker->latitude,
            'origin_lng' => $this->faker->longitude,
            'destination_address' => $this->faker->streetAddress,
            'destination_lat' => $this->faker->latitude,
            'destination_lng' => $this->faker->longitude,
            'status' => 'pending',
            'user_id' => User::factory(),
            'drone_id' => null,
            'handoff_from_drone_id' => null,
        ];
    }

    public function reserved(Drone $drone)
    {
        return $this->state(fn() => ['status' => 'reserved', 'drone_id' => $drone->id]);
    }
    public function pickedUp(Drone $drone)
    {
        return $this->state(fn() => ['status' => 'picked_up', 'drone_id' => $drone->id]);
    }
    public function handoff(Drone $drone)
    {
        return $this->state(fn() => ['status' => 'handoff_pending', 'drone_id' => null, 'handoff_from_drone_id' => $drone->id]);
    }
    public function delivered(Drone $drone)
    {
        return $this->state(fn() => ['status' => 'delivered', 'drone_id' => $drone->id]);
    }
    public function failed(Drone $drone)
    {
        return $this->state(fn() => ['status' => 'failed', 'drone_id' => $drone->id]);
    }
    public function withdrawn(User $user)
    {
        return $this->state(fn() => ['status' => 'withdrawn', 'drone_id' => null, 'user_id' => $user->id]);
    }
}
