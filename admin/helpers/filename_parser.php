<?php
declare(strict_types=1);

function normalize_filename_text(string $text): string
{
    $text = strtolower($text);
    $text = str_replace(['_', '-'], ' ', $text);
    $text = preg_replace('/\s+/', ' ', (string)$text);
    return trim((string)$text);
}

if (!function_exists('filename_capacity_label')) {
    function filename_capacity_label(string $number, string $unit = 'GB'): string
    {
        $number = trim($number);
        $unit = strtoupper(trim($unit));

        if ($number === '') {
            return '';
        }

        if ($unit === 'TB') {
            return strtoupper($number) . 'TB';
        }

        return strtoupper($number) . 'GB';
    }
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

        if (preg_match('/\b(4g|5g)\b/i', $normalized, $networkMatch)) {
            $network = strtoupper((string)$networkMatch[1]);
        }

        $normalizedForStorage = $normalized;

        /*
        |--------------------------------------------------------------
        | Explicit RAM patterns
        |--------------------------------------------------------------
        | Supports:
        |   20GB RAM
        |   RAM 20GB
        |   20 RAM
        |   RAM 20
        */
        if (preg_match('/\b(\d+(?:\.\d+)?)\s*(tb|gb)\s*ram\b/i', $normalized, $ramMatch)) {
            $ram = filename_capacity_label((string)$ramMatch[1], (string)$ramMatch[2]) . ' RAM';
            $normalizedForStorage = preg_replace('/' . preg_quote((string)$ramMatch[0], '/') . '/i', ' ', $normalizedForStorage, 1) ?? $normalizedForStorage;
        } elseif (preg_match('/\bram\s*(\d+(?:\.\d+)?)\s*(tb|gb)\b/i', $normalized, $ramMatch)) {
            $ram = filename_capacity_label((string)$ramMatch[1], (string)$ramMatch[2]) . ' RAM';
            $normalizedForStorage = preg_replace('/' . preg_quote((string)$ramMatch[0], '/') . '/i', ' ', $normalizedForStorage, 1) ?? $normalizedForStorage;
        } elseif (preg_match('/\b(\d+(?:\.\d+)?)\s*ram\b/i', $normalized, $ramMatch)) {
            $ram = filename_capacity_label((string)$ramMatch[1], 'GB') . ' RAM';
            $normalizedForStorage = preg_replace('/' . preg_quote((string)$ramMatch[0], '/') . '/i', ' ', $normalizedForStorage, 1) ?? $normalizedForStorage;
        } elseif (preg_match('/\bram\s*(\d+(?:\.\d+)?)\b/i', $normalized, $ramMatch)) {
            $ram = filename_capacity_label((string)$ramMatch[1], 'GB') . ' RAM';
            $normalizedForStorage = preg_replace('/' . preg_quote((string)$ramMatch[0], '/') . '/i', ' ', $normalizedForStorage, 1) ?? $normalizedForStorage;
        }

        /*
        |--------------------------------------------------------------
        | Storage detection after removing explicit RAM phrase
        |--------------------------------------------------------------
        */
        preg_match_all('/\b(\d+(?:\.\d+)?)\s*(tb|gb)\b/i', $normalizedForStorage, $storageMatches, PREG_SET_ORDER);
        $storageValues = [];

        foreach ($storageMatches as $match) {
            $label = filename_capacity_label((string)$match[1], (string)$match[2]);
            if ($label !== '') {
                $storageValues[] = $label;
            }
        }

        if (!empty($storageValues)) {
            $storage = (string)$storageValues[0];
        }

        /*
        |--------------------------------------------------------------
        | If RAM was not explicit, infer from second capacity token
        |--------------------------------------------------------------
        | Examples:
        |   256GB 20GB 5G  => storage 256GB, ram 20GB RAM
        |   1TB 16GB       => storage 1TB,   ram 16GB RAM
        */
        if ($ram === null) {
            preg_match_all('/\b(\d+(?:\.\d+)?)\s*(tb|gb)\b/i', $normalized, $capacityMatches, PREG_SET_ORDER);
            $capacityValues = [];

            foreach ($capacityMatches as $match) {
                $label = filename_capacity_label((string)$match[1], (string)$match[2]);
                if ($label !== '') {
                    $capacityValues[] = $label;
                }
            }

            if (count($capacityValues) >= 2) {
                $storage = (string)$capacityValues[0];
                $ram = (string)$capacityValues[1] . ' RAM';
            } elseif (count($capacityValues) === 1 && $storage === null) {
                $storage = (string)$capacityValues[0];
            }
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
