<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class SqlDialect
{
    public static function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    public static function stringAggregateDistinct(string $column, string $alias): string
    {
        return match (self::driver()) {
            'pgsql' => "string_agg(DISTINCT {$column}, ',') as {$alias}",
            default => "group_concat(DISTINCT {$column}) as {$alias}",
        };
    }

    public static function transactionNumberOrderExpression(string $column = 'no_transaksi', int $startPosition = 14): string
    {
        return match (self::driver()) {
            'sqlite' => "CAST(SUBSTR({$column}, {$startPosition}) AS INTEGER) DESC",
            'pgsql' => "CAST(SUBSTRING({$column} FROM {$startPosition}) AS INTEGER) DESC",
            default => "CAST(SUBSTRING({$column}, {$startPosition}) AS UNSIGNED) DESC",
        };
    }

    public static function yearlyLocalDateExpression(string $expression, string $alias = 'date'): string
    {
        $timezoneOffset = config('database.connections.mysql.timezone', '+07:00');

        return match (self::driver()) {
            'pgsql', 'sqlite' => "DATE({$expression}) as {$alias}",
            default => "DATE(CONVERT_TZ({$expression}, '+00:00', '{$timezoneOffset}')) as {$alias}",
        };
    }

    public static function effectiveTimestamp(string $waktuTerimaColumn = 'waktu_terima', string $createdAtColumn = 'created_at'): string
    {
        return "COALESCE({$waktuTerimaColumn}, {$createdAtColumn})";
    }

    public static function maxEffectiveTimestamp(string $waktuTerimaColumn = 'waktu_terima', string $createdAtColumn = 'created_at', string $alias = 'effective_time'): string
    {
        return 'MAX(' . self::effectiveTimestamp($waktuTerimaColumn, $createdAtColumn) . ") as {$alias}";
    }

    public static function effectiveTimestampOrder(string $waktuTerimaColumn = 'waktu_terima', string $createdAtColumn = 'created_at', string $direction = 'DESC'): string
    {
        return self::effectiveTimestamp($waktuTerimaColumn, $createdAtColumn) . ' ' . strtoupper($direction);
    }

    public static function maxEffectiveTimestampOrder(string $waktuTerimaColumn = 'waktu_terima', string $createdAtColumn = 'created_at', string $direction = 'DESC'): string
    {
        return 'MAX(' . self::effectiveTimestamp($waktuTerimaColumn, $createdAtColumn) . ') ' . strtoupper($direction);
    }

    public static function moneyTransferAggregate(string $methodColumn = 'metode', string $transferColumn = 'is_transfer', string $alias = 'has_transfer'): string
    {
        return 'MAX(' . self::booleanAsIntegerCase("{$methodColumn} = 'uang'", $transferColumn) . ") as {$alias}";
    }

    public static function booleanAsInteger(string $column): string
    {
        return "CASE WHEN {$column} THEN 1 ELSE 0 END";
    }

    public static function booleanAsIntegerCase(string $conditionSql, string $booleanColumn): string
    {
        return "CASE WHEN {$conditionSql} THEN " . self::booleanAsInteger($booleanColumn) . ' ELSE 0 END';
    }

    public static function sumWhenBooleanTrue(string $booleanColumn, string $valueColumn, string $alias): string
    {
        return "SUM(CASE WHEN {$booleanColumn} THEN {$valueColumn} ELSE 0 END) as {$alias}";
    }

    public static function sumWhenBooleanFalse(string $booleanColumn, string $valueColumn, string $alias): string
    {
        return "SUM(CASE WHEN {$booleanColumn} THEN 0 ELSE {$valueColumn} END) as {$alias}";
    }

    public static function dateExpression(string $expression, string $alias = 'date'): string
    {
        return "DATE({$expression}) as {$alias}";
    }
}
