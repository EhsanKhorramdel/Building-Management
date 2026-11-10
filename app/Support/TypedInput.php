<?php

namespace App\Support;

final class TypedInput
{
    public static function normalize(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim(strtr($value, self::digitMap()));

        return $value === '' ? null : $value;
    }

    public static function int(int|string $value): int
    {
        return (int) self::normalize($value);
    }

    public static function float(float|int|string $value): float
    {
        return (float) self::normalize($value);
    }

    public static function nullableInt(mixed $value = null): ?int
    {
        $value = self::normalize($value);

        return filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
    }

    public static function nullableFloat(float|int|string|null $value = null): ?float
    {
        $value = self::normalize($value);

        return $value === null ? null : self::float($value);
    }

    public static function searchText(string $value): string
    {
        return trim(strtr($value, self::searchMap()));
    }

    public static function searchMap(): array
    {
        return self::digitMap() + ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک'];
    }

    private static function digitMap(): array
    {
        return array_combine(mb_str_split('۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩'), str_split('01234567890123456789'));
    }
}
