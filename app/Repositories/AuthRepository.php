<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Drone;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

class AuthRepository
{
    public function generateToken(string $name, string $type): array
    {
        $now = Carbon::now()->timestamp;
        $ttl = intval(config('jwt.ttl_minutes', 360)) * 60;

        // Ensure entities exist
        if (in_array($type, ['admin', 'enduser'])) {
            $user = User::firstOrCreate(
                ['name' => $name],
                ['email' => "{$name}@local", 'password' => bcrypt(Str::random(16))]
            );
            $user->assignRole($type);
        } else {
            Drone::firstOrCreate(['identifier' => $name]);
        }

        $payload = [
            'iss' => config('app.url', 'drone-api'),
            'iat' => $now,
            'exp' => $now + $ttl,
            'sub' => $name,
            'type' => $type,
        ];

        $token = JWT::encode($payload, env('JWT_SECRET'), 'HS256');

        return [
            'token' => $token,
            'type' => 'bearer',
            'expires_at' => Carbon::createFromTimestamp($now + $ttl)->toIso8601String(),
        ];
    }
}
