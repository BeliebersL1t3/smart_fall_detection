<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id', 'type', 'status', 'acceleration_peak', 'occurred_at', 'resolved_at', 'dismissed_at', 'notes',
    ];

    protected $casts = [
        'occurred_at'  => 'datetime',
        'resolved_at'  => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}