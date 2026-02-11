<?php
class SupplierSelectionService
{
    public function criteria(): array
    {
        return [
            'PRECIO_NETO' => ['name' => 'Precio neto', 'weight' => 35.0],
            'DESCUENTO' => ['name' => 'Descuento', 'weight' => 10.0],
            'ENTREGA' => ['name' => 'Tiempo de entrega', 'weight' => 20.0],
            'PAGO' => ['name' => 'Condiciones de pago', 'weight' => 10.0],
            'GARANTIA_POSTVENTA' => ['name' => 'Garantía / postventa', 'weight' => 10.0],
            'EXPERIENCIA' => ['name' => 'Experiencia', 'weight' => 10.0],
            'CERTIFICACIONES' => ['name' => 'Certificaciones', 'weight' => 5.0],
        ];
    }

    public function validateForDecision(array $quotations): void
    {
        if (count($quotations) < 3) {
            throw new InvalidArgumentException('Se requieren mínimo 3 cotizaciones para seleccionar proveedor.');
        }

        $supplierIds = array_unique(array_map(fn($q) => (int)$q['supplier_id'], $quotations));
        if (count($supplierIds) < 3) {
            throw new InvalidArgumentException('Las cotizaciones deben corresponder a 3 proveedores diferentes.');
        }

        foreach ($quotations as $quotation) {
            if (empty($quotation['archivo_cotizacion_url']) && empty($quotation['evidence_file_path'])) {
                throw new InvalidArgumentException('Todas las cotizaciones deben tener archivo de cotización adjunto.');
            }

            if ((int)($quotation['ofrece_descuento'] ?? 0) === 1) {
                if (!in_array((string)($quotation['tipo_descuento'] ?? ''), ['PORCENTAJE', 'VALOR'], true)) {
                    throw new InvalidArgumentException('Si ofrece descuento, debe registrar tipo de descuento.');
                }
                if ((float)($quotation['descuento_valor'] ?? 0) <= 0) {
                    throw new InvalidArgumentException('Si ofrece descuento, descuento_valor debe ser mayor a 0.');
                }
            }

            $hasCertifications = ((int)($quotation['certificaciones_tecnicas'] ?? 0) === 1)
                || ((int)($quotation['certificaciones_comerciales'] ?? 0) === 1);
            if ($hasCertifications) {
                if (trim((string)($quotation['lista_certificaciones'] ?? '')) === '') {
                    throw new InvalidArgumentException('Si marca certificaciones, debe registrar la lista de certificaciones.');
                }
                if (trim((string)($quotation['archivo_certificaciones_url'] ?? '')) === '') {
                    throw new InvalidArgumentException('Si marca certificaciones, debe adjuntar archivo_certificaciones.');
                }
            }
        }
    }

