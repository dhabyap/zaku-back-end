<?php

namespace App\Services;

use Carbon\CarbonInterface;

class DateLabelService
{
    private const MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public static function date(CarbonInterface $date): string
    {
        return $date->day.' '.self::MONTHS[$date->month].' '.$date->year;
    }

    public static function month(CarbonInterface $date): string
    {
        return strtoupper(self::MONTHS[$date->month]).' '.$date->year;
    }
}
