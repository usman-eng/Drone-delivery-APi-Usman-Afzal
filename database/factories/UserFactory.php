<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'type' => 'enduser',
        ];
    }

    public function admin() { return $this->state(fn() => ['type' => 'admin']); }
    public function drone() { return $this->state(fn() => ['type' => 'drone']); }
}
