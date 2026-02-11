<?php
class SupplierEvaluationPdfBuilder
{
    public function __construct(private ?SettingsRepository $settings = null, private ?CompanyRepository $companies = null)
    {
    }

    public function generate(array $evaluation): string
    {
        $uploadDir = __DIR__ . '/../../public/uploads/evaluations';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = sprintf('evaluacion_proveedor_%d_%s.pdf', (int)$evaluation['supplier_id'], date('Ymd_His'));
        $absolutePath = $uploadDir . '/' . $filename;

        $branding = $this->brandData();
        [$primaryR, $primaryG, $primaryB] = $this->hexToRgb($branding['primary_color']);
        [$secondaryR, $secondaryG, $secondaryB] = $this->hexToRgb($branding['secondary_color']);
        [$logoData, $logoWidth, $logoHeight] = $this->logoJpegData($branding['logo_path']);

        $stream = [];
        $stream[] = '0.96 0.97 1.00 rg';
        $stream[] = '30 730 535 95 re f';

        if ($logoData !== '') {
            $stream[] = 'q';
            $stream[] = '90 0 0 36 42 772 cm';
            $stream[] = '/Im1 Do';
            $stream[] = 'Q';
        }

        $documentTitle = strtoupper((string)($evaluation['document_title'] ?? 'EVALUACIÓN DE PROVEEDOR'));
        $this->drawText($stream, 140, 805, 11, $branding['company_name'], true, [$primaryR, $primaryG, $primaryB]);
        $this->drawText($stream, 140, 790, 9, 'NIT: ' . $branding['company_nit'], false, [0.20, 0.24, 0.32]);
        $this->drawText($stream, 410, 805, 9, 'Versión: 02', false, [0.20, 0.24, 0.32]);
        $this->drawText($stream, 410, 792, 9, 'Fecha: ' . date('d/m/Y'), false, [0.20, 0.24, 0.32]);
        $this->drawText($stream, 410, 779, 9, 'Evaluador: ' . (string)($evaluation['evaluator_name'] ?? 'N/D'), false, [0.20, 0.24, 0.32]);
        $this->drawCenteredText($stream, 300, 758, 13, $documentTitle, true, [$primaryR, $primaryG, $primaryB]);

        $this->drawText($stream, 34, 744, 8, 'Proveedor: ' . (string)($evaluation['supplier_name'] ?? 'N/D'), true, [0.11, 0.19, 0.42]);
        $this->drawText($stream, 250, 744, 8, 'NIT: ' . (string)($evaluation['supplier_nit'] ?? 'N/D'), false, [0.20, 0.24, 0.32]);
        $this->drawText($stream, 380, 744, 8, 'Servicio: ' . (string)($evaluation['supplier_service'] ?? 'N/D'), false, [0.20, 0.24, 0.32]);

        $tableX = 30;
        $tableTop = 708;
        $headerHeight = 24;
        $rowHeight = 24;
        $columns = [150, 75, 230, 80];
        $headers = ['CRITERIO', 'PONDERACIÓN (%)', 'DESCRIPCIÓN / ESCALA', 'PUNTAJE'];

        $stream[] = sprintf('%.3f %.3f %.3f rg', $primaryR, $primaryG, $primaryB);
        $stream[] = sprintf('%.2f %.2f %.2f %.2f re f', $tableX, $tableTop - $headerHeight, array_sum($columns), $headerHeight);

        $x = $tableX;
        foreach ($headers as $index => $header) {
            $this->drawText($stream, $x + 4, $tableTop - 16, 8, $header, true, [1, 1, 1]);
            $x += $columns[$index];
        }

        $y = $tableTop - $headerHeight;
        foreach (($evaluation['details'] ?? []) as $index => $detail) {
            $y -= $rowHeight;
            $fill = $index % 2 === 1 ? [0.99, 0.93, 0.96] : [1, 1, 1];
            $stream[] = sprintf('%.2f %.2f %.2f rg', $fill[0], $fill[1], $fill[2]);
            $stream[] = sprintf('%.2f %.2f %.2f %.2f re f', $tableX, $y, array_sum($columns), $rowHeight);

            $criterionName = (string)($detail['criterion_name'] ?? '');
            $score = (int)($detail['score'] ?? 0);
            $cells = [
                $criterionName,
                (string)$this->criterionWeight($criterionName),
                (string)($detail['option_label'] ?? ''),
                (string)$score,
            ];

            $cx = $tableX;
            foreach ($cells as $c => $cell) {
                $this->drawClippedText($stream, $cx + 4, $y + 9, 8, $cell, $columns[$c] - 8, [0.18, 0.21, 0.27], $c === 2);
                $cx += $columns[$c];
            }
        }

        $y -= $rowHeight;
        $stream[] = sprintf('%.3f %.3f %.3f rg', $primaryR, $primaryG, $primaryB);
        $stream[] = sprintf('%.2f %.2f %.2f %.2f re f', $tableX, $y, array_sum($columns), $rowHeight);
        $this->drawText($stream, $tableX + 4, $y + 9, 8, 'TOTAL PUNTAJE', true, [1, 1, 1]);
        $this->drawText($stream, $tableX + $columns[0] + $columns[1] + 20, $y + 9, 8, (string)((int)($evaluation['total_score'] ?? 0)), true, [1, 1, 1]);
        $this->drawText($stream, $tableX + $columns[0] + $columns[1] + $columns[2] + 12, $y + 9, 8, '/ 100', true, [1, 1, 1]);

        $gridBottom = $y;
        $stream[] = '0.84 0.87 0.92 RG';
        $stream[] = '0.5 w';
        $stream[] = sprintf('%.2f %.2f %.2f %.2f re S', $tableX, $gridBottom, array_sum($columns), ($tableTop - $gridBottom));
        $xLine = $tableX;
        foreach ($columns as $width) {
            $xLine += $width;
            $stream[] = sprintf('%.2f %.2f m %.2f %.2f l S', $xLine, $gridBottom, $xLine, $tableTop);
        }

        $statusY = $gridBottom - 52;
        $stream[] = sprintf('%.3f %.3f %.3f rg', $secondaryR, $secondaryG, $secondaryB);
        $stream[] = sprintf('%.2f %.2f %.2f %.2f re f', 30, $statusY, 535, 34);
        $this->drawText($stream, 42, $statusY + 20, 10, 'RESULTADO: ' . (string)($evaluation['status_label'] ?? 'N/D'), true, [0.38, 0.07, 0.24]);

        $obsY = $statusY - 66;
        $stream[] = '0.95 0.97 1.00 rg';
        $stream[] = sprintf('%.2f %.2f %.2f %.2f re f', 30, $obsY, 535, 52);
        $this->drawText($stream, 42, $obsY + 35, 9, 'COMENTARIOS / OBSERVACIONES', true, [0.11, 0.19, 0.42]);
        $this->drawClippedText($stream, 42, $obsY + 18, 8, (string)($evaluation['observations'] ?? 'Sin observaciones registradas.'), 515, [0.18, 0.21, 0.27]);

        $pdf = $this->buildPdf(implode("\n", $stream), $logoData, $logoWidth, $logoHeight);
        file_put_contents($absolutePath, $pdf);

        return '/uploads/evaluations/' . $filename;
    }

