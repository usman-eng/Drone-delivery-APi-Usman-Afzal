<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        static $types = ['drone', 'enduser', 'admin'];

        // Get the next unique type from the array
        $type = array_shift($types);

        // If all types are used, reset the array
        if ($type === null) {
            $types = ['drone', 'enduser', 'admin'];
            $type = array_shift($types);
        }
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'),
            'type' => $type,
        ];
    }

    public function admin()
    {
        return $this->state(fn() => ['type' => 'admin']);
    }
    public function drone()
    {
        return $this->state(fn() => ['type' => 'drone']);
    }
}
