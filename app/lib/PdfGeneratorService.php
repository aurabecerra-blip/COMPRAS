<?php
class PdfGeneratorService
{
    public function __construct(private ?SettingsRepository $settings = null)
    {
    }

    public function generateProviderSelectionPdf(array $context): string
    {
        $prId = (int)($context['purchase_request']['id'] ?? 0);
        $dir = __DIR__ . '/../../public/storage/seleccion_proveedor/' . $prId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = 'analisis_seleccion_' . date('Ymd_His') . '.pdf';
        $fullPath = $dir . '/' . $filename;

        $branding = $this->brandData();
        [$logoData, $logoWidth, $logoHeight] = $this->logoJpegData($branding['logo_path']);

        $scores = array_values($context['scores'] ?? []);
        $criteria = $context['criteria'] ?? [];
        $topProviders = array_slice($scores, 0, 3);
        while (count($topProviders) < 3) {
            $topProviders[] = [];
        }

        $rows = [];
        foreach ($criteria as $code => $criterion) {
            $rows[] = [
                'label' => (string)($criterion['label'] ?? strtoupper((string)$code)),
                'ponderacion' => (string)($criterion['ponderacion'] ?? ''),
                'descripcion' => (string)($criterion['descripcion'] ?? ''),
                'values' => [
                    (string)($topProviders[0][$code . '_score'] ?? '-'),
                    (string)($topProviders[1][$code . '_score'] ?? '-'),
                    (string)($topProviders[2][$code . '_score'] ?? '-'),
                ],
            ];
        }

        $totals = [
            (string)($topProviders[0]['total_score'] ?? 0),
            (string)($topProviders[1]['total_score'] ?? 0),
            (string)($topProviders[2]['total_score'] ?? 0),
        ];

        $providerNames = [
            (string)($topProviders[0]['provider_name'] ?? 'N/D'),
            (string)($topProviders[1]['provider_name'] ?? 'N/D'),
            (string)($topProviders[2]['provider_name'] ?? 'N/D'),
        ];

        $winner = (string)($context['winner_name'] ?? 'N/D');
        $observations = trim((string)($context['observations'] ?? ''));

        $stream = [];
        $stream[] = '0.95 0.96 0.98 rg';
        $stream[] = '30 730 535 95 re f';

        if ($logoData !== '') {
            $stream[] = 'q';
            $stream[] = '90 0 0 36 42 772 cm';
            $stream[] = '/Im1 Do';
            $stream[] = 'Q';
        }

        $this->drawText($stream, 140, 804, 11, $branding['company_name'], true, [0.11, 0.19, 0.42]);
        $this->drawText($stream, 140, 788, 9, 'NIT: ' . $branding['company_nit'], false, [0.20, 0.24, 0.32]);
        $this->drawCenteredText($stream, 300, 770, 13, 'ANÁLISIS DE SELECCIÓN DE PROVEEDORES', true, [0.11, 0.19, 0.42]);
        $this->drawText($stream, 430, 804, 9, 'Versión: 02', false, [0.20, 0.24, 0.32]);
        $this->drawText($stream, 430, 790, 9, 'Fecha: ' . date('d/m/Y'), false, [0.20, 0.24, 0.32]);
        $this->drawText($stream, 430, 776, 9, 'Solicitud (PR): #' . $prId, false, [0.20, 0.24, 0.32]);

        $tableX = 30;
        $tableTop = 708;
        $headerHeight = 26;
        $rowHeight = 24;
        $columns = [120, 67, 67, 67, 80, 134];
        $headers = ['CRITERIO', 'PROVEEDOR 1', 'PROVEEDOR 2', 'PROVEEDOR 3', 'PONDERACIÓN (%)', 'DESCRIPCIÓN / ESCALA'];

        $stream[] = '0.11 0.19 0.42 rg';
        $stream[] = sprintf('%.2f %.2f %.2f %.2f re f', $tableX, $tableTop - $headerHeight, array_sum($columns), $headerHeight);

        $cursorX = $tableX;
        foreach ($headers as $i => $header) {
            $this->drawText($stream, $cursorX + 4, $tableTop - 17, 8, $header, true, [1, 1, 1]);
            $cursorX += $columns[$i];
        }

        $y = $tableTop - $headerHeight;
        foreach ($rows as $index => $row) {
            $y -= $rowHeight;
            $isAlt = $index % 2 === 1;
            $fill = $isAlt ? [0.99, 0.93, 0.96] : [1, 1, 1];
            $stream[] = sprintf('%.2f %.2f %.2f rg', $fill[0], $fill[1], $fill[2]);
            $stream[] = sprintf('%.2f %.2f %.2f %.2f re f', $tableX, $y, array_sum($columns), $rowHeight);

            $cells = [
                $row['label'],
                $row['values'][0],
                $row['values'][1],
                $row['values'][2],
                $row['ponderacion'],
                $row['descripcion'],
            ];
            $cx = $tableX;
            foreach ($cells as $c => $cell) {
                $this->drawClippedText($stream, $cx + 4, $y + 9, 8, $cell, $columns[$c] - 8, [0.18, 0.21, 0.27], $c !== 0 && $c < 5);
                $cx += $columns[$c];
            }
        }

        $y -= $rowHeight;
        $stream[] = '0.08 0.15 0.34 rg';
        $stream[] = sprintf('%.2f %.2f %.2f %.2f re f', $tableX, $y, array_sum($columns), $rowHeight);
        $cx = $tableX;
        $totalCells = ['TOTAL PUNTAJE', $totals[0], $totals[1], $totals[2], '100', 'Suma automática'];
        foreach ($totalCells as $c => $cell) {
            $this->drawClippedText($stream, $cx + 4, $y + 9, 8, $cell, $columns[$c] - 8, [1, 1, 1], $c !== 0 && $c < 5, true);
            $cx += $columns[$c];
        }

        $gridBottom = $y;
        $stream[] = '0.84 0.87 0.92 RG';
        $stream[] = '0.5 w';
        $stream[] = sprintf('%.2f %.2f %.2f %.2f re S', $tableX, $gridBottom, array_sum($columns), ($tableTop - $gridBottom));
        $xLine = $tableX;
        foreach ($columns as $width) {
            $xLine += $width;
            $stream[] = sprintf('%.2f %.2f m %.2f %.2f l S', $xLine, $gridBottom, $xLine, $tableTop);
        }

        $winnerY = $gridBottom - 52;
        $stream[] = '0.95 0.69 0.79 rg';
        $stream[] = sprintf('%.2f %.2f %.2f %.2f re f', 30, $winnerY, 535, 34);
        $this->drawText($stream, 42, $winnerY + 20, 10, 'GANADOR: ' . $winner, true, [0.38, 0.07, 0.24]);
        $this->drawText($stream, 42, $winnerY + 8, 8, 'PROVEEDORES EVALUADOS: ' . implode(' | ', $providerNames), false, [0.38, 0.07, 0.24]);

        $obsY = $winnerY - 66;
        $stream[] = '0.95 0.97 1.00 rg';
        $stream[] = sprintf('%.2f %.2f %.2f %.2f re f', 30, $obsY, 535, 52);
        $this->drawText($stream, 42, $obsY + 35, 9, 'COMENTARIOS / OBSERVACIONES', true, [0.11, 0.19, 0.42]);
        $this->drawClippedText($stream, 42, $obsY + 18, 8, $observations !== '' ? $observations : 'Sin observaciones registradas.', 515, [0.18, 0.21, 0.27]);

        $pdf = $this->buildPdf(implode("\n", $stream), $logoData, $logoWidth, $logoHeight);
        file_put_contents($fullPath, $pdf);

        return '/storage/seleccion_proveedor/' . $prId . '/' . $filename;
    }