    private function extractScale(string $label, int $score): string
    {
        if ($score >= 80) {
            return 'Alto';
        }
        if ($score >= 50) {
            return 'Medio';
        }
        return $label !== '' ? 'Bajo' : 'N/A';
    }

    private function brandData(): array
    {
        $activeCompany = $this->companies?->active() ?? [];

        return [
            'company_name' => trim((string)($activeCompany['name'] ?? ($this->settings?->get('company_name', 'AOS') ?? 'AOS'))) ?: 'AOS',
            'company_nit' => trim((string)($activeCompany['nit'] ?? ($this->settings?->get('company_nit', '900.635.119-8') ?? '900.635.119-8'))) ?: '900.635.119-8',
            'logo_path' => trim((string)($activeCompany['logo_path'] ?? ($this->settings?->get('brand_logo_path', 'assets/logo_aos.png') ?? 'assets/logo_aos.png'))) ?: 'assets/logo_aos.png',
            'primary_color' => trim((string)($activeCompany['primary_color'] ?? ($this->settings?->get('brand_primary_color', '#1E3A8A') ?? '#1E3A8A'))) ?: '#1E3A8A',
            'secondary_color' => trim((string)($activeCompany['secondary_color'] ?? ($this->settings?->get('brand_accent_color', '#F8C8D8') ?? '#F8C8D8'))) ?: '#F8C8D8',
        ];
    }

    private function criterionWeight(string $criterionName): int
    {
        $normalized = mb_strtolower(trim($criterionName));
        if (str_contains($normalized, 'tiempo') || str_contains($normalized, 'entrega')) {
            return 25;
        }
        if (str_contains($normalized, 'calidad')) {
            return 25;
        }
        if (str_contains($normalized, 'postventa')) {
            return 15;
        }
        if (str_contains($normalized, 'sqr')) {
            return 15;
        }
        if (str_contains($normalized, 'document')) {
            return 20;
        }

        return 0;
    }

