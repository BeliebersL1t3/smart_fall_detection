<?php

namespace App\Support;

class IotUrl
{
    public static function normalizeApiBase(?string $url): string
    {
        $url = rtrim(trim((string) $url), '/');

        if ($url !== '' && ! str_ends_with($url, '/api')) {
            $url .= '/api';
        }

        return $url !== '' ? $url : rtrim(url('/api'), '/');
    }

    /** URL untuk API_BASE_URL di Arduino (tanpa /api) */
    public static function arduinoRootFromApiBase(string $apiBase): string
    {
        $apiBase = rtrim($apiBase, '/');

        if (str_ends_with($apiBase, '/api')) {
            return substr($apiBase, 0, -4);
        }

        return $apiBase;
    }
}
