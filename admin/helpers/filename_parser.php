<?php

function normalize_text($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function extract_devices_from_filename($filename) {
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = normalize_text($name);

    // فصل الأجهزة (لو فيه + أو and)
    $parts = preg_split('/\+|and|,/', $name);

    $devices = [];

    foreach ($parts as $part) {
        $part = trim($part);

        if (!$part) continue;

        $device = [
            'title' => $part,
            'normalized' => normalize_text($part),
            'storage' => null,
            'ram' => null,
        ];

        // استخراج storage
        if (preg_match('/(128|256|512|1tb)/', $part, $m)) {
            $device['storage'] = $m[1];
        }

        // استخراج RAM
        if (preg_match('/(4gb|6gb|8gb|12gb)/', $part, $m)) {
            $device['ram'] = $m[1];
        }

        $devices[] = $device;
    }

    return $devices;
}