    public function score(array $quotations): array
    {
        $this->validateForDecision($quotations);
        $criteria = $this->criteria();

        $minNetPrice = INF;
        $maxDiscount = 0.0;
        $minDeliveryDays = INF;

        foreach ($quotations as $quotation) {
            $net = $this->netPrice($quotation);
            $discount = $this->discountApplied($quotation);
            $days = max(1, (int)($quotation['delivery_term_days'] ?? 0));

            $minNetPrice = min($minNetPrice, $net);
            $maxDiscount = max($maxDiscount, $discount);
            $minDeliveryDays = min($minDeliveryDays, $days);
        }

        $rows = [];
        foreach ($quotations as $quotation) {
            $netPrice = $this->netPrice($quotation);
            $discountApplied = $this->discountApplied($quotation);
            $deliveryDays = max(1, (int)$quotation['delivery_term_days']);

            $priceScore = $minNetPrice > 0 ? round($criteria['PRECIO_NETO']['weight'] * ($minNetPrice / max($netPrice, 0.0001)), 2) : 0.0;
            $discountScore = $maxDiscount > 0 ? round($criteria['DESCUENTO']['weight'] * ($discountApplied / $maxDiscount), 2) : 0.0;
            $deliveryScore = round($criteria['ENTREGA']['weight'] * ($minDeliveryDays / $deliveryDays), 2);
            $paymentScore = $this->paymentScore((string)($quotation['evaluacion_pago'] ?? 'ACEPTABLES'));
            $postSaleScore = $this->afterSalesScore((string)($quotation['evaluacion_postventa'] ?? 'CUMPLE_PARCIAL'));
            $experienceScore = $this->experienceScore((int)($quotation['experiencia_anios'] ?? 0));
            $certificationScore = $this->certificationScore($quotation);

            $criteriaRows = [
                $this->criterionRow($quotation, 'PRECIO_NETO', $criteria['PRECIO_NETO']['name'], $criteria['PRECIO_NETO']['weight'], $priceScore, [
                    'valor_total' => (float)($quotation['valor_total'] ?? 0),
                    'descuento_aplicado' => $discountApplied,
                    'precio_neto' => $netPrice,
                    'min_precio_neto' => $minNetPrice,
                ], '35 * (min_precio_neto / precio_neto_proveedor)'),
                $this->criterionRow($quotation, 'DESCUENTO', $criteria['DESCUENTO']['name'], $criteria['DESCUENTO']['weight'], $discountScore, [
                    'descuento_equiv_cop' => $discountApplied,
                    'max_descuento_equiv_cop' => $maxDiscount,
                ], '10 * (descuento_equiv_cop / max_descuento_equiv_cop)'),
                $this->criterionRow($quotation, 'ENTREGA', $criteria['ENTREGA']['name'], $criteria['ENTREGA']['weight'], $deliveryScore, [
                    'dias_proveedor' => $deliveryDays,
                    'min_dias' => $minDeliveryDays,
                ], '20 * (min_dias / dias_proveedor)'),
                $this->criterionRow($quotation, 'PAGO', $criteria['PAGO']['name'], $criteria['PAGO']['weight'], $paymentScore, [
                    'evaluacion_pago' => $quotation['evaluacion_pago'] ?? 'ACEPTABLES',
                ], 'Muy favorables=10, Aceptables=5, Poco favorables=0'),
                $this->criterionRow($quotation, 'GARANTIA_POSTVENTA', $criteria['GARANTIA_POSTVENTA']['name'], $criteria['GARANTIA_POSTVENTA']['weight'], $postSaleScore, [
                    'evaluacion_postventa' => $quotation['evaluacion_postventa'] ?? 'CUMPLE_PARCIAL',
                ], 'Cumple todas=10, Cumple parcial=5, No cumple=0'),
                $this->criterionRow($quotation, 'EXPERIENCIA', $criteria['EXPERIENCIA']['name'], $criteria['EXPERIENCIA']['weight'], $experienceScore, [
                    'experiencia_anios' => (int)($quotation['experiencia_anios'] ?? 0),
                ], '>=5:10, 3-4:7, 1-2:4, 0:0'),
                $this->criterionRow($quotation, 'CERTIFICACIONES', $criteria['CERTIFICACIONES']['name'], $criteria['CERTIFICACIONES']['weight'], $certificationScore, [
                    'certificaciones_tecnicas' => (int)($quotation['certificaciones_tecnicas'] ?? 0),
                    'certificaciones_comerciales' => (int)($quotation['certificaciones_comerciales'] ?? 0),
                    'lista_certificaciones' => (string)($quotation['lista_certificaciones'] ?? ''),
                    'archivo_certificaciones_url' => (string)($quotation['archivo_certificaciones_url'] ?? ''),
                ], 'Técnicas=3, Comerciales=2, máximo 5 con soportes'),
            ];

            $total = round(array_reduce($criteriaRows, fn($sum, $row) => $sum + (float)$row['score_value'], 0.0), 2);
            $criteriaRows[] = $this->criterionRow($quotation, 'TOTAL', 'Total puntaje', 100.0, $total, [
                'precio_neto' => $netPrice,
                'descuento_aplicado' => $discountApplied,
            ], 'Suma de criterios');

            $rows[] = [
                'quotation_id' => (int)$quotation['id'],
                'supplier_id' => (int)$quotation['supplier_id'],
                'supplier_name' => $quotation['supplier_name'] ?? ('Proveedor #' . $quotation['supplier_id']),
                'total_score' => $total,
                'criteria_rows' => $criteriaRows,
            ];
        }

        usort($rows, fn($a, $b) => $b['total_score'] <=> $a['total_score']);
        foreach ($rows as $index => &$row) {
            $row['rank_position'] = $index + 1;
            $row['is_winner'] = $index === 0;
        }
        unset($row);

        return $rows;
    }

