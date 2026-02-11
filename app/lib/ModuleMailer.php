<?php
class ModuleMailer
{
    public function sendWithAttachment(string $to, string $subject, string $htmlBody, string $attachmentAbsolutePath, string $attachmentName = 'documento.pdf'): bool
    {
        if (!is_file($attachmentAbsolutePath)) {
            throw new RuntimeException('El adjunto no existe en ruta esperada.');
        }

        $boundary = 'boundary_' . md5((string)microtime(true));
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
            'From: Compras AOS <no-reply@aossas.com>',
        ];

        $attachmentData = chunk_split(base64_encode((string)file_get_contents($attachmentAbsolutePath)));
        $message = '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $htmlBody . "\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: application/pdf; name=\"" . $attachmentName . "\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "Content-Disposition: attachment; filename=\"" . $attachmentName . "\"\r\n\r\n";
        $message .= $attachmentData . "\r\n";
        $message .= '--' . $boundary . '--';

        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
}
