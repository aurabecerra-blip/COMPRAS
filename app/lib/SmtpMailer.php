<?php
class SmtpMailer
{
    public function send(string $to, string $subject, string $htmlBody, array $config): void
    {
        $host = trim($config['host'] ?? '');
        $port = (int)($config['port'] ?? 0);
        $security = strtolower(trim($config['security'] ?? 'none'));
        $username = trim($config['username'] ?? '');
        $password = $config['password'] ?? '';
        $fromEmail = trim($config['from_email'] ?? '');
        $fromName = trim($config['from_name'] ?? '');

        if ($host === '' || $port <= 0) {
            throw new RuntimeException('Configuración SMTP incompleta.');
        }

        if ($fromEmail === '' && $username !== '') {
            $fromEmail = $username;
        }

        if ($fromEmail === '') {
            throw new RuntimeException('Correo remitente no configurado.');
        }

        $this->validateSecurityPort($port, $security);

        $address = ($security === 'ssl') ? 'ssl://' . $host . ':' . $port : $host . ':' . $port;
        $socket = stream_socket_client($address, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            $details = $errstr !== '' ? $errstr : 'sin mensaje';
            throw new RuntimeException('No se pudo conectar al servidor SMTP (' . $errno . '): ' . $details);
        }

        $this->expect($socket, [220], 'saludo inicial');
        $this->command($socket, 'EHLO localhost', [250]);

        if ($security === 'tls') {
            $this->command($socket, 'STARTTLS', [220]);
            $cryptoError = $this->withErrorCapture(fn () => stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT));
            if ($cryptoError['result'] !== true) {
                $detail = $cryptoError['message'] !== '' ? $cryptoError['message'] : 'handshake fallido';
                throw new RuntimeException('Fallo en STARTTLS: ' . $detail);
            }
            $this->command($socket, 'EHLO localhost', [250]);
        }

        if ($username !== '') {
            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode($username), [334]);
            $this->command($socket, base64_encode($password), [235]);
        }

        $this->command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        $this->command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        $this->command($socket, 'DATA', [354]);

        $encodedName = $fromName !== '' ? $this->encodeHeader($fromName) : '';
        $fromHeader = $encodedName !== '' ? $encodedName . ' <' . $fromEmail . '>' : $fromEmail;
        $headers = [
            'From: ' . $fromHeader,
            'To: ' . $to,
            'Subject: ' . $this->encodeHeader($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
        ];

        $body = $this->prepareBody($htmlBody);
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
        $this->write($socket, $payload . "\r\n");
        $this->expect($socket, [250], 'envío del mensaje');
        $this->command($socket, 'QUIT', [221, 250]);
        fclose($socket);
    }

    private function command($socket, string $command, array $expected): void
    {
        $this->write($socket, $command . "\r\n");
        $this->expect($socket, $expected, 'comando ' . $command);
    }

    private function write($socket, string $data): void
    {
        $written = fwrite($socket, $data);
        if ($written === false) {
            throw new RuntimeException('No se pudo escribir en la conexión SMTP.');
        }
    }

    private function expect($socket, array $expected, string $context): void
    {
        $response = '';
        while (!feof($socket)) {
            $line = fgets($socket, 515);
            if ($line === false) {
                break;
            }
            $response .= $line;
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }

        if ($response === '') {
            throw new RuntimeException('No se recibió respuesta SMTP durante ' . $context . ' (esperado ' . implode('/', $expected) . ').');
        }

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expected, true)) {
            $message = trim($response);
            throw new RuntimeException('Error SMTP ' . $code . ': ' . $message);
        }
    }

    private function encodeHeader(string $value): string
    {
        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($value, 'UTF-8');
        }
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function prepareBody(string $htmlBody): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $htmlBody);
        $normalized = str_replace("\n.", "\n..", $normalized);
        return str_replace("\n", "\r\n", $normalized);
    }

    private function validateSecurityPort(int $port, string $security): void
    {
        $security = $security !== '' ? $security : 'none';
        $allowed = [
            465 => ['ssl'],
            587 => ['tls'],
            25 => ['none'],
        ];
        if (!isset($allowed[$port])) {
            return;
        }
        if (!in_array($security, $allowed[$port], true)) {
            $expected = strtoupper(implode('/', $allowed[$port]));
            $current = strtoupper($security);
            throw new RuntimeException(
                'Configuración SMTP inválida: el puerto ' . $port . ' requiere seguridad ' . $expected . ' (actual: ' . $current . ').'
            );
        }
    }

    private function withErrorCapture(callable $callback): array
    {
        $message = '';
        $handler = static function (int $errno, string $errstr) use (&$message): bool {
            $message = $errstr;
            return true;
        };
        set_error_handler($handler);
        try {
            $result = $callback();
        } finally {
            restore_error_handler();
        }
        return [
            'result' => $result,
            'message' => $message,
        ];
    }
}
