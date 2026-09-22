<?php

namespace App\Support;

final class Utf8
{
    public static function clean(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[is_string($key) ? (string) self::clean($key) : $key] = self::clean($item);
            }

            return $out;
        }

        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8');

        return is_string($converted) && $converted !== '' ? $converted : preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
    }
}
