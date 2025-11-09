<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use App\Models\Drone;


class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $auth = $request->header('Authorization') ?: '';
        if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
            return response()->json(['message'=>'Token not provided'],401);
        }

        $claims = AuthController::decodeToken($matches[1]);
        if (!$claims) return response()->json(['message'=>'Invalid or expired token'],401);

        // Attach claims and local actor
        $request->attributes->set('jwt_claims', $claims);
        if (($claims['type'] ?? null) === 'drone') {
            $drone = Drone::where('identifier', $claims['sub'])->first();
            if ($drone) $request->attributes->set('actor', $drone);
        } else {
            $user = User::where('name', $claims['sub'])->first();
            if ($user) $request->setUserResolver(fn()=>$user); 
            $request->attributes->set('actor', $user);
        }

        return $next($request);
    
    }
}
