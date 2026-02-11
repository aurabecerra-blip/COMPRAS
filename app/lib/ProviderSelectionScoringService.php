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

    public function buildScoresFromQuotes(array $quotes): array
    {
        if (empty($quotes)) {
            return [];
        }

        $priceOptions = $this->resolvePriceOptions($quotes);
        $result = [];

        foreach ($quotes as $quote) {
            $providerId = (int)($quote['provider_id'] ?? 0);
            if ($providerId <= 0) {
                continue;
            }

            $tipoCompra = (string)($quote['tipo_compra'] ?? 'BIENES');
            $experiencia = $this->scoreExperiencia((string)($quote['experiencia'] ?? ''));
            $formaPago = $this->scoreFormaPago($tipoCompra, (string)($quote['forma_pago'] ?? ''), (string)($quote['forma_pago_na_result'] ?? ''));
            $entrega = $this->scoreEntrega($tipoCompra, (string)($quote['entrega'] ?? ''), (string)($quote['entrega_na_result'] ?? ''));
            $descuento = $this->scoreDescuento((string)($quote['descuento'] ?? 'NO'));
            $certificaciones = $this->scoreCertificaciones((string)($quote['certificaciones'] ?? 'NINGUNA'));

            $priceOption = $priceOptions[$providerId] ?? 'IGUAL';
            $precios = $this->scorePrecios($priceOption);

            $total = $experiencia + $formaPago + $entrega + $descuento + $certificaciones + $precios;

            $result[] = [
                'provider_id' => $providerId,
                'provider_name' => $quote['provider_name'] ?? ('Proveedor #' . $providerId),
                'experiencia_score' => $experiencia,
                'forma_pago_score' => $formaPago,
                'entrega_score' => $entrega,
                'descuento_score' => $descuento,
                'certificaciones_score' => $certificaciones,
                'precios_score' => $precios,
                'total_score' => $total,
                'detail' => [
                    'tipo_compra' => $tipoCompra,
                    'experiencia' => $quote['experiencia'] ?? null,
                    'forma_pago' => $quote['forma_pago'] ?? null,
                    'entrega' => $quote['entrega'] ?? null,
                    'entrega_na_result' => $quote['entrega_na_result'] ?? null,
                    'descuento' => $quote['descuento'] ?? null,
                    'certificaciones' => $quote['certificaciones'] ?? null,
                    'precios' => $priceOption,
                    'valor' => (float)($quote['valor'] ?? 0),
                ],
            ];
        }

        usort($result, function (array $a, array $b) {
            if ((int)$a['total_score'] === (int)$b['total_score']) {
                if ((int)$a['precios_score'] === (int)$b['precios_score']) {
                    return (int)$a['provider_id'] <=> (int)$b['provider_id'];
                }
                return (int)$b['precios_score'] <=> (int)$a['precios_score'];
            }
            return (int)$b['total_score'] <=> (int)$a['total_score'];
        });

        return $result;
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

        $automaticWinnerProviderId = (int)$top['provider_id'];
        $automaticReason = null;

        if (count($tied) === 1) {
            $automaticReason = 'Proveedor seleccionado automáticamente por mayor puntaje en evaluación.';
        } else {
            usort($tied, fn($a, $b) => (int)$b['precios_score'] <=> (int)$a['precios_score']);
            if ((int)$tied[0]['precios_score'] > (int)$tied[1]['precios_score']) {
                $automaticWinnerProviderId = (int)$tied[0]['provider_id'];
                $automaticReason = 'Empate en puntaje total, definido por mayor puntaje en criterio Precios.';
            } elseif ($manualWinnerProviderId <= 0 || $manualReason === '') {
                throw new InvalidArgumentException('Empate total y en precios: debes seleccionar manualmente el ganador con justificación.');
            }
        }

        if ($manualWinnerProviderId > 0) {
            $found = array_values(array_filter($scores, fn($row) => (int)$row['provider_id'] === $manualWinnerProviderId));
            if (!$found) {
                throw new InvalidArgumentException('El proveedor ganador manual no está en la evaluación.');
            }

            if ($manualWinnerProviderId !== $automaticWinnerProviderId && $manualReason === '') {
                throw new InvalidArgumentException('Si seleccionas un ganador diferente al automático, debes registrar la observación de justificación.');
            }

            if ($manualWinnerProviderId !== $automaticWinnerProviderId) {
                return [
                    'winner_provider_id' => $manualWinnerProviderId,
                    'tie_break_reason' => $manualReason,
                ];
            }
        }

        return [
            'winner_provider_id' => $automaticWinnerProviderId,
            'tie_break_reason' => $automaticReason,
        ];
    }

    private function resolvePriceOptions(array $quotes): array
    {
        $pricesByProvider = [];
        foreach ($quotes as $quote) {
            $providerId = (int)($quote['provider_id'] ?? 0);
            if ($providerId <= 0) {
                continue;
            }
            $pricesByProvider[$providerId] = (float)($quote['valor'] ?? 0);
        }

        if (empty($pricesByProvider)) {
            return [];
        }

        $min = min($pricesByProvider);
        $max = max($pricesByProvider);
        $options = [];

        foreach ($pricesByProvider as $providerId => $price) {
            if ($min === $max) {
                $options[$providerId] = 'IGUAL';
                continue;
            }
            if ((float)$price === (float)$min) {
                $options[$providerId] = 'MENOR';
                continue;
            }
            if ((float)$price === (float)$max) {
                $options[$providerId] = 'MAYOR';
                continue;
            }
            $options[$providerId] = 'IGUAL';
        }

        return $options;
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

        if ($tipoCompra === 'BIENES') {
            return match ($option) {
                'IGUAL_10' => 5,
                'MENOR_5' => 20,
                default => 0,
            };
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
