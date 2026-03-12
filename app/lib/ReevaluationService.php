<?php
class ReevaluationService
{
    private const LEVEL_FACTORS = [
        'excellent' => 1.0,
        'good' => 0.75,
        'regular' => 0.5,
        'deficient' => 0.0,
    ];

    private const LEVEL_LABELS = [
        'excellent' => 'Excelente',
        'good' => 'Bueno',
        'regular' => 'Regular',
        'deficient' => 'Deficiente',
    ];

    private const LEGACY_OPTION_MAP = [
        'delivery_time' => [
            'on_time' => 'excellent',
            'breach' => 'regular',
        ],
        'quality' => [
            'meets' => 'excellent',
            'not_meets' => 'deficient',
        ],
        'after_sales' => [
            'full' => 'excellent',
            'partial' => 'regular',
            'none' => 'deficient',
        ],
        'sqr' => [
            'no_claims' => 'excellent',
            'timely' => 'regular',
            'untimely' => 'deficient',
        ],
        'documents' => [
            'complete' => 'excellent',
            'incomplete' => 'deficient',
        ],
    ];

    private const CRITERIA = [
        'delivery_time' => [
            'name' => 'Cumple con los tiempos de entrega',
            'weight' => 20,
        ],
        'quality' => [
            'name' => 'Calidad del producto o servicio',
            'weight' => 40,
        ],
        'after_sales' => [
            'name' => 'Servicio postventa (garantías)',
            'weight' => 10,
        ],
        'sqr' => [
            'name' => 'Atención oportuna a SQR (Sugerencias, Quejas y Reclamos)',
            'weight' => 10,
        ],
        'documents' => [
            'name' => 'Cumple con los documentos requeridos',
            'weight' => 20,
        ],
    ];

    public function criteria(): array
    {
        $criteria = [];
        foreach (self::CRITERIA as $code => $criterion) {
            $criterion['options'] = $this->optionsForCriterion($code);
            $criteria[$code] = $criterion;
        }

        return $criteria;
    }

    public function calculate(array $input): array
    {
        $items = [];

        foreach (self::CRITERIA as $code => $criterion) {
            $selected = $this->normalizeLevel($code, (string)($input[$code] ?? ''));
            $factor = self::LEVEL_FACTORS[$selected];
            $score = (int)round($criterion['weight'] * $factor);
            $items[] = [
                'criterion_code' => $code,
                'criterion_name' => $criterion['name'],
                'selected_option' => $selected,
                'selected_label' => self::LEVEL_LABELS[$selected],
                'extra_value' => null,
                'item_score' => $score,
            ];
        }

        $total = array_sum(array_column($items, 'item_score'));
        if ($total < 0 || $total > 100) {
            throw new RuntimeException('El total de puntaje calculado debe estar entre 0 y 100.');
        }

        return ['items' => $items, 'total_score' => $total];
    }

    private function optionsForCriterion(string $criterionCode): array
    {
        $criterion = self::CRITERIA[$criterionCode] ?? null;
        if ($criterion === null) {
            return [];
        }

        $options = [];
        foreach (self::LEVEL_FACTORS as $key => $factor) {
            $options[$key] = [
                'label' => self::LEVEL_LABELS[$key],
                'score' => (int)round($criterion['weight'] * $factor),
            ];
        }

        return $options;
    }

    private function normalizeLevel(string $criterionCode, string $option): string
    {
        $normalized = trim(strtolower($option));
        if ($normalized === '') {
            throw new InvalidArgumentException('Debe seleccionar una opción válida para: ' . (self::CRITERIA[$criterionCode]['name'] ?? $criterionCode));
        }

        if (isset(self::LEVEL_FACTORS[$normalized])) {
            return $normalized;
        }

        $legacy = self::LEGACY_OPTION_MAP[$criterionCode][$normalized] ?? null;
        if ($legacy !== null) {
            return $legacy;
        }

        throw new InvalidArgumentException('Debe seleccionar una opción válida para: ' . (self::CRITERIA[$criterionCode]['name'] ?? $criterionCode));
    }

    public function buildPdf(array $reevaluation): string
    {
        $dir = __DIR__ . '/../../public/storage/reevaluaciones';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $filename = 'reevaluacion_' . (int)$reevaluation['id'] . '_' . date('Ymd_His') . '.pdf';
        $fullPath = $dir . '/' . $filename;

        $lines = [
            'REEVALUACION DE PROVEEDOR',
            'Proveedor: ' . ($reevaluation['provider_name'] ?? 'N/D'),
            'NIT: ' . ($reevaluation['provider_nit'] ?? 'N/D'),
            'Servicio: ' . ($reevaluation['service_provided'] ?? 'N/D'),
            'Fecha: ' . ($reevaluation['evaluation_date'] ?? date('Y-m-d')),
            'Evaluador: ' . ($reevaluation['evaluator_name'] ?? 'N/D'),
            'Total puntaje: ' . (int)$reevaluation['total_score'] . ' / 100',
            'Observaciones: ' . ($reevaluation['observations'] ?? 'Sin observaciones'),
            '',
            'DETALLE CRITERIOS:',
        ];

        foreach ($reevaluation['items'] as $item) {
            $lines[] = '- ' . $item['criterion_name'] . ': ' . $item['selected_label'] . ' => ' . (int)$item['item_score'];
        }

        $stream = "BT\n/F1 10 Tf\n40 800 Td\n";
        foreach ($lines as $idx => $line) {
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], trim($line));
            $stream .= ($idx === 0 ? "($escaped) Tj\n" : "0 -14 Td\n($escaped) Tj\n");
        }
        $stream .= "ET";

        $pdf = "%PDF-1.4\n";
        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $objects[] = "5 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream\nendobj\n";

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
        file_put_contents($fullPath, $pdf);

        return '/storage/reevaluaciones/' . $filename;
    }
}
