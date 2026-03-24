<?php

namespace App\Support;

final class Format
{
    public static function rupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    public static function kg(float $kg): string
    {
        return number_format($kg, 2, ',', '.') . ' Kg';
    }

    /**
     * Highlights search terms in a string.
     * Use with {!! !!} in Blade.
     */
    public static function highlight(string $text, ?string $query): string
    {
        if (!$query || trim($query) === '') {
            return e($text);
        }

        $escapedText = e($text);
        $escapedQuery = e($query);

        return str_ireplace(
            $escapedQuery,
            '<mark class="bg-yellow-200 font-bold text-gray-900 rounded-px px-0.5">' . $escapedQuery . '</mark>',
            $escapedText
        );
    }
    public static function phone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if ($phone === '') return '';

        // Handle various prefixes to ensure 08... format
        if (str_starts_with($phone, '628')) {
            $phone = '08' . substr($phone, 3);
        } elseif (str_starts_with($phone, '8') && strlen($phone) >= 9) {
            $phone = '0' . $phone;
        }

        return $phone;
    }
}
