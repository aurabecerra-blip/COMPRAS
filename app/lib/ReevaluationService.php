<?php
class ReevaluationService
{
    private const CRITERIA = [
        'delivery_time' => [
            'name' => 'Cumple con los tiempos de entrega',
            'weight' => 20,
        ],
        'quality' => [
            'name' => 'Cumple con la Calidad del producto o servicio',
            'weight' => 40,
            'scores' => [
                'meets' => 40,
                'not_meets' => 0,
            ],
            'labels' => [
                'meets' => 'Cumple con los requisitos',
                'not_meets' => 'No cumple',
            ],
        ],
        'after_sales' => [
            'name' => 'Servicio postventa oportuno - garantías',
            'weight' => 10,
            'scores' => [
                'full' => 10,
                'partial' => 5,
                'none' => 0,
            ],
            'labels' => [
                'full' => 'Cumple oportunamente con todas las garantías y soporte técnico',
                'partial' => 'Cumple parcialmente con garantías y soporte técnico',
                'none' => 'No cumple con garantías ni soporte técnico',
            ],
        ],
    ];

    public function criteria(): array
    {
        return self::CRITERIA;
    }

    public function calculate(array $input): array
    {
        $items = [];

        $deliveryMode = (string)($input['delivery_mode'] ?? 'on_time');
        if ($deliveryMode === 'breach') {
            $breaches = max(0, (int)($input['delivery_breaches'] ?? 0));
            $score = max(0, 20 - ($breaches * 2));
            $items[] = [
                'criterion_code' => 'delivery_time',
                'criterion_name' => self::CRITERIA['delivery_time']['name'],
                'selected_option' => 'breach',
                'selected_label' => 'Incumplimiento',
                'extra_value' => $breaches,
                'item_score' => $score,
            ];
        } else {
            $items[] = [
                'criterion_code' => 'delivery_time',
                'criterion_name' => self::CRITERIA['delivery_time']['name'],
                'selected_option' => 'on_time',
                'selected_label' => 'A tiempo',
                'extra_value' => null,
                'item_score' => 20,
            ];
        }

        foreach (['quality', 'after_sales'] as $code) {
            $option = (string)($input[$code] ?? '');
            $criterion = self::CRITERIA[$code];
            if (!isset($criterion['scores'][$option])) {
                throw new InvalidArgumentException('Debe seleccionar una opción válida para: ' . $criterion['name']);
            }
            $items[] = [
                'criterion_code' => $code,
                'criterion_name' => $criterion['name'],
                'selected_option' => $option,
                'selected_label' => $criterion['labels'][$option],
                'extra_value' => null,
                'item_score' => (int)$criterion['scores'][$option],
            ];
        }

        $total = array_sum(array_column($items, 'item_score'));
        if ($total < 0 || $total > 100) {
            throw new RuntimeException('El total de puntaje calculado debe estar entre 0 y 100.');
        }

        return ['items' => $items, 'total_score' => $total];
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
            $extra = $item['extra_value'] !== null ? ' (incumplimientos: ' . (int)$item['extra_value'] . ')' : '';
            $lines[] = '- ' . $item['criterion_name'] . ': ' . $item['selected_label'] . $extra . ' => ' . (int)$item['item_score'];
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
