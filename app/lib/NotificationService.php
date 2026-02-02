<?php
class NotificationService
{
    public function __construct(
        private SettingsRepository $settings,
        private NotificationTypeRepository $types,
        private NotificationLogRepository $logs,
        private UserRepository $users,
        private SmtpMailer $mailer
    ) {
    }

    public function send(string $typeCode, string $subject, string $htmlBody, array $context = []): void
    {
        if (!$this->isEnabled('notifications_enabled')) {
            return;
        }

        $type = $this->types->findByCode($typeCode);
        if (!$type || !$type['is_active']) {
            return;
        }

        $channel = $type['channel'] ?? 'email';
        if ($channel !== 'email' || !$this->isEnabled('notifications_email_enabled')) {
            return;
        }

        $roleRecipients = $this->users->emailsByRoles($type['roles'] ?? []);
        $extraRecipients = $context['recipients'] ?? [];
        $recipients = array_merge($roleRecipients, $extraRecipients);
        $recipients = array_values(array_unique(array_filter(array_map('trim', $recipients))));
        if (empty($recipients)) {
            return;
        }

        $smtpConfig = $this->smtpConfig();

        foreach ($recipients as $recipient) {
            $status = 'enviado';
            $error = null;
            try {
                $this->mailer->send($recipient, $subject, $htmlBody, $smtpConfig);
            } catch (Throwable $e) {
                $status = 'error';
                $error = $e->getMessage();
            }
            $this->logs->add([
                'type_id' => $type['id'],
                'channel' => $channel,
                'recipient' => $recipient,
                'status' => $status,
                'error_message' => $error,
            ]);
        }
    }

    public function sendTestEmail(string $recipient): void
    {
        if (!$this->isEnabled('notifications_enabled') || !$this->isEnabled('notifications_email_enabled')) {
            return;
        }

        $type = $this->types->findByCode('test_email');
        if (!$type || !$type['is_active']) {
            return;
        }

        $subject = 'Prueba de correo - Configuración de notificaciones';
        $body = '<p>Este es un correo de prueba desde el módulo de notificaciones.</p>';
        $this->send('test_email', $subject, $body, ['recipients' => [$recipient]]);
    }

    private function isEnabled(string $key): bool
    {
        return $this->settings->get($key, '0') === '1';
    }

    private function smtpConfig(): array
    {
        return [
            'host' => $this->settings->get('notifications_smtp_host', ''),
            'port' => (int)($this->settings->get('notifications_smtp_port', '0') ?: 0),
            'security' => $this->settings->get('notifications_smtp_security', 'none'),
            'username' => $this->settings->get('notifications_smtp_user', ''),
            'password' => $this->settings->get('notifications_smtp_password', ''),
            'from_email' => $this->settings->get('notifications_from_email', ''),
            'from_name' => $this->settings->get('notifications_from_name', ''),
        ];
    }
}
