<?php

namespace App\Support;

class BatteryHelper
{
    public static function normalize(?int $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return max(0, min(100, $value));
    }

    public static function fromPayload(array $data): ?int
    {
        if (array_key_exists('battery', $data) && $data['battery'] !== null) {
            return self::normalize((int) $data['battery']);
        }

        if (array_key_exists('battery_level', $data) && $data['battery_level'] !== null) {
            return self::normalize((int) $data['battery_level']);
        }

        return null;
    }

    /** @return 'green'|'yellow'|'red'|'gray' */
    public static function levelColor(?int $percent): string
    {
        if ($percent === null) {
            return 'gray';
        }

        if ($percent > 70) {
            return 'green';
        }

        if ($percent >= 30) {
            return 'yellow';
        }

        return 'red';
    }

    public static function statusLabel(?int $percent, bool $charging = false): string
    {
        if ($charging) {
            return 'Charging';
        }

        if ($percent === null) {
            return 'No Data';
        }

        if ($percent < 30) {
            return 'Critical Battery';
        }

        if ($percent < 70) {
            return 'Low Battery';
        }

        return 'Normal';
    }
}
