<?php
declare(strict_types=1);

if (!function_exists('github_sync_env')) {
    function github_sync_env(string $key, mixed $default = null): mixed
    {
        global $env;

        if (isset($env) && is_array($env) && array_key_exists($key, $env)) {
            return $env[$key];
        }

        return $default;
    }
}

if (!function_exists('github_sync_is_enabled')) {
    function github_sync_is_enabled(): bool
    {
        return (bool)github_sync_env('GITHUB_SYNC_ENABLED', false);
    }
}

if (!function_exists('github_sync_is_configured')) {
    function github_sync_is_configured(): bool
    {
        if (!github_sync_is_enabled()) {
            return false;
        }

        $token = trim((string)github_sync_env('GITHUB_SYNC_TOKEN', ''));
        $owner = trim((string)github_sync_env('GITHUB_SYNC_OWNER', ''));
        $repo = trim((string)github_sync_env('GITHUB_SYNC_REPO', ''));

        return $token !== '' && $owner !== '' && $repo !== '';
    }
}

if (!function_exists('github_sync_reset_report')) {
    function github_sync_reset_report(): void
    {
        $GLOBALS['__github_sync_report'] = [
            'enabled' => github_sync_is_enabled(),
            'configured' => github_sync_is_configured(),
            'has_errors' => false,
            'operations' => [],
        ];
    }
}

if (!function_exists('github_sync_record_operation')) {
    function github_sync_record_operation(string $action, string $path, bool $ok, string $message = ''): void
    {
        if (!isset($GLOBALS['__github_sync_report']) || !is_array($GLOBALS['__github_sync_report'])) {
            github_sync_reset_report();
        }

        if (!$ok) {
            $GLOBALS['__github_sync_report']['has_errors'] = true;
        }

        $GLOBALS['__github_sync_report']['operations'][] = [
            'action' => $action,
            'path' => $path,
            'ok' => $ok,
            'message' => $message,
        ];
    }
}

if (!function_exists('github_sync_get_report')) {
    function github_sync_get_report(): array
    {
        if (!isset($GLOBALS['__github_sync_report']) || !is_array($GLOBALS['__github_sync_report'])) {
            github_sync_reset_report();
        }

        return $GLOBALS['__github_sync_report'];
    }
}

if (!function_exists('github_sync_repo_branch')) {
    function github_sync_repo_branch(): string
    {
        $branch = trim((string)github_sync_env('GITHUB_SYNC_BRANCH', 'main'));
        return $branch !== '' ? $branch : 'main';
    }
}

if (!function_exists('github_sync_repo_prefix')) {
    function github_sync_repo_prefix(): string
    {
        $prefix = trim((string)github_sync_env('GITHUB_SYNC_PATH_PREFIX', ''));
        $prefix = trim($prefix, '/');
        return $prefix;
    }
}

if (!function_exists('github_sync_normalize_repo_path')) {
    function github_sync_normalize_repo_path(string $publicPath): string
    {
        $path = trim($publicPath);
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);
        $path = ltrim((string)$path, '/');

        $prefix = github_sync_repo_prefix();
        if ($prefix !== '') {
            $path = $prefix . '/' . $path;
        }

        return trim($path, '/');
    }
}

if (!function_exists('github_sync_encoded_repo_path')) {
    function github_sync_encoded_repo_path(string $publicPath): string
    {
        $normalized = github_sync_normalize_repo_path($publicPath);
        $parts = array_map('rawurlencode', array_values(array_filter(explode('/', $normalized), static fn($v) => $v !== '')));
        return implode('/', $parts);
    }
}

if (!function_exists('github_sync_api_base')) {
    function github_sync_api_base(): string
    {
        $owner = rawurlencode(trim((string)github_sync_env('GITHUB_SYNC_OWNER', '')));
        $repo = rawurlencode(trim((string)github_sync_env('GITHUB_SYNC_REPO', '')));
        return 'https://api.github.com/repos/' . $owner . '/' . $repo . '/contents/';
    }
}

