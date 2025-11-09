<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Drone extends Model
{
    protected $fillable = ['identifier','battery_level', 'status', 'lat', 'lng','handoff_triggered'];
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function assignedOrder()
    {
        return $this->hasOne(Order::class)->whereIn('status', ['reserved', 'picked_up', 'in_transit', 'handoff_pending']);
    }
}
