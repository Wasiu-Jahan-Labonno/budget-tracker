<?php

namespace App\Support;

use Carbon\CarbonImmutable;

trait DateRanges
{
    protected function parseMonth(string $ym = null): array
    {
        $tz = 'Asia/Dhaka';
        $base = $ym
            ? CarbonImmutable::createFromFormat('Y-m-d', $ym . '-01', $tz)
            : CarbonImmutable::now($tz)->startOfMonth();

        $start = $base->startOfMonth();
        $end   = $base->endOfMonth();
        return [$start, $end];
    }

    protected function threeMonthWindowBefore(string $ym): array
    {
        $tz = 'Asia/Dhaka';
        $base = CarbonImmutable::createFromFormat('Y-m-d', $ym . '-01', $tz);
        $prevStart = $base->subMonths(3)->startOfMonth();
        $prevEnd   = $base->subMonth()->endOfMonth();
        return [$prevStart, $prevEnd];
    }
}
