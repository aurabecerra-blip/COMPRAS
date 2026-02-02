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

        $address = ($security === 'ssl') ? 'ssl://' . $host . ':' . $port : $host . ':' . $port;
        $socket = stream_socket_client($address, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
        if (!$socket) {
            throw new RuntimeException('No se pudo conectar al servidor SMTP: ' . $errstr);
        }

        $this->expect($socket, [220]);
        $this->command($socket, 'EHLO localhost', [250]);

        if ($security === 'tls') {
            $this->command($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('No se pudo iniciar TLS con el servidor SMTP.');
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
        $this->expect($socket, [250]);
        $this->command($socket, 'QUIT', [221, 250]);
        fclose($socket);
    }

    private function command($socket, string $command, array $expected): void
    {
        $this->write($socket, $command . "\r\n");
        $this->expect($socket, $expected);
    }

    private function write($socket, string $data): void
    {
        $written = fwrite($socket, $data);
        if ($written === false) {
            throw new RuntimeException('No se pudo escribir en la conexión SMTP.');
        }
    }

    private function expect($socket, array $expected): void
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
            throw new RuntimeException('Respuesta SMTP vacía.');
        }

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expected, true)) {
            throw new RuntimeException('Error SMTP: ' . trim($response));
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
}
