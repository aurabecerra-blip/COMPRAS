<?php
class ProviderSelectionScoringService
{
    public function criteriaTemplate(): array
    {
        return [
            'experiencia' => ['label' => 'Experiencia', 'ponderacion' => 15],
            'forma_pago' => ['label' => 'Forma de Pago', 'ponderacion' => 25],
            'entrega' => ['label' => 'Condiciones de Entrega', 'ponderacion' => 20],
            'descuento' => ['label' => 'Descuento / Valor Agregado', 'ponderacion' => 5],
            'certificaciones' => ['label' => 'Certificaciones Técnicas o Comerciales', 'ponderacion' => 10],
            'precios' => ['label' => 'Precios', 'ponderacion' => 25],
        ];
    }

    public function score(string $tipoCompra, array $input): array
    {
        $experiencia = $this->scoreExperiencia((string)($input['experiencia'] ?? ''));
        $formaPago = $this->scoreFormaPago($tipoCompra, (string)($input['forma_pago'] ?? ''), (string)($input['forma_pago_na_result'] ?? ''));
        $entrega = $this->scoreEntrega($tipoCompra, (string)($input['entrega'] ?? ''), (string)($input['entrega_na_result'] ?? ''));
        $descuento = $this->scoreDescuento((string)($input['descuento'] ?? ''));
        $certificaciones = $this->scoreCertificaciones((string)($input['certificaciones'] ?? ''));
        $precios = $this->scorePrecios((string)($input['precios'] ?? ''));

        $total = $experiencia + $formaPago + $entrega + $descuento + $certificaciones + $precios;
        return [
            'experiencia_score' => $experiencia,
            'forma_pago_score' => $formaPago,
            'entrega_score' => $entrega,
            'descuento_score' => $descuento,
            'certificaciones_score' => $certificaciones,
            'precios_score' => $precios,
            'total_score' => $total,
            'detail' => [
                'tipo_compra' => $tipoCompra,
                'experiencia' => $input['experiencia'] ?? null,
                'forma_pago' => $input['forma_pago'] ?? null,
                'forma_pago_na_result' => $input['forma_pago_na_result'] ?? null,
                'entrega' => $input['entrega'] ?? null,
                'entrega_na_result' => $input['entrega_na_result'] ?? null,
                'descuento' => $input['descuento'] ?? null,
                'certificaciones' => $input['certificaciones'] ?? null,
                'precios' => $input['precios'] ?? null,
            ],
        ];
    }

    public function resolveWinner(array $scores, ?int $manualWinnerProviderId, string $manualReason): array
    {
        if (empty($scores)) {
            throw new InvalidArgumentException('No hay puntajes para seleccionar ganador.');
        }

        usort($scores, function (array $a, array $b) {
            if ((int)$a['total_score'] === (int)$b['total_score']) {
                if ((int)$a['precios_score'] === (int)$b['precios_score']) {
                    return (int)$a['provider_id'] <=> (int)$b['provider_id'];
                }
                return (int)$b['precios_score'] <=> (int)$a['precios_score'];
            }
            return (int)$b['total_score'] <=> (int)$a['total_score'];
        });

        $top = $scores[0];
        $tied = array_values(array_filter($scores, fn($row) => (int)$row['total_score'] === (int)$top['total_score']));

        if (count($tied) === 1) {
            return ['winner_provider_id' => (int)$top['provider_id'], 'tie_break_reason' => null];
        }

        usort($tied, fn($a, $b) => (int)$b['precios_score'] <=> (int)$a['precios_score']);
        if ((int)$tied[0]['precios_score'] > (int)$tied[1]['precios_score']) {
            return [
                'winner_provider_id' => (int)$tied[0]['provider_id'],
                'tie_break_reason' => 'Empate en puntaje total, definido por mayor puntaje en criterio Precios.',
            ];
        }

        if ($manualWinnerProviderId <= 0 || $manualReason === '') {
            throw new InvalidArgumentException('Empate total y en precios: debes seleccionar manualmente el ganador con justificación.');
        }

        $found = array_values(array_filter($scores, fn($row) => (int)$row['provider_id'] === $manualWinnerProviderId));
        if (!$found) {
            throw new InvalidArgumentException('El proveedor ganador manual no está en la evaluación.');
        }

        return [
            'winner_provider_id' => $manualWinnerProviderId,
            'tie_break_reason' => $manualReason,
        ];
    }

    private function scoreExperiencia(string $option): int
    {
        return match ($option) {
            'LT2' => 5,
            '2TO5' => 10,
            'GT5' => 15,
            default => 0,
        };
    }

    private function scoreFormaPago(string $tipoCompra, string $option, string $naResult): int
    {
        if ($tipoCompra === 'SERVICIOS_TECNICOS') {
            return $naResult === 'CUMPLE' ? 25 : 0;
        }

        return match ($option) {
            'CONTADO' => 10,
            'CREDICONTADO' => 20,
            'CREDITO_30_MAS' => 25,
            default => 0,
        };
    }

    private function scoreEntrega(string $tipoCompra, string $option, string $naResult): int
    {
        if ($tipoCompra === 'SERVICIOS_TECNICOS') {
            return $naResult === 'CUMPLE' ? 20 : 0;
        }

        return match ($option) {
            'MAYOR_10' => 5,
            'IGUAL_10' => 10,
            'MENOR_5' => 20,
            default => 0,
        };
    }

    private function scoreDescuento(string $option): int
    {
        return $option === 'SI' ? 5 : 0;
    }

    private function scoreCertificaciones(string $option): int
    {
        return match ($option) {
            'UNA' => 5,
            'DOS_MAS' => 10,
            default => 0,
        };
    }

    private function scorePrecios(string $option): int
    {
        return match ($option) {
            'MAYOR' => 5,
            'IGUAL' => 15,
            'MENOR' => 25,
            default => 0,
        };
    }
}
