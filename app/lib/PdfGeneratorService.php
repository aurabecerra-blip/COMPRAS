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

        $providers = array_slice($context['scores'], 0, 3);
        $criteria = $context['criteria'];

        $lines = [
            'ANÁLISIS DE SELECCIÓN DE PROVEEDORES',
            'Versión: 01  Fecha: ' . date('d/m/Y'),
            'Solicitud: PR #' . $prId . ' - ' . ($context['purchase_request']['title'] ?? ''),
            '',
            'CRITERIO | PROVEEDOR 1 | PROVEEDOR 2 | PROVEEDOR 3 | PONDERACIÓN | DESCRIPCIÓN/ESCALA',
        ];

        foreach ($criteria as $key => $criterion) {
            $scores = [];
            foreach ([0, 1, 2] as $idx) {
                $scores[] = isset($providers[$idx]) ? (string)($providers[$idx][$key . '_score'] ?? 0) : '-';
            }

            $lines[] = strtoupper($criterion['label'])
                . ' | ' . $scores[0]
                . ' | ' . $scores[1]
                . ' | ' . $scores[2]
                . ' | ' . $criterion['ponderacion'] . '%'
                . ' | ' . $criterion['descripcion'];
        }

        $totals = [];
        foreach ([0, 1, 2] as $idx) {
            $totals[] = isset($providers[$idx]) ? (string)($providers[$idx]['total_score'] ?? 0) : '-';
        }

        $lines[] = 'TOTAL PUNTAJE | ' . $totals[0] . ' | ' . $totals[1] . ' | ' . $totals[2] . ' | 100 | ';        
        $lines[] = '';
        $lines[] = 'Ganador: ' . ($context['winner_name'] ?? 'N/D');
        $lines[] = 'Comentarios adicionales / Observaciones:';
        $lines[] = $context['observations'] ?: 'Sin observaciones';

        $stream = "BT\n/F1 9 Tf\n36 800 Td\n";
        foreach ($lines as $index => $line) {
            $escaped = $this->pdfEscape($line);
            if ($index === 0) {
                $stream .= "(" . $escaped . ") Tj\n";
            } else {
                $stream .= "0 -14 Td\n(" . $escaped . ") Tj\n";
            }
        }
        $stream .= "ET";

        file_put_contents($fullPath, $this->buildPdf($stream));
        return '/storage/seleccion_proveedor/' . $prId . '/' . $filename;
    }

    private function buildPdf(string $stream): string
    {
        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
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
