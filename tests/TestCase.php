<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function jwtFor($actor)
    {
        $payload = [
            'iss' => url('/'),
            'iat' => now()->timestamp,
            'exp' => now()->addHour()->timestamp,
            'sub' => $actor->name ?? $actor->identifier,
            'type' => $actor->type ?? 'drone',
            'id' => $actor->id
        ];

        $token = \Firebase\JWT\JWT::encode($payload, env('JWT_SECRET', 'secret'), 'HS256');
        return "Bearer $token";
    }

}
