<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $claims = $request->attributes->get('jwt_claims', []);
        $actor = $request->attributes->get('actor');

        if (!isset($claims['type']) || $claims['type'] !== $role || !$actor) {
            return ApiResponse::error("Unauthorized, only {$role}s allowed", null, 403);
        }

        return $next($request);
    }
}