    public function resolveWinner(array $scores, int $manualWinnerSupplierId, string $manualJustification): array
    {
        if (empty($scores)) {
            throw new InvalidArgumentException('No hay puntajes disponibles para resolver el ganador.');
        }

        $defaultWinner = $scores[0];
        if ($manualWinnerSupplierId <= 0 || $manualWinnerSupplierId === (int)$defaultWinner['supplier_id']) {
            return [
                'winner_supplier_id' => (int)$defaultWinner['supplier_id'],
                'justification' => 'Ganador por mayor puntaje total.',
            ];
        }

        if (trim($manualJustification) === '') {
            throw new InvalidArgumentException('Si el ganador no es el #1 por puntaje, la justificación es obligatoria.');
        }

        $exists = array_filter($scores, fn($score) => (int)$score['supplier_id'] === $manualWinnerSupplierId);
        if (!$exists) {
            throw new InvalidArgumentException('El proveedor ganador manual no está dentro de las cotizaciones evaluadas.');
        }

        return [
            'winner_supplier_id' => $manualWinnerSupplierId,
            'justification' => trim($manualJustification),
        ];
    }

    public function buildActPdf(array $context): string
    {
        $prId = (int)($context['purchase_request']['id'] ?? 0);
        $dir = __DIR__ . '/../../public/storage/actas_seleccion/' . $prId;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = 'acta_seleccion_' . date('Ymd_His') . '.pdf';
        $fullPath = $dir . '/' . $filename;

        $lines = [
            'ACTA DE SELECCION DE PROVEEDOR POR COTIZACIONES',
            'Solicitud #' . $prId,
            'Area solicitante: ' . ($context['purchase_request']['area'] ?? 'N/D'),
            'Fecha aprobacion: ' . ($context['purchase_request']['approved_at'] ?? 'N/D'),
            'Fecha acta: ' . date('Y-m-d H:i:s'),
            '',
            'Tabla comparativa (puntajes):',
        ];

        foreach ($context['scores'] as $score) {
            $criteriaMap = [];
            foreach (($score['criteria_rows'] ?? []) as $detail) {
                $criteriaMap[$detail['criterion_code']] = $detail['score_value'];
            }
            $lines[] = sprintf(
                '- %s | Precio %.2f | Desc %.2f | Entrega %.2f | Pago %.2f | Garantia/Postventa %.2f | Experiencia %.2f | Certif %.2f | Total %.2f',
                $score['supplier_name'] ?? ('Proveedor #' . $score['supplier_id']),
                (float)($criteriaMap['PRECIO_NETO'] ?? 0),
                (float)($criteriaMap['DESCUENTO'] ?? 0),
                (float)($criteriaMap['ENTREGA'] ?? 0),
                (float)($criteriaMap['PAGO'] ?? 0),
                (float)($criteriaMap['GARANTIA_POSTVENTA'] ?? 0),
                (float)($criteriaMap['EXPERIENCIA'] ?? 0),
                (float)($criteriaMap['CERTIFICACIONES'] ?? 0),
                (float)($criteriaMap['TOTAL'] ?? $score['total_score'] ?? 0)
            );
        }

        $lines[] = '';
        $lines[] = 'Proveedor ganador: ' . ($context['winner_name'] ?? 'N/D');
        $lines[] = 'Justificacion: ' . ($context['winner_justification'] ?? 'N/D');
        $lines[] = '';
        $lines[] = 'Evidencias adjuntas por proveedor:';

        foreach (($context['quotations'] ?? []) as $quotation) {
            $lines[] = '- ' . ($quotation['supplier_name'] ?? ('Proveedor #' . $quotation['supplier_id']))
                . ' | Cotizacion: ' . ($quotation['archivo_cotizacion_url'] ?? 'N/D')
                . ' | Soporte exp: ' . (($quotation['archivo_soporte_experiencia_url'] ?? '') !== '' ? $quotation['archivo_soporte_experiencia_url'] : 'N/A')
                . ' | Certificaciones: ' . (($quotation['archivo_certificaciones_url'] ?? '') !== '' ? $quotation['archivo_certificaciones_url'] : 'N/A');
        }

        $stream = "BT\n/F1 9 Tf\n40 800 Td\n";
        foreach ($lines as $i => $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], trim($line));
            $stream .= ($i === 0 ? "($escaped) Tj\n" : "0 -12 Td\n($escaped) Tj\n");
        }
        $stream .= "ET";

        $pdf = "%PDF-1.4\n";
        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream\nendobj\n",
        ];

        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        file_put_contents($fullPath, $pdf);
        return '/storage/actas_seleccion/' . $prId . '/' . $filename;
    }

    private function criterionRow(array $quotation, string $code, string $name, float $weight, float $score, array $inputs, string $formula): array
    {
        return [
            'quotation_id' => (int)$quotation['id'],
            'supplier_id' => (int)$quotation['supplier_id'],
            'criterion_code' => $code,
            'criterion_name' => $name,
            'criterion_weight' => $weight,
            'score_value' => max(0.0, round($score, 2)),
            'input_data' => $inputs,
            'formula_applied' => $formula,
        ];
    }

    private function discountApplied(array $quotation): float
    {
        if ((int)($quotation['ofrece_descuento'] ?? 0) !== 1) {
            return 0.0;
        }

        $total = (float)($quotation['valor_total'] ?? 0);
        $discountValue = (float)($quotation['descuento_valor'] ?? 0);
        $type = (string)($quotation['tipo_descuento'] ?? 'VALOR');

        if ($type === 'PORCENTAJE') {
            return round($total * ($discountValue / 100), 2);
        }
        return round($discountValue, 2);
    }

    private function netPrice(array $quotation): float
    {
        $net = (float)($quotation['valor_total'] ?? 0) - $this->discountApplied($quotation);
        return round(max(0.0, $net), 2);
    }

    private function paymentScore(string $evaluation): float
    {
        return match ($evaluation) {
            'MUY_FAVORABLES' => 10.0,
            'POCO_FAVORABLES' => 0.0,
            default => 5.0,
        };
    }

    private function afterSalesScore(string $evaluation): float
    {
        return match ($evaluation) {
            'CUMPLE_TOTAL' => 10.0,
            'NO_CUMPLE' => 0.0,
            default => 5.0,
        };
    }

    private function experienceScore(int $years): float
    {
        if ($years >= 5) {
            return 10.0;
        }
        if ($years >= 3) {
            return 7.0;
        }
        if ($years >= 1) {
            return 4.0;
        }
        return 0.0;
    }

    private function certificationScore(array $quotation): float
    {
        $hasTechnical = (int)($quotation['certificaciones_tecnicas'] ?? 0) === 1;
        $hasCommercial = (int)($quotation['certificaciones_comerciales'] ?? 0) === 1;
        $list = trim((string)($quotation['lista_certificaciones'] ?? ''));
        $file = trim((string)($quotation['archivo_certificaciones_url'] ?? ''));

        if (($hasTechnical || $hasCommercial) && ($list === '' || $file === '')) {
            return 0.0;
        }

        $score = 0.0;
        if ($hasTechnical) {
            $score += 3.0;
        }
        if ($hasCommercial) {
            $score += 2.0;
        }
        return min(5.0, $score);
    }
}
