<?php
class Mailer
{
    public function __construct(private array $config)
    {
        if (!empty($config['smtp_host'])) {
            ini_set('SMTP', $config['smtp_host']);
        }
        if (!empty($config['smtp_port'])) {
            ini_set('smtp_port', (string)$config['smtp_port']);
        }
        if (!empty($config['from_email'])) {
            ini_set('sendmail_from', $config['from_email']);
        }
    }

    public function send(array|string $to, string $subject, string $htmlBody): void
    {
        $recipients = is_array($to) ? $to : [$to];
        $recipients = array_values(array_filter(array_map('trim', $recipients), fn($e) => $e !== ''));
        if (empty($recipients)) {
            return;
        }

        $headers = [];
        $fromEmail = $this->config['from_email'] ?? 'no-reply@example.com';
        $fromName = $this->config['from_name'] ?? 'Notificaciones';
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headersStr = implode("\r\n", $headers);

        foreach ($recipients as $email) {
            @mail($email, $subject, $htmlBody, $headersStr);
        }

        $this->log($recipients, $subject, $htmlBody);
    }

    private function log(array $recipients, string $subject, string $htmlBody): void
    {
        $logPath = $this->config['log_path'] ?? null;
        if (!$logPath) {
            return;
        }
        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $entry = sprintf(
            "[%s] To: %s\nSubject: %s\n%s\n\n",
            date('c'),
            implode(', ', $recipients),
            $subject,
            strip_tags($htmlBody)
        );
        file_put_contents($logPath, $entry, FILE_APPEND);
    }
}
