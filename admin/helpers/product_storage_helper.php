<?php
declare(strict_types=1);

if (!function_exists('product_storage_slugify')) {
    function product_storage_slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\.[^.]+$/', '', $value);
        $value = str_replace(['_', '+'], ' ', $value);
        $value = str_replace('.', ' ', $value);
        $value = preg_replace('/[^a-z0-9\-\s]+/', ' ', (string)$value);
        $value = preg_replace('/\s+/', '-', (string)$value);
        $value = preg_replace('/-+/', '-', (string)$value);
        return trim((string)$value, '-');
    }
}

if (!function_exists('product_storage_ensure_dir')) {
    function product_storage_ensure_dir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException('Failed to create directory: ' . $dir);
        }
    }
}

if (!function_exists('product_storage_encode_json')) {
    function product_storage_encode_json(array $payload): string
    {
        $encoded = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        if ($encoded === false) {
            throw new RuntimeException('Failed to encode JSON payload');
        }

        return $encoded;
    }
}

if (!function_exists('product_storage_write_json_file')) {
    function product_storage_write_json_file(string $filePath, array $payload): void
    {
        product_storage_ensure_dir(dirname($filePath));

        $encoded = product_storage_encode_json($payload);

        if (file_put_contents($filePath, $encoded) === false) {
            throw new RuntimeException('Failed to write JSON file: ' . $filePath);
        }
    }
}

if (!function_exists('product_storage_ensure_category_structure')) {
    function product_storage_ensure_category_structure(string $categorySlug): array
    {
        $categorySlug = product_storage_slugify($categorySlug);

        if ($categorySlug === '') {
            throw new RuntimeException('Invalid category slug');
        }

        $rootDir = dirname(__DIR__, 2);
        $imagesCategoryDir = $rootDir . '/images/' . $categorySlug;
        $productsCategoryDir = $rootDir . '/products/' . $categorySlug;
        $categoryDataJsonAbs = $productsCategoryDir . '/data.json';

        product_storage_ensure_dir($imagesCategoryDir);
        product_storage_ensure_dir($productsCategoryDir);

        if (!is_file($categoryDataJsonAbs)) {
            product_storage_write_json_file($categoryDataJsonAbs, []);
        }

        return [
            'root_dir' => $rootDir,
            'category_slug' => $categorySlug,
            'images_category_dir_abs' => $imagesCategoryDir,
            'images_category_dir_rel' => '/images/' . $categorySlug . '/',
            'products_category_dir_abs' => $productsCategoryDir,
            'products_category_dir_rel' => '/products/' . $categorySlug . '/',
            'category_data_json_abs' => $categoryDataJsonAbs,
            'category_data_json_rel' => '/products/' . $categorySlug . '/data.json',
        ];
    }
}

if (!function_exists('product_storage_build_paths')) {
    function product_storage_build_paths(string $categorySlug, string $brandSlug, string $productSlug): array
    {
        $categoryPaths = product_storage_ensure_category_structure($categorySlug);

        $brandSlug = product_storage_slugify($brandSlug);
        $productSlug = product_storage_slugify($productSlug);

        if ($brandSlug === '' || $productSlug === '') {
            throw new RuntimeException('Invalid brand slug or product slug');
        }

        $brandImageDirAbs = $categoryPaths['images_category_dir_abs'] . '/' . $brandSlug;
        product_storage_ensure_dir($brandImageDirAbs);

        $imageAbs = $brandImageDirAbs . '/' . $productSlug . '.webp';
        $imageRel = '/images/' . $categoryPaths['category_slug'] . '/' . $brandSlug . '/' . $productSlug . '.webp';

        return array_merge($categoryPaths, [
            'brand_slug' => $brandSlug,
            'product_slug' => $productSlug,
            'brand_image_dir_abs' => $brandImageDirAbs,
            'brand_image_dir_rel' => '/images/' . $categoryPaths['category_slug'] . '/' . $brandSlug . '/',
            'image_abs' => $imageAbs,
            'image_rel' => $imageRel,
        ]);
    }
}

