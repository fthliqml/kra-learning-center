<?php

if (!function_exists('formatRangeDate')) {
    function formatRangeDate($startDate, $endDate)
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);

        return $start->format('d') . '-' . $end->format('d') . ' ' . $end->format('F Y');
    }
}

if (!function_exists('formatFullDate')) {
    function formatFullDate($date)
    {
        $d = new DateTime($date);
        return $d->format('d F Y');
    }
}
