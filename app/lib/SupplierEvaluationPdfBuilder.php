<?php
class SupplierEvaluationPdfBuilder
{
    public function generate(array $evaluation): string
    {
        $uploadDir = __DIR__ . '/../../public/uploads/evaluations';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = sprintf('evaluacion_proveedor_%d_%s.pdf', (int)$evaluation['supplier_id'], date('Ymd_His'));
        $absolutePath = $uploadDir . '/' . $filename;

        $lines = [
            'Evaluación de proveedor',
            '',
            'Proveedor: ' . ($evaluation['supplier_name'] ?? 'N/D'),
            'NIT: ' . ($evaluation['supplier_nit'] ?? 'N/D'),
            'Servicio: ' . ($evaluation['supplier_service'] ?? 'N/D'),
            'Fecha: ' . ($evaluation['evaluation_date'] ?? date('Y-m-d H:i:s')),
            'Evaluador: ' . ($evaluation['evaluator_name'] ?? 'N/D'),
            'Puntaje total: ' . (int)($evaluation['total_score'] ?? 0) . ' / 100',
            'Estado: ' . ($evaluation['status_label'] ?? 'N/D'),
            '',
            'Observaciones:',
            (string)($evaluation['observations'] ?? 'Sin observaciones'),
            '',
            'Detalle de criterios:',
        ];

        foreach (($evaluation['details'] ?? []) as $detail) {
            $lines[] = '- ' . ($detail['criterion_name'] ?? '') . ': ' . ($detail['option_label'] ?? '') . ' (' . (int)($detail['score'] ?? 0) . ' pts)';
        }

        [$logoData, $logoWidth, $logoHeight] = $this->logoJpegData();

        $content = "";
        if ($logoData !== '') {
            $content .= "q\n120 0 0 45 40 780 cm\n/Im1 Do\nQ\n";
        }
        $content .= "BT\n/F1 11 Tf\n40 740 Td\n";
        foreach ($lines as $index => $line) {
            $escaped = $this->pdfEscape($line);
            if ($index === 0) {
                $content .= '(' . $escaped . ") Tj\n";
            } else {
                $content .= "0 -16 Td\n(" . $escaped . ") Tj\n";
            }
        }
        $content .= "ET";

        $pdf = $this->buildPdf($content, $logoData, $logoWidth, $logoHeight);
        file_put_contents($absolutePath, $pdf);

        return '/uploads/evaluations/' . $filename;
    }

    private function logoJpegData(): array
    {
        $logoPath = __DIR__ . '/../../assets/logo_aos.png';
        if (!file_exists($logoPath) || !function_exists('imagecreatefrompng')) {
            return ['', 1, 1];
        }

        $image = @imagecreatefrompng($logoPath);
        if (!$image) {
            return ['', 1, 1];
        }

        if (!function_exists('imagejpeg') || !function_exists('imagesx') || !function_exists('imagesy') || !function_exists('imagedestroy')) {
            if (function_exists('imagedestroy')) {
                imagedestroy($image);
            }
            return ['', 1, 1];
        }

        ob_start();
        imagejpeg($image, null, 90);
        $jpegData = ob_get_clean();
        $width = imagesx($image);
        $height = imagesy($image);
        imagedestroy($image);

        return [$jpegData ?: '', max(1, $width), max(1, $height)];
    }

    private function buildPdf(string $stream, string $logoData, int $logoWidth, int $logoHeight): string
    {
        $hasLogo = $logoData !== '';
        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $resources = '/Font << /F1 4 0 R >>';
        if ($hasLogo) {
            $resources .= ' /XObject << /Im1 6 0 R >>';
        }

        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << {$resources} >> /Contents 5 0 R >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $objects[] = "5 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream\nendobj\n";

        if ($hasLogo) {
            $imgObj = "6 0 obj\n<< /Type /XObject /Subtype /Image /Width {$logoWidth} /Height {$logoHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($logoData) . " >>\nstream\n" . $logoData . "\nendstream\nendobj\n";
            $objects[] = $imgObj;
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
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
