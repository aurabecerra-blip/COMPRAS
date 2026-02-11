<?php
class SupplierSelectionService
{
    public function criteria(): array
    {
        return [
            'price' => 40,
            'delivery' => 25,
            'payment' => 15,
            'warranty' => 10,
            'technical' => 10,
        ];
    }

    public function validateForDecision(array $quotations): void
    {
        if (count($quotations) < 3) {
            throw new InvalidArgumentException('Se requieren mínimo 3 cotizaciones para decidir.');
        }
        $supplierIds = array_unique(array_map(fn($q) => (int)$q['supplier_id'], $quotations));
        if (count($supplierIds) < 3) {
            throw new InvalidArgumentException('Las 3 cotizaciones deben ser de proveedores diferentes.');
        }
    }

    public function score(array $quotations): array
    {
        $this->validateForDecision($quotations);

        $minPrice = min(array_map(fn($q) => (float)$q['total_value'], $quotations));
        $minDelivery = min(array_map(fn($q) => (int)$q['delivery_term_days'], $quotations));

        $rows = [];
        foreach ($quotations as $quotation) {
            $price = (float)$quotation['total_value'];
            $delivery = max(1, (int)$quotation['delivery_term_days']);

            $priceScore = $minPrice > 0 ? round(($minPrice / $price) * 40, 2) : 0.0;
            $deliveryScore = round(($minDelivery / $delivery) * 25, 2);
            $paymentScore = $this->paymentScore((string)$quotation['payment_terms']);
            $warrantyScore = $this->warrantyScore((string)$quotation['warranty']);
            $technicalScore = $this->technicalScore((string)$quotation['technical_compliance']);

            $total = round($priceScore + $deliveryScore + $paymentScore + $warrantyScore + $technicalScore, 2);
            $rows[] = [
                'quotation_id' => (int)$quotation['id'],
                'supplier_id' => (int)$quotation['supplier_id'],
                'price_score' => $priceScore,
                'delivery_score' => $deliveryScore,
                'payment_score' => $paymentScore,
                'warranty_score' => $warrantyScore,
                'technical_score' => $technicalScore,
                'total_score' => $total,
                'details' => [
                    'price' => $price,
                    'delivery_days' => $delivery,
                    'payment_terms' => $quotation['payment_terms'],
                    'warranty' => $quotation['warranty'],
                    'technical_compliance' => $quotation['technical_compliance'],
                ],
            ];
        }

        usort($rows, fn($a, $b) => $b['total_score'] <=> $a['total_score']);
        foreach ($rows as $idx => &$row) {
            $row['rank_position'] = $idx + 1;
            $row['is_winner'] = $idx === 0;
        }
        unset($row);

        return $rows;
    }

    public function resolveWinner(array $scores, int $manualWinnerSupplierId, string $manualJustification): array
    {
        if (empty($scores)) {
            throw new InvalidArgumentException('No hay puntajes para resolver ganador.');
        }

        $defaultWinner = $scores[0];
        if ($manualWinnerSupplierId <= 0 || $manualWinnerSupplierId === (int)$defaultWinner['supplier_id']) {
            return [
                'winner_supplier_id' => (int)$defaultWinner['supplier_id'],
                'justification' => 'Ganador por mayor puntaje total.',
            ];
        }

        if (trim($manualJustification) === '') {
            throw new InvalidArgumentException('Si elige un ganador diferente al de mayor puntaje, la justificación es obligatoria.');
        }

        $exists = array_filter($scores, fn($s) => (int)$s['supplier_id'] === $manualWinnerSupplierId);
        if (!$exists) {
            throw new InvalidArgumentException('El proveedor ganador manual no está en la evaluación.');
        }

        return [
            'winner_supplier_id' => $manualWinnerSupplierId,
            'justification' => trim($manualJustification),
        ];
    }

