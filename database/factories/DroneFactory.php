<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Drone;

class DroneFactory extends Factory
{
    protected $model = Drone::class;

    public function definition()
    {
        return [
            'identifier' => $this->faker->unique()->word,
            'status' => 'idle', // idle, reserved, busy, broken
            'lat' => $this->faker->latitude,
            'lng' => $this->faker->longitude,
            'battery_level' => $this->faker->numberBetween(20, 100),
            'handoff_triggered' => false,
        ];
    }

    public function reserved()
    {
        return $this->state(fn() => ['status' => 'reserved']);
    }
    public function busy()
    {
        return $this->state(fn() => ['status' => 'busy']);
    }
    public function broken()
    {
        return $this->state(fn() => ['status' => 'broken', 'handoff_triggered' => true]);
    }
}
