<?php
class PdfGeneratorService
{
    public function generateProviderSelectionPdf(array $context): string
    {
        $prId = (int)$context['purchase_request']['id'];
        $dir = __DIR__ . '/../../public/storage/seleccion_proveedor/' . $prId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = 'analisis_seleccion_' . date('Ymd_His') . '.pdf';
        $fullPath = $dir . '/' . $filename;

        $scores = $context['scores'] ?? [];
        $headers = ['Proveedor', 'Precio', 'Exp.', 'Pago', 'Entrega', 'Desc.', 'Cert.', 'Precios', 'Total'];
        $widths = [18, 10, 5, 5, 7, 5, 5, 7, 5];

        $lines = [
            'ANÁLISIS DE SELECCIÓN DE PROVEEDORES',
            'Versión: 01  Fecha: ' . date('d/m/Y'),
            'Solicitud: PR #' . $prId . ' - ' . ($context['purchase_request']['title'] ?? ''),
            '',
            $this->buildTableRow($headers, $widths),
            $this->buildTableSeparator($widths),
        ];

        foreach ($scores as $row) {
            $detail = json_decode($row['criterio_detalle_json'] ?? '{}', true) ?: [];
            $lines[] = $this->buildTableRow([
                (string)($row['provider_name'] ?? 'N/D'),
                number_format((float)($detail['valor'] ?? 0), 0),
                (string)$row['experiencia_score'],
                (string)$row['forma_pago_score'],
                (string)$row['entrega_score'],
                (string)$row['descuento_score'],
                (string)$row['certificaciones_score'],
                (string)$row['precios_score'],
                (string)$row['total_score'],
            ], $widths);
        }

        $lines[] = '';
        $lines[] = 'Ganador: ' . ($context['winner_name'] ?? 'N/D');
        $lines[] = 'Observaciones: ' . (($context['observations'] ?? '') ?: 'Sin observaciones');

        $stream = "BT
/F1 9 Tf
36 800 Td
";
        foreach ($lines as $index => $line) {
            $escaped = $this->pdfEscape($line);
            if ($index === 0) {
                $stream .= "(" . $escaped . ") Tj
";
            } else {
                $stream .= "0 -14 Td
(" . $escaped . ") Tj
";
            }
        }
        $stream .= "ET";

        file_put_contents($fullPath, $this->buildPdf($stream));
        return '/storage/seleccion_proveedor/' . $prId . '/' . $filename;
    }

    private function buildTableRow(array $values, array $widths): string
    {
        $cells = [];
        foreach ($widths as $index => $width) {
            $value = $values[$index] ?? '';
            $clean = mb_strimwidth((string)$value, 0, $width, '');
            $cells[] = str_pad($clean, $width, ' ');
        }

        return '| ' . implode(' | ', $cells) . ' |';
    }

    private function buildTableSeparator(array $widths): string
    {
        $segments = [];
        foreach ($widths as $width) {
            $segments[] = str_repeat('-', $width);
        }

        return '|-' . implode('-|-', $segments) . '-|';
    }

    private function buildPdf(string $stream): string
    {
        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>\nendobj\n";
        $objects[] = "5 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream\nendobj\n";

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