if (!function_exists('github_sync_request')) {
    function github_sync_request(string $method, string $publicPath, ?array $payload = null, array $query = []): array
    {
        if (!github_sync_is_enabled()) {
            throw new RuntimeException('GitHub sync is disabled');
        }

        if (!github_sync_is_configured()) {
            throw new RuntimeException('GitHub sync is not configured');
        }

        $token = trim((string)github_sync_env('GITHUB_SYNC_TOKEN', ''));
        $url = github_sync_api_base() . github_sync_encoded_repo_path($publicPath);

        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        $headers = [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . $token,
            'X-GitHub-Api-Version: 2022-11-28',
            'User-Agent: ClickCompany-GitHubSync',
        ];

        $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        if ($body === false) {
            throw new RuntimeException('Failed to encode GitHub sync payload');
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                throw new RuntimeException('Failed to initialize cURL');
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            $responseBody = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($responseBody === false) {
                throw new RuntimeException('GitHub sync request failed: ' . $curlErr);
            }
        } else {
            $headerLines = $headers;
            if ($body !== null) {
                $headerLines[] = 'Content-Type: application/json';
            }

            $context = stream_context_create([
                'http' => [
                    'method' => strtoupper($method),
                    'header' => implode("\r\n", $headerLines),
                    'content' => $body ?? '',
                    'timeout' => 30,
                    'ignore_errors' => true,
                ],
            ]);

            $responseBody = @file_get_contents($url, false, $context);
            $responseHeaders = $http_response_header ?? [];
            $status = 0;

            foreach ($responseHeaders as $headerLine) {
                if (preg_match('#HTTP/\S+\s+(\d{3})#', $headerLine, $m)) {
                    $status = (int)$m[1];
                    break;
                }
            }

            if ($responseBody === false && $status === 0) {
                throw new RuntimeException('GitHub sync request failed');
            }
        }

        $decoded = null;
        if (is_string($responseBody) && trim($responseBody) !== '') {
            $decoded = json_decode($responseBody, true);
        }

        return [
            'status' => $status,
            'body' => is_string($responseBody) ? $responseBody : '',
            'json' => is_array($decoded) ? $decoded : null,
        ];
    }
}

if (!function_exists('github_sync_get_sha')) {
    function github_sync_get_sha(string $publicPath): ?string
    {
        $response = github_sync_request('GET', $publicPath, null, ['ref' => github_sync_repo_branch()]);

        if ($response['status'] === 404) {
            return null;
        }

        if ($response['status'] < 200 || $response['status'] >= 300) {
            $message = is_array($response['json']) ? (string)($response['json']['message'] ?? '') : '';
            throw new RuntimeException($message !== '' ? $message : 'Failed to fetch GitHub file SHA');
        }

        return is_array($response['json']) ? (string)($response['json']['sha'] ?? '') ?: null : null;
    }
}

if (!function_exists('github_sync_upsert_text_file')) {
    function github_sync_upsert_text_file(string $publicPath, string $contents, string $commitMessage): bool
    {
        if (!github_sync_is_enabled()) {
            return false;
        }

        if (!github_sync_is_configured()) {
            github_sync_record_operation('upsert', $publicPath, false, 'GitHub sync is enabled but not configured');
            return false;
        }

        try {
            $sha = github_sync_get_sha($publicPath);

            $payload = [
                'message' => $commitMessage,
                'content' => base64_encode($contents),
                'branch' => github_sync_repo_branch(),
            ];

            if ($sha !== null && $sha !== '') {
                $payload['sha'] = $sha;
            }

            $response = github_sync_request('PUT', $publicPath, $payload);

            if ($response['status'] < 200 || $response['status'] >= 300) {
                $message = is_array($response['json']) ? (string)($response['json']['message'] ?? '') : 'Unknown GitHub sync error';
                throw new RuntimeException($message);
            }

            github_sync_record_operation('upsert', $publicPath, true, 'Synced to GitHub');
            return true;
        } catch (Throwable $e) {
            github_sync_record_operation('upsert', $publicPath, false, $e->getMessage());
            error_log('[GitHub Sync] upsert failed for ' . $publicPath . ': ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('github_sync_upsert_local_file')) {
    function github_sync_upsert_local_file(string $publicPath, string $absolutePath, string $commitMessage): bool
    {
        if (!is_file($absolutePath)) {
            github_sync_record_operation('upsert', $publicPath, false, 'Local file not found: ' . $absolutePath);
            return false;
        }

        $contents = file_get_contents($absolutePath);
        if ($contents === false) {
            github_sync_record_operation('upsert', $publicPath, false, 'Failed to read local file: ' . $absolutePath);
            return false;
        }

        return github_sync_upsert_text_file($publicPath, $contents, $commitMessage);
    }
}

if (!function_exists('github_sync_delete_file')) {
    function github_sync_delete_file(string $publicPath, string $commitMessage): bool
    {
        if (!github_sync_is_enabled()) {
            return false;
        }

        if (!github_sync_is_configured()) {
            github_sync_record_operation('delete', $publicPath, false, 'GitHub sync is enabled but not configured');
            return false;
        }

        try {
            $sha = github_sync_get_sha($publicPath);

            if ($sha === null || $sha === '') {
                github_sync_record_operation('delete', $publicPath, true, 'File already absent on GitHub');
                return true;
            }

            $payload = [
                'message' => $commitMessage,
                'sha' => $sha,
                'branch' => github_sync_repo_branch(),
            ];

            $response = github_sync_request('DELETE', $publicPath, $payload);

            if ($response['status'] < 200 || $response['status'] >= 300) {
                $message = is_array($response['json']) ? (string)($response['json']['message'] ?? '') : 'Unknown GitHub delete error';
                throw new RuntimeException($message);
            }

            github_sync_record_operation('delete', $publicPath, true, 'Deleted from GitHub');
            return true;
        } catch (Throwable $e) {
            github_sync_record_operation('delete', $publicPath, false, $e->getMessage());
            error_log('[GitHub Sync] delete failed for ' . $publicPath . ': ' . $e->getMessage());
            return false;
        }
    }
}