if (!function_exists('product_storage_fetch_category_brand')) {
    function product_storage_fetch_category_brand(PDO $pdo, int $categoryId, int $brandId): array
    {
        $stmt = $pdo->prepare("
            SELECT 
                c.id AS category_id,
                c.display_name AS category_name,
                c.slug AS category_slug,
                b.id AS brand_id,
                b.name AS brand_name,
                b.display_name AS brand_display_name,
                b.slug AS brand_slug
            FROM categories c
            INNER JOIN brands b ON b.category_id = c.id
            WHERE c.id = :category_id
              AND b.id = :brand_id
            LIMIT 1
        ");
        $stmt->execute([
            'category_id' => $categoryId,
            'brand_id' => $brandId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new RuntimeException('Invalid category or brand');
        }

        return $row;
    }
}

if (!function_exists('product_storage_remove_file_if_exists')) {
    function product_storage_remove_file_if_exists(string $filePath): void
    {
        if ($filePath !== '' && is_file($filePath)) {
            @unlink($filePath);
        }
    }
}

if (!function_exists('product_storage_find_binary')) {
    function product_storage_find_binary(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

if (!function_exists('product_storage_run_command')) {
    function product_storage_run_command(array $command): void
    {
        if (!function_exists('proc_open')) {
            throw new RuntimeException('No available process execution method on this server');
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start image conversion process');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $message = trim($stderr !== '' ? $stderr : $stdout);
            throw new RuntimeException($message !== '' ? $message : 'Image conversion command failed');
        }
    }
}

if (!function_exists('product_storage_convert_to_webp')) {
    function product_storage_convert_to_webp(
        string $sourcePath,
        string $targetPath,
        bool $isUploaded = false,
        int $quality = 85
    ): void {
        if (!is_file($sourcePath)) {
            throw new RuntimeException('Source image file not found');
        }

        product_storage_ensure_dir(dirname($targetPath));

        $quality = max(1, min(100, $quality));

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo ? (string)finfo_file($finfo, $sourcePath) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mime === '') {
            $imageInfo = @getimagesize($sourcePath);
            $mime = is_array($imageInfo) ? (string)($imageInfo['mime'] ?? '') : '';
        }

        $mime = strtolower(trim($mime));

        if ($mime === 'image/webp') {
            if ($isUploaded && is_uploaded_file($sourcePath)) {
                if (!move_uploaded_file($sourcePath, $targetPath)) {
                    throw new RuntimeException('Failed to store uploaded WebP image');
                }
            } else {
                if (realpath($sourcePath) !== realpath($targetPath) && !copy($sourcePath, $targetPath)) {
                    throw new RuntimeException('Failed to copy WebP image');
                }
            }

            if (!is_file($targetPath)) {
                throw new RuntimeException('WebP image was not created');
            }

            return;
        }

        if (class_exists('Imagick')) {
            try {
                $image = new Imagick();
                $image->readImage($sourcePath);
                $image->setImageFormat('webp');
                if (method_exists($image, 'setImageCompressionQuality')) {
                    $image->setImageCompressionQuality($quality);
                }
                $image->writeImage($targetPath);
                $image->clear();
                $image->destroy();

                if (!is_file($targetPath)) {
                    throw new RuntimeException('Imagick did not create the WebP image');
                }

                return;
            } catch (Throwable $e) {
                product_storage_remove_file_if_exists($targetPath);
            }
        }

        if (function_exists('imagewebp')) {
            $resource = null;

            if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
                $resource = @imagecreatefromjpeg($sourcePath);
            } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
                $resource = @imagecreatefrompng($sourcePath);
            } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
                $resource = @imagecreatefromwebp($sourcePath);
            } elseif ($mime === 'image/gif' && function_exists('imagecreatefromgif')) {
                $resource = @imagecreatefromgif($sourcePath);
            } elseif (function_exists('imagecreatefromstring')) {
                $resource = @imagecreatefromstring((string)file_get_contents($sourcePath));
            }

            if ((class_exists('GdImage', false) && $resource instanceof GdImage) || is_resource($resource)) {
                if (function_exists('imagepalettetotruecolor')) {
                    @imagepalettetotruecolor($resource);
                }

                if (function_exists('imagealphablending')) {
                    @imagealphablending($resource, true);
                }

                if (function_exists('imagesavealpha')) {
                    @imagesavealpha($resource, true);
                }

                if (!@imagewebp($resource, $targetPath, $quality)) {
                    if ((class_exists('GdImage', false) && $resource instanceof GdImage) || is_resource($resource)) {
                        imagedestroy($resource);
                    }
                    throw new RuntimeException('Failed to convert image to WebP using GD');
                }

                if ($resource instanceof GdImage) {
                    imagedestroy($resource);
                } elseif (is_resource($resource)) {
                    imagedestroy($resource);
                }

                if (!is_file($targetPath)) {
                    throw new RuntimeException('GD did not create the WebP image');
                }

                return;
            }
        }

        $cwebp = product_storage_find_binary([
            '/usr/bin/cwebp',
            '/usr/local/bin/cwebp',
            '/bin/cwebp',
        ]);

        if ($cwebp !== null) {
            product_storage_run_command([
                $cwebp,
                '-quiet',
                '-q',
                (string)$quality,
                $sourcePath,
                '-o',
                $targetPath,
            ]);

            if (!is_file($targetPath)) {
                throw new RuntimeException('cwebp did not create the WebP image');
            }

            return;
        }

        $ffmpeg = product_storage_find_binary([
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/bin/ffmpeg',
        ]);

        if ($ffmpeg !== null) {
            product_storage_run_command([
                $ffmpeg,
                '-y',
                '-loglevel',
                'error',
                '-i',
                $sourcePath,
                '-vcodec',
                'libwebp',
                '-q:v',
                (string)max(1, min(100, (int)round(($quality / 100) * 75))),
                $targetPath,
            ]);

            if (!is_file($targetPath)) {
                throw new RuntimeException('ffmpeg did not create the WebP image');
            }

            return;
        }

        throw new RuntimeException('WebP conversion is not available on this server. Install GD, Imagick, cwebp, or ffmpeg.');
    }
}
