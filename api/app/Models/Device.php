<?php

namespace App\Models;

use App\Support\IotUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'device_token', 'api_base_url', 'label', 'fall_threshold',
        'orientation_threshold', 'immobility_duration', 'is_online', 'battery_level',
        'last_magnitude', 'last_ax', 'last_ay', 'last_az', 'last_status', 'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function resolvedApiBaseUrl(): string
    {
        if ($this->api_base_url) {
            return IotUrl::normalizeApiBase($this->api_base_url);
        }

        $fromEnv = config('iot.api_base');

        return $fromEnv
            ? IotUrl::normalizeApiBase($fromEnv)
            : IotUrl::normalizeApiBase(url('/api'));
    }

    public function arduinoApiRootUrl(): string
    {
        return IotUrl::arduinoRootFromApiBase($this->resolvedApiBaseUrl());
    }

    public function isRecentlyOnline(int $seconds = 30): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->gt(now()->subSeconds($seconds));
    }

    public function syncOnlineStatus(int $seconds = 30): void
    {
        $online = $this->isRecentlyOnline($seconds);

        if ($this->is_online !== $online) {
            $this->update(['is_online' => $online]);
        }
    }

    public function displayLocation(): string
    {
        return $this->label ?: 'Perangkat ESP32';
    }
}