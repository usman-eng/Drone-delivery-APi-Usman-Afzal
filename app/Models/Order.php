<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'drone_id',
        'origin_address',
        'origin_lat',
        'origin_lng',
        'destination_address',
        'destination_lat',
        'destination_lng',
        'handoff_from_drone_id',
        'status'
    ];
    public function drone()
    {
        return $this->belongsTo(Drone::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
