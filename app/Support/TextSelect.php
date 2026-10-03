<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Select list of a table where the FLOAT / DOUBLE / DECIMAL columns are read as text (CAST .. AS CHAR).
 * CakePHP reads every value as text ("8551.00"); PDO hands PHP a float, and the report figures - and the way
 * they print - are only the same when the arithmetic starts from the same text.
 */
class TextSelect
{
    /** @var array<string, array<int, array{0: string, 1: string}>> */
    private static array $cache = [];

    /**
     * @return array<int, \Illuminate\Database\Query\Expression|string>  ready for ->select([...])
     */
    public static function columns(string $table, ?string $alias = null, ?string $prefix = null): array
    {
        $alias ??= $table;
        if (!isset(self::$cache[$table])) {
            self::$cache[$table] = array_map(
                fn ($r) => [$r->column_name, strtolower($r->data_type)],
                DB::select('select column_name, data_type from information_schema.columns where table_schema = database() and table_name = ? order by ordinal_position', [$table])
            );
        }
        $out = [];
        foreach (self::$cache[$table] as [$name, $type]) {
            $as = ($prefix ?? '') . $name;
            if (in_array($type, ['float', 'double', 'decimal'], true)) {
                $out[] = DB::raw("CAST(`$alias`.`$name` AS CHAR) as `$as`");
            } else {
                $out[] = DB::raw("`$alias`.`$name` as `$as`");
            }
        }

        return $out;
    }
}
