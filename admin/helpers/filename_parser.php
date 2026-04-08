<?php
declare(strict_types=1);

function normalize_filename_text(string $text): string
{
    $text = strtolower($text);
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

        preg_match_all('/\b(64|128|256|512)\s*gb\b/i', $normalized, $gbMatches);
        preg_match('/\b1\s*tb\b/i', $normalized, $tbMatch);
        preg_match('/\b(4g|5g)\b/i', $normalized, $networkMatch);

        $gbValues = [];
        if (!empty($gbMatches[1]) && is_array($gbMatches[1])) {
            foreach ($gbMatches[1] as $value) {
                $gbValues[] = strtoupper(trim((string)$value)) . 'GB';
            }
        }

        if (!empty($tbMatch[0])) {
            array_unshift($gbValues, '1TB');
        }

        if (count($gbValues) >= 2) {
            $storage = $gbValues[0];
            $ram = $gbValues[1] . ' RAM';
        } elseif (count($gbValues) === 1) {
            if (
                preg_match('/\b(ram)\s*(64|128|256|512)\s*gb\b/i', $normalized, $ramAfterRamWord) ||
                preg_match('/\b(64|128|256|512)\s*gb\s*ram\b/i', $normalized, $ramWithWord)
            ) {
                $ramNumber = $ramAfterRamWord[2] ?? $ramWithWord[1] ?? '';
                if ($ramNumber !== '') {
                    $ram = strtoupper($ramNumber) . 'GB RAM';
                }
            } else {
                $storage = $gbValues[0];
            }
        }

        if ($ram === null) {
            if (preg_match('/\b(2|3|4|6|8|12|16|18|24|32)\s*gb\s*ram\b/i', $normalized, $ramMatch)) {
                $ram = $ramMatch[1] . 'GB RAM';
            } elseif (preg_match('/\bram\s*(2|3|4|6|8|12|16|18|24|32)\b/i', $normalized, $ramMatch)) {
                $ram = $ramMatch[1] . 'GB RAM';
            } elseif (preg_match('/\b(2|3|4|6|8|12|16|18|24|32)\s*ram\b/i', $normalized, $ramMatch)) {
                $ram = $ramMatch[1] . 'GB RAM';
            } elseif (
                $storage !== null &&
                preg_match_all('/\b(2|3|4|6|8|12|16|18|24|32)\s*gb\b/i', $normalized, $smallGbMatches) &&
                !empty($smallGbMatches[1])
            ) {
                foreach ($smallGbMatches[1] as $candidate) {
                    $candidateValue = strtoupper(trim((string)$candidate)) . 'GB';
                    if ($candidateValue !== $storage) {
                        $ram = $candidateValue . ' RAM';
                        break;
                    }
                }
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
