<?php

namespace App\Support;

class HomePage
{
    /**
     * Marketing-friendly figure for the landing page "at a glance" row: rounds
     * DOWN to a clean number and adds "+" (same as Cake's elGlanceNum()).
     */
    public static function glanceNumber($n): string
    {
        $n = (int) $n;

        if ($n >= 10000000) {
            $c = floor($n / 1000000) / 10;
            return rtrim(rtrim(number_format($c, 1), '0'), '.') . ' Cr+';
        }
        if ($n >= 100000) {
            return floor($n / 100000) . ' Lakh+';
        }
        if ($n >= 1000) {
            return number_format(floor($n / 100) * 100) . '+';
        }
        if ($n >= 100) {
            return (string) (floor($n / 10) * 10) . '+';
        }

        return $n > 0 ? $n . '+' : '0';
    }
}
