<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Device-Token')
            ?? $request->header('Authorization')
            ?? $request->input('device_token');

        if (is_string($token) && str_starts_with($token, 'Bearer ')) {
            $token = trim(substr($token, 7));
        }

        if (! $token) {
            return response()->json(['success' => false, 'message' => 'device_token required'], 401);
        }

        $device = Device::with('user')->where('device_token', $token)->first();

        if (! $device) {
            return response()->json(['success' => false, 'message' => 'Invalid device token'], 401);
        }

        $request->attributes->set('device', $device);

        return $next($request);
    }
}
