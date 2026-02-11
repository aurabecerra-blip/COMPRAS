<?php
class SupplierEvaluationCalculator
{
    public const CRITERIA = [
        'delivery_time' => [
            'name' => 'Cumple con los tiempos de entrega',
            'max_score' => 20,
            'type' => 'delivery',
            'options' => [
                'on_time' => ['label' => 'A tiempo', 'score' => 20],
            ],
        ],
        'quality' => [
            'name' => 'Cumple con la calidad del producto o servicio',
            'max_score' => 40,
            'type' => 'choice',
            'options' => [
                'meets' => ['label' => 'Cumple con los requisitos', 'score' => 40],
                'not_meets' => ['label' => 'No cumple', 'score' => 0],
            ],
        ],
        'after_sales' => [
            'name' => 'Servicio postventa oportuno / garantías',
            'max_score' => 10,
            'type' => 'choice',
            'options' => [
                'full' => ['label' => 'Cumple oportunamente con todas las garantías y soporte técnico', 'score' => 10],
                'partial' => ['label' => 'Cumple parcialmente con las garantías y soporte técnico', 'score' => 5],
                'none' => ['label' => 'No cumple con las garantías ni brinda soluciones', 'score' => 0],
            ],
        ],
        'sqr' => [
            'name' => 'Atención oportuna a SQR (Sugerencias, Quejas y Reclamos)',
            'max_score' => 10,
            'type' => 'choice',
            'options' => [
                'no_claims' => ['label' => 'No se han presentado quejas, atiende oportunamente las solicitudes', 'score' => 10],
                'timely' => ['label' => 'Atiende oportunamente los reclamos (1 a 5 días)', 'score' => 5],
                'untimely' => ['label' => 'No atiende reclamos oportunamente', 'score' => 0],
            ],
        ],
        'documents' => [
            'name' => 'Cumple con los documentos requeridos',
            'max_score' => 20,
            'type' => 'choice',
            'options' => [
                'complete' => ['label' => 'Cumple con todos los documentos solicitados', 'score' => 20],
                'incomplete' => ['label' => 'No envía documentos completos o demora en la entrega', 'score' => 0],
            ],
        ],
    ];

    public function definitions(): array
    {
        return self::CRITERIA;
    }

    public function calculate(array $input): array
    {
        $details = [];
        $total = 0;

        foreach (self::CRITERIA as $code => $criterion) {
            if ($criterion['type'] === 'delivery') {
                $result = $this->calculateDelivery($criterion, $input[$code] ?? []);
            } else {
                $result = $this->calculateChoice($criterion, (string)($input[$code] ?? ''));
            }

            $total += $result['score'];
            $details[] = [
                'criterion_code' => $code,
                'criterion_name' => $criterion['name'],
                'option_key' => $result['option_key'],
                'option_label' => $result['option_label'],
                'score' => $result['score'],
                'notes' => $result['notes'],
            ];
        }

        return [
            'total_score' => $total,
            'status_label' => $this->statusFromScore($total),
            'details' => $details,
        ];
    }

    private function calculateDelivery(array $criterion, array $raw): array
    {
        $mode = (string)($raw['mode'] ?? 'on_time');
        $defaults = $criterion['options']['on_time'];

        if ($mode !== 'breach') {
            return [
                'option_key' => 'on_time',
                'option_label' => $defaults['label'],
                'score' => $defaults['score'],
                'notes' => null,
            ];
        }

        $breaches = max(0, (int)($raw['breaches'] ?? 0));
        $discount = $breaches * 2;
        $score = max(0, $criterion['max_score'] - $discount);

        return [
            'option_key' => 'breach',
            'option_label' => 'Incumplimientos de entrega: ' . $breaches,
            'score' => $score,
            'notes' => 'Descuento aplicado: ' . $discount . ' puntos.',
        ];
    }

    private function calculateChoice(array $criterion, string $option): array
    {
        if (!isset($criterion['options'][$option])) {
            throw new InvalidArgumentException('Opción inválida para criterio: ' . $criterion['name']);
        }

        return [
            'option_key' => $option,
            'option_label' => $criterion['options'][$option]['label'],
            'score' => $criterion['options'][$option]['score'],
            'notes' => null,
        ];
    }

    public function statusFromScore(int $score): string
    {
        if ($score >= 80) {
            return 'Aprobado';
        }

        if ($score >= 60) {
            return 'Condicional';
        }

        return 'No aprobado';
    }
}