    private function brandData(): array
    {
        $company = $this->settings?->get('company_name', 'AOS') ?? 'AOS';
        $nit = $this->settings?->get('company_nit', '900.000.000-0') ?? '900.000.000-0';
        $logoPath = $this->settings?->get('brand_logo_path', 'assets/logo_aos.png') ?? 'assets/logo_aos.png';

        return [
            'company_name' => trim($company) !== '' ? trim($company) : 'AOS',
            'company_nit' => trim($nit) !== '' ? trim($nit) : '900.000.000-0',
            'logo_path' => trim($logoPath) !== '' ? trim($logoPath) : 'assets/logo_aos.png',
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

    private function drawClippedText(array &$stream, float $x, float $y, int $size, string $text, float $maxWidth, array $rgb = [0, 0, 0], bool $center = false, bool $bold = false): void
    {
        $content = $this->truncateText($text, $size, $maxWidth, $bold);
        if ($center) {
            $contentWidth = $this->textWidth($content, $size, $bold);
            $x += max(0, ($maxWidth - $contentWidth) / 2);
        }
        $this->drawText($stream, $x, $y, $size, $content, $bold, $rgb);
    }

    private function truncateText(string $text, int $size, float $maxWidth, bool $bold = false): string
    {
        $text = trim($text);
        if ($this->textWidth($text, $size, $bold) <= $maxWidth) {
            return $text;
        }

        $suffix = '…';
        while ($text !== '' && $this->textWidth($text . $suffix, $size, $bold) > $maxWidth) {
            $text = mb_substr($text, 0, max(0, mb_strlen($text) - 1));
        }

        return rtrim($text) . $suffix;
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
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
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
