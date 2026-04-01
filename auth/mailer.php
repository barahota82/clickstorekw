<?php
declare(strict_types=1);

function smtp_send_mail(
    string $toEmail,
    string $toName,
    string $subject,
    string $bodyText
): bool {

    // ✅ استخدام السيرفر الداخلي (MailEnable في Plesk)
    $smtpHost = 'localhost';
    $smtpPort = 25;
    $smtpUser = 'info@clickstorekw.com';
    $smtpPass = 'ClickStore@2026#KW';

    $fromEmail = 'info@clickstorekw.com';
    $fromName  = 'Click Store KW';

    // ✅ اتصال عادي بدون SSL أو TLS
    $socket = stream_socket_client(
        "tcp://{$smtpHost}:{$smtpPort}",
        $errno,
        $errstr,
        30
    );

    if (!$socket) {
        throw new RuntimeException("SMTP connection failed: {$errstr} ({$errno})");
    }

    stream_set_timeout($socket, 30);

    smtp_expect($socket, [220]);

    smtp_command($socket, 'EHLO localhost', [250]);

    // ⚠️ MailEnable غالبًا لا يحتاج AUTH داخلي
    // لذلك لن نستخدم AUTH هنا

    smtp_command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
    smtp_command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
    smtp_command($socket, 'DATA', [354]);

    $headers = [];
    $headers[] = 'Date: ' . date('r');
    $headers[] = 'From: ' . mime_header($fromName) . " <{$fromEmail}>";
    $headers[] = 'To: ' . mime_header($toName) . " <{$toEmail}>";
    $headers[] = 'Subject: ' . mime_header($subject);
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';

    $message = implode("\r\n", $headers) . "\r\n\r\n" . normalize_smtp_body($bodyText) . "\r\n.";

    fwrite($socket, $message . "\r\n");
    smtp_expect($socket, [250]);

    smtp_command($socket, 'QUIT', [221]);
    fclose($socket);

    return true;
}

function smtp_command($socket, string $command, array $expectedCodes): void
{
    fwrite($socket, $command . "\r\n");
    smtp_expect($socket, $expectedCodes);
}

function smtp_expect($socket, array $expectedCodes): void
{
    $response = '';

    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;

        if (preg_match('/^\d{3}\s/', $line)) {
            break;
        }
    }

    if ($response === '') {
        throw new RuntimeException('SMTP empty response.');
    }

    $code = (int) substr($response, 0, 3);

    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP error: ' . trim($response));
    }
}

function mime_header(string $text): string
{
    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

function normalize_smtp_body(string $body): string
{
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $lines = explode("\n", $body);

    foreach ($lines as &$line) {
        if (isset($line[0]) && $line[0] === '.') {
            $line = '.' . $line;
        }
    }

    return implode("\r\n", $lines);
}
