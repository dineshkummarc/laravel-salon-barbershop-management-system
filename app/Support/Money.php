<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class Money
{
    public static function format(int|float|null $minor): string
    {
        return '৳'.number_format(($minor ?? 0) / 100, 2);
    }

    public static function parse(string|int|float $value): int
    {
        if (! preg_match('/^\d+(\.\d{1,2})?$/', (string) $value)) {
            throw ValidationException::withMessages(['amount' => 'Use a non-negative amount with at most two decimal places.']);
        }
        $parts = explode('.', (string) $value);

        return ((int) $parts[0]) * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    }
}