    public function buildActPdf(array $context): string
    {
        $prId = (int)$context['purchase_request']['id'];
        $dir = __DIR__ . '/../../public/storage/selection-evaluations/' . $prId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = 'evaluacion_seleccion_' . date('Ymd_His') . '.pdf';
        $fullPath = $dir . '/' . $filename;

        $lines = [
            'ACTA DE EVALUACION DE SELECCION DE PROVEEDOR',
            'Solicitud: #' . $prId . ' - ' . ($context['purchase_request']['title'] ?? ''),
            'Area: ' . ($context['purchase_request']['area'] ?? 'N/D'),
            'Justificacion: ' . ($context['purchase_request']['justification'] ?? 'N/D'),
            'Fecha: ' . date('Y-m-d H:i:s'),
            '',
            'Cotizaciones registradas:',
        ];

        foreach (($context['quotations'] ?? []) as $quotation) {
            $lines[] = '- ' . ($quotation['supplier_name'] ?? ('Proveedor #' . $quotation['supplier_id']))
                . ' | Numero: ' . ($quotation['quote_number'] ?? 'N/A')
                . ' | Fecha: ' . ($quotation['quotation_date'] ?? 'N/D')
                . ' | Total: ' . ($quotation['currency'] ?? 'COP') . ' ' . number_format((float)($quotation['total_value'] ?? 0), 2);
        }

        $lines[] = '';
        $lines[] = 'Puntuaciones detalladas:';
        $lines[] = 'Criterios: Precio(40), Entrega(25), Pago(15), Garantia(10), Tecnico(10).';

        foreach ($context['scores'] as $score) {
            $lines[] = '- ' . ($score['supplier_name'] ?? ('Proveedor #' . $score['supplier_id']))
                . ': Total ' . $score['total_score']
                . ' (Precio ' . $score['price_score']
                . ', Entrega ' . $score['delivery_score']
                . ', Pago ' . $score['payment_score']
                . ', Garantia ' . $score['warranty_score']
                . ', Tecnico ' . $score['technical_score'] . ')';
        }

        $lines[] = '';
        $lines[] = 'Proveedor seleccionado: ' . ($context['winner_name'] ?? 'N/D');
        $lines[] = 'Justificacion: ' . ($context['winner_justification'] ?? 'N/D');
        $lines[] = 'Observaciones: ' . (($context['observations'] ?? '') !== '' ? $context['observations'] : 'Sin observaciones');

        $stream = "BT
/F1 10 Tf
40 800 Td
";
        foreach ($lines as $i => $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], trim($line));
            $stream .= ($i === 0 ? "($escaped) Tj
" : "0 -14 Td
($escaped) Tj
");
        }
        $stream .= "ET";

        $pdf = "%PDF-1.4
";
        $objs = [
            "1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
",
            "2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
",
            "3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>
endobj
",
            "4 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
",
            "5 0 obj
<< /Length " . strlen($stream) . " >>
stream
" . $stream . "
endstream
endobj
",
        ];

        $offsets = [0];
        foreach ($objs as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
        }
        $xref = strlen($pdf);
        $pdf .= "xref
0 " . (count($objs) + 1) . "
0000000000 65535 f 
";
        for ($i = 1; $i <= count($objs); $i++) {
            $pdf .= sprintf("%010d 00000 n 
", $offsets[$i]);
        }
        $pdf .= "trailer
<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>
startxref
" . $xref . "
%%EOF";

        file_put_contents($fullPath, $pdf);
        return '/storage/selection-evaluations/' . $prId . '/' . $filename;
    }

    private function paymentScore(string $paymentTerms): float
    {
        $terms = mb_strtoupper($paymentTerms);
        if (str_contains($terms, '60') || str_contains($terms, '45') || str_contains($terms, '30')) {
            return 15.0;
        }
        if (str_contains($terms, '15')) {
            return 10.0;
        }
        if (str_contains($terms, 'CONTADO')) {
            return 5.0;
        }
        return 8.0;
    }

    private function warrantyScore(string $warranty): float
    {
        $text = mb_strtoupper($warranty);
        if (str_contains($text, '24') || str_contains($text, '2 AÑO')) {
            return 10.0;
        }
        if (str_contains($text, '12') || str_contains($text, '1 AÑO')) {
            return 7.0;
        }
        if (str_contains($text, '6')) {
            return 5.0;
        }
        return 3.0;
    }

    private function technicalScore(string $technicalCompliance): float
    {
        return match ($technicalCompliance) {
            'CUMPLE' => 10.0,
            'PARCIAL' => 5.0,
            default => 0.0,
        };
    }
}
