<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); 
            $table->foreignId('drone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('origin_address');
            $table->decimal('origin_lat',10,7)->nullable();
            $table->decimal('origin_lng',10,7)->nullable();
            $table->string('destination_address');
            $table->decimal('destination_lat',10,7)->nullable();
            $table->decimal('destination_lng',10,7)->nullable();
            $table->enum('status',['pending','reserved','picked_up','in_transit','delivered','failed','withdrawn','handoff_pending'])->default('pending');
            $table->foreignId('handoff_from_drone_id')->nullable()->constrained('drones')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
