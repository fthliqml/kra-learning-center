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

if (!function_exists('rsrc_url')) {
    function rsrc_url(?string $u): string
    {
        if (!$u) {
            return '';
        }
        if (str_starts_with($u, 'http://') || str_starts_with($u, 'https://')) {
            return $u;
        }
        try {
            return \Illuminate\Support\Facades\Storage::url($u);
        } catch (\Throwable $e) {
            return asset($u);
        }
    }
}

if (!function_exists('yt_embed_url')) {
    function yt_embed_url(?string $u): string
    {
        $u = rsrc_url($u);
        if (!$u) {
            return '';
        }
        $parts = @parse_url($u) ?: [];
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $query = $parts['query'] ?? '';
        $id = null;
        if (str_contains($host, 'youtu.be')) {
            $id = ltrim($path, '/');
        } elseif (str_contains($host, 'youtube.com')) {
            if (str_starts_with($path, '/watch')) {
                parse_str($query ?? '', $q);
                $id = $q['v'] ?? null;
            } elseif (str_starts_with($path, '/shorts/')) {
                $id = trim(substr($path, strlen('/shorts/')));
            } elseif (str_starts_with($path, '/embed/')) {
                return $u; // already an embed URL
            }
        }
        return $id ? ('https://www.youtube-nocookie.com/embed/' . $id) : $u;
    }
}
