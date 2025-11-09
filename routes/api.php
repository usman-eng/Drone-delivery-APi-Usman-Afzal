<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DroneController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AdminController;

Route::post('/token', [AuthController::class, 'token']);
// drone endpoints
Route::middleware(['jwt.auth', 'role:drone'])->group(function () {
    // Drone-only actions
    Route::post('drones/{order}/reserve', [DroneController::class, 'reserve']);
    Route::post('drones/{order}/grab', [DroneController::class, 'grab']);
    Route::post('drones/{order}/delivered', [DroneController::class, 'delivered']);
    Route::post('drones/{order}/failed', [DroneController::class, 'failed']);
    Route::post('drones/broken', [DroneController::class, 'markBroken']);
    Route::post('drones/heartbeat', [DroneController::class, 'heartbeat']);
    Route::get('drones/assigned-order', [DroneController::class, 'assignedOrder']);

});

// Enduser routes
Route::middleware(['jwt.auth', 'role:enduser'])->group(function () {
    Route::post('orders', [OrderController::class, 'store']);
    Route::post('orders/{order}/withdraw', [OrderController::class, 'withdraw']);
    Route::get('orders', [OrderController::class, 'show']);

});

// Admin routes
Route::middleware(['jwt.auth', 'role:admin'])->group(function () {
    Route::get('admin/orders', [AdminController::class, 'bulkOrders']);
    Route::put('admin/orders/{order}/location', [AdminController::class, 'updateOrderLocation']);
    Route::get('admin/drones', [AdminController::class, 'listDrones']);
    Route::post('admin/drones/{drone}/mark', [AdminController::class, 'markDrone']);
});
