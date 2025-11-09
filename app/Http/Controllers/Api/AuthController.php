<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Http\Responses\ApiResponse;
use App\Repositories\AuthRepository;
use Throwable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{

    //function to generate token according to role
    public function token(AuthRequest $request)
    {
        DB::beginTransaction();
        try {
            $authRepository = new AuthRepository();
            $data = $authRepository->generateToken(
                $request->input('name'),
                $request->input('type')
            );
            DB::commit();
            return ApiResponse::success($data, 'Token generated successfully.');
        } catch (Throwable $e) {
            DB::rollBack();
            return ApiResponse::error('Failed to generate token', [
                'exception' => $e->getMessage(),
            ], 500);
        }
    }

    //helper to decode token
    public static function decodeToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
}
