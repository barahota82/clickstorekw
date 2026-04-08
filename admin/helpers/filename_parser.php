<?php
declare(strict_types=1);

function normalize_filename_text(string $text): string
{
    $text = strtolower($text);
    $text = preg_replace('/\.[^.]+$/', '', $text);
    $text = str_replace(['_', '-'], ' ', $text);
    $text = preg_replace('/\s+/', ' ', (string)$text);
    return trim((string)$text);
}

function parse_devices_from_filename(string $filename): array
{
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $parts = preg_split('/\+/', $base) ?: [];

    $devices = [];

    foreach ($parts as $index => $part) {
        $raw = trim((string)$part);

        if ($raw === '') {
            continue;
        }

        $normalized = normalize_filename_text($raw);

        $storage = null;
        $ram = null;
        $network = null;

        if (preg_match('/\b(64|128|256|512)\s*gb\b/i', $normalized, $storageMatch)) {
            $storage = strtoupper($storageMatch[1]) . 'GB';
        } elseif (preg_match('/\b1\s*tb\b/i', $normalized)) {
            $storage = '1TB';
        }

        if (preg_match('/\b(2|3|4|6|8|12|16)\s*gb\s*ram\b/i', $normalized, $ramMatch)) {
            $ram = $ramMatch[1] . 'GB RAM';
        } elseif (preg_match('/\bram\s*(2|3|4|6|8|12|16)\b/i', $normalized, $ramMatch)) {
            $ram = $ramMatch[1] . 'GB RAM';
        } elseif (preg_match('/\b(2|3|4|6|8|12|16)\s*ram\b/i', $normalized, $ramMatch)) {
            $ram = $ramMatch[1] . 'GB RAM';
        }

        if (preg_match('/\b(4g|5g)\b/i', $normalized, $networkMatch)) {
            $network = strtoupper($networkMatch[1]);
        }

        $devices[] = [
            'device_index' => $index + 1,
            'raw_title' => $raw,
            'normalized_title' => $normalized,
            'storage_value' => $storage,
            'ram_value' => $ram,
            'network_value' => $network,
        ];
    }

    return $devices;
}