    private function hexToRgb(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) !== 6) {
            return [0.12, 0.23, 0.54];
        }

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    private function logoJpegData(string $logoSettingPath): array
    {
        $candidates = [];
        $normalized = ltrim($logoSettingPath, '/');
        $candidates[] = __DIR__ . '/../../public/' . $normalized;
        $candidates[] = __DIR__ . '/../../' . $normalized;
        $candidates[] = __DIR__ . '/../../assets/logo_aos.png';

        $logoPath = '';
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $logoPath = $candidate;
                break;
            }
        }

        if ($logoPath === '' || !function_exists('imagecreatefromstring')) {
            return ['', 1, 1];
        }

        $raw = file_get_contents($logoPath);
        if ($raw === false) {
            return ['', 1, 1];
        }

        $image = @imagecreatefromstring($raw);
        if (!$image || !function_exists('imagejpeg') || !function_exists('imagesx') || !function_exists('imagesy')) {
            if ($image && function_exists('imagedestroy')) {
                imagedestroy($image);
            }
            return ['', 1, 1];
        }

        ob_start();
        imagejpeg($image, null, 90);
        $jpegData = ob_get_clean() ?: '';
        $width = max(1, imagesx($image));
        $height = max(1, imagesy($image));

        if (function_exists('imagedestroy')) {
            imagedestroy($image);
        }

        return [$jpegData, $width, $height];
    }

    private function drawText(array &$stream, float $x, float $y, int $size, string $text, bool $bold = false, array $rgb = [0, 0, 0]): void
    {
        $font = $bold ? '/F2' : '/F1';
        $stream[] = sprintf('%.2f %.2f %.2f rg', $rgb[0], $rgb[1], $rgb[2]);
        $stream[] = 'BT';
        $stream[] = sprintf('%s %d Tf', $font, $size);
        $stream[] = sprintf('1 0 0 1 %.2f %.2f Tm', $x, $y);
        $stream[] = '(' . $this->pdfEscape($text) . ') Tj';
        $stream[] = 'ET';
    }

    private function drawCenteredText(array &$stream, float $centerX, float $y, int $size, string $text, bool $bold = false, array $rgb = [0, 0, 0]): void
    {
        $width = $this->textWidth($text, $size, $bold);
        $this->drawText($stream, $centerX - ($width / 2), $y, $size, $text, $bold, $rgb);
    }

    private function drawClippedText(array &$stream, float $x, float $y, int $size, string $text, float $maxWidth, array $rgb = [0, 0, 0], bool $center = false): void
    {
        $content = $this->truncateText($text, $size, $maxWidth);
        if ($center) {
            $x += max(0, ($maxWidth - $this->textWidth($content, $size)) / 2);
        }
        $this->drawText($stream, $x, $y, $size, $content, false, $rgb);
    }

    private function truncateText(string $text, int $size, float $maxWidth): string
    {
        $text = trim($text);
        if ($this->textWidth($text, $size) <= $maxWidth) {
            return $text;
        }

        while ($text !== '' && $this->textWidth($text . '…', $size) > $maxWidth) {
            $text = mb_substr($text, 0, max(0, mb_strlen($text) - 1));
        }

        return rtrim($text) . '…';
    }

    private function textWidth(string $text, int $size, bool $bold = false): float
    {
        $multiplier = $bold ? 0.56 : 0.52;
        return mb_strlen($text) * ($size * $multiplier);
    }

    private function buildPdf(string $stream, string $logoData, int $logoWidth, int $logoHeight): string
    {
        $hasLogo = $logoData !== '';
        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $resources = '/Font << /F1 4 0 R /F2 6 0 R >>';
        if ($hasLogo) {
            $resources .= ' /XObject << /Im1 7 0 R >>';
        }

        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << {$resources} >> /Contents 5 0 R >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n";
        $objects[] = "5 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream\nendobj\n";
        $objects[] = "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\nendobj\n";

        if ($hasLogo) {
            $objects[] = "7 0 obj\n<< /Type /XObject /Subtype /Image /Width {$logoWidth} /Height {$logoHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($logoData) . " >>\nstream\n" . $logoData . "\nendstream\nendobj\n";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
        return $pdf;
    }

    private function pdfEscape(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        $value = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) ?: $value;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
