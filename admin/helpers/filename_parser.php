<?php
declare(strict_types=1);

function normalize_filename_text(string $text): string
{
    $text = strtolower($text);
    $text = str_replace(['_', '-'], ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim((string)$text);
}

function parse_devices_from_filename(string $filename): array
{
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $parts = preg_split('/\+/', $base) ?: [];

    $devices = [];

    foreach ($parts as $index => $part) {
        $raw = trim($part);
        if ($raw === '') {
            continue;
        }

        $normalized = normalize_filename_text($raw);

        preg_match('/(64|128|256|512|1tb)\s*gb?|\b1tb\b/i', $normalized, $storageMatch);
        preg_match('/(2|3|4|6|8|12|16)\s*gb\s*ram|\b(2|3|4|6|8|12|16)\s*ram\b/i', $normalized, $ramMatch);
        preg_match('/\b(4g|5g)\b/i', $normalized, $networkMatch);

        $storage = null;
        $ram = null;
        $network = null;

        if (!empty($storageMatch[0])) {
            $storage = strtoupper(str_replace(' ', '', $storageMatch[0]));
            $storage = str_replace('GB', 'GB', $storage);
            if ($storage === '1TB') {
                $storage = '1TB';
            }
        }

        if (!empty($ramMatch[0])) {
            preg_match('/(2|3|4|6|8|12|16)/', $ramMatch[0], $ramNumber);
            if (!empty($ramNumber[1])) {
                $ram = $ramNumber[1] . 'GB RAM';
            }
        }

        if (!empty($networkMatch[1])) {
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
