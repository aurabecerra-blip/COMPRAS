<?php
class SupplierEvaluationCalculator
{
    public const MIN_PASSING_SCORE = 80;

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

    public const CRITERIA = [
        'delivery_time' => [
            'name' => 'Cumple con los tiempos de entrega',
            'max_score' => 20,
        ],
        'quality' => [
            'name' => 'Calidad del producto o servicio',
            'max_score' => 40,
        ],
        'after_sales' => [
            'name' => 'Servicio postventa (garantías)',
            'max_score' => 10,
        ],
        'sqr' => [
            'name' => 'Atención oportuna a SQR (Sugerencias, Quejas y Reclamos)',
            'max_score' => 10,
        ],
        'documents' => [
            'name' => 'Cumple con los documentos requeridos',
            'max_score' => 20,
        ],
    ];

    public function definitions(): array
    {
        $definitions = [];

        foreach (self::CRITERIA as $code => $criterion) {
            $criterion['options'] = $this->optionsForCriterion($code);
            $definitions[$code] = $criterion;
        }

        return $definitions;
    }

    public function calculate(array $input): array
    {
        $details = [];
        $total = 0;

        foreach (self::CRITERIA as $code => $criterion) {
            $normalizedLevel = $this->normalizeLevel($code, (string)($input[$code] ?? ''));
            $factor = self::LEVEL_FACTORS[$normalizedLevel];
            $score = (int)round($criterion['max_score'] * $factor);
            $option = $this->optionsForCriterion($code)[$normalizedLevel];

            $total += $score;
            $details[] = [
                'criterion_code' => $code,
                'criterion_name' => $criterion['name'],
                'option_key' => $normalizedLevel,
                'option_label' => $option['label'],
                'score' => $score,
                'notes' => null,
            ];
        }

        return [
            'total_score' => $total,
            'status_label' => $this->statusFromScore($total),
            'details' => $details,
        ];
    }

    public function optionsForCriterion(string $criterionCode): array
    {
        $criterion = self::CRITERIA[$criterionCode] ?? null;
        if ($criterion === null) {
            return [];
        }

        $options = [];
        foreach (self::LEVEL_FACTORS as $key => $factor) {
            $score = (int)round($criterion['max_score'] * $factor);
            $options[$key] = [
                'label' => self::LEVEL_LABELS[$key] . ' (' . $score . ')',
                'score' => $score,
                'factor' => $factor,
            ];
        }

        return $options;
    }

    public function normalizeLevel(string $criterionCode, string $rawOption): string
    {
        $option = trim(strtolower($rawOption));
        if ($option === '') {
            throw new InvalidArgumentException('Debe seleccionar un nivel para el criterio: ' . (self::CRITERIA[$criterionCode]['name'] ?? $criterionCode));
        }

        if (isset(self::LEVEL_FACTORS[$option])) {
            return $option;
        }

        $legacy = self::LEGACY_OPTION_MAP[$criterionCode][$option] ?? null;
        if ($legacy !== null) {
            return $legacy;
        }

        throw new InvalidArgumentException('Opción inválida para criterio: ' . (self::CRITERIA[$criterionCode]['name'] ?? $criterionCode));
    }

    public function statusFromScore(int $score): string
    {
        if ($score >= self::MIN_PASSING_SCORE) {
            return 'Aprobado';
        }

        return 'No aprobado';
    }
}
