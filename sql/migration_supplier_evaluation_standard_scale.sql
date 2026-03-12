-- Estandariza opciones de evaluación/reevaluación a escala uniforme
-- Excelente (100%), Bueno (75%), Regular (50%), Deficiente (0%)

-- 1) Evaluaciones de proveedor (módulo ISO)
UPDATE supplier_evaluation_details
SET option_key = CASE
        WHEN option_key IN ('on_time', 'meets', 'full', 'no_claims', 'complete') THEN 'excellent'
        WHEN option_key = 'breach' THEN 'regular'
        WHEN option_key IN ('partial', 'timely') THEN 'regular'
        WHEN option_key IN ('not_meets', 'none', 'untimely', 'incomplete') THEN 'deficient'
        ELSE option_key
    END,
    option_label = CASE
        WHEN option_key IN ('excellent', 'on_time', 'meets', 'full', 'no_claims', 'complete') THEN 'Excelente'
        WHEN option_key IN ('good') THEN 'Bueno'
        WHEN option_key IN ('regular', 'breach', 'partial', 'timely') THEN 'Regular'
        WHEN option_key IN ('deficient', 'not_meets', 'none', 'untimely', 'incomplete') THEN 'Deficiente'
        ELSE option_label
    END,
    notes = NULL,
    score = CASE
        WHEN criterion_code = 'delivery_time' THEN
            CASE
                WHEN option_key IN ('excellent', 'on_time', 'meets', 'full', 'no_claims', 'complete') THEN 20
                WHEN option_key = 'good' THEN 15
                WHEN option_key IN ('regular', 'breach', 'partial', 'timely') THEN 10
                WHEN option_key IN ('deficient', 'not_meets', 'none', 'untimely', 'incomplete') THEN 0
                ELSE score
            END
        WHEN criterion_code = 'quality' THEN
            CASE
                WHEN option_key IN ('excellent', 'on_time', 'meets', 'full', 'no_claims', 'complete') THEN 40
                WHEN option_key = 'good' THEN 30
                WHEN option_key IN ('regular', 'breach', 'partial', 'timely') THEN 20
                WHEN option_key IN ('deficient', 'not_meets', 'none', 'untimely', 'incomplete') THEN 0
                ELSE score
            END
        WHEN criterion_code IN ('after_sales', 'sqr') THEN
            CASE
                WHEN option_key IN ('excellent', 'on_time', 'meets', 'full', 'no_claims', 'complete') THEN 10
                WHEN option_key = 'good' THEN 8
                WHEN option_key IN ('regular', 'breach', 'partial', 'timely') THEN 5
                WHEN option_key IN ('deficient', 'not_meets', 'none', 'untimely', 'incomplete') THEN 0
                ELSE score
            END
        WHEN criterion_code = 'documents' THEN
            CASE
                WHEN option_key IN ('excellent', 'on_time', 'meets', 'full', 'no_claims', 'complete') THEN 20
                WHEN option_key = 'good' THEN 15
                WHEN option_key IN ('regular', 'breach', 'partial', 'timely') THEN 10
                WHEN option_key IN ('deficient', 'not_meets', 'none', 'untimely', 'incomplete') THEN 0
                ELSE score
            END
        ELSE score
    END;

UPDATE supplier_evaluations e
INNER JOIN (
    SELECT evaluation_id, COALESCE(SUM(score), 0) AS total_score
    FROM supplier_evaluation_details
    GROUP BY evaluation_id
) d ON d.evaluation_id = e.id
SET e.total_score = d.total_score,
    e.status_label = CASE WHEN d.total_score >= 80 THEN 'Aprobado' ELSE 'No aprobado' END;

-- 2) Reevaluaciones de proveedor (módulo A)
UPDATE provider_reevaluation_items
SET selected_option = CASE
        WHEN selected_option IN ('on_time', 'meets', 'full', 'no_claims', 'complete') THEN 'excellent'
        WHEN selected_option = 'breach' THEN 'regular'
        WHEN selected_option IN ('partial', 'timely') THEN 'regular'
        WHEN selected_option IN ('not_meets', 'none', 'untimely', 'incomplete') THEN 'deficient'
        ELSE selected_option
    END,
    selected_label = CASE
        WHEN selected_option IN ('excellent', 'on_time', 'meets', 'full', 'no_claims', 'complete') THEN 'Excelente'
        WHEN selected_option IN ('good') THEN 'Bueno'
        WHEN selected_option IN ('regular', 'breach', 'partial', 'timely') THEN 'Regular'
        WHEN selected_option IN ('deficient', 'not_meets', 'none', 'untimely', 'incomplete') THEN 'Deficiente'
        ELSE selected_label
    END,
    extra_value = NULL,
    item_score = CASE
        WHEN criterion_code = 'delivery_time' THEN
            CASE
                WHEN selected_option IN ('excellent', 'on_time', 'meets', 'full', 'no_claims', 'complete') THEN 20
                WHEN selected_option = 'good' THEN 15
                WHEN selected_option IN ('regular', 'breach', 'partial', 'timely') THEN 10
                WHEN selected_option IN ('deficient', 'not_meets', 'none', 'untimely', 'incomplete') THEN 0
                ELSE item_score
            END
        WHEN criterion_code = 'quality' THEN
            CASE
                WHEN selected_option IN ('excellent', 'on_time', 'meets', 'full', 'no_claims', 'complete') THEN 40
                WHEN selected_option = 'good' THEN 30
                WHEN selected_option IN ('regular', 'breach', 'partial', 'timely') THEN 20
                WHEN selected_option IN ('deficient', 'not_meets', 'none', 'untimely', 'incomplete') THEN 0
                ELSE item_score
            END
        WHEN criterion_code IN ('after_sales', 'sqr') THEN
            CASE
                WHEN selected_option IN ('excellent', 'on_time', 'meets', 'full', 'no_claims', 'complete') THEN 10
                WHEN selected_option = 'good' THEN 8
                WHEN selected_option IN ('regular', 'breach', 'partial', 'timely') THEN 5
                WHEN selected_option IN ('deficient', 'not_meets', 'none', 'untimely', 'incomplete') THEN 0
                ELSE item_score
            END
        WHEN criterion_code = 'documents' THEN
            CASE
                WHEN selected_option IN ('excellent', 'on_time', 'meets', 'full', 'no_claims', 'complete') THEN 20
                WHEN selected_option = 'good' THEN 15
                WHEN selected_option IN ('regular', 'breach', 'partial', 'timely') THEN 10
                WHEN selected_option IN ('deficient', 'not_meets', 'none', 'untimely', 'incomplete') THEN 0
                ELSE item_score
            END
        ELSE item_score
    END;

UPDATE provider_reevaluations r
INNER JOIN (
    SELECT reevaluation_id, COALESCE(SUM(item_score), 0) AS total_score
    FROM provider_reevaluation_items
    GROUP BY reevaluation_id
) d ON d.reevaluation_id = r.id
SET r.total_score = d.total_score,
    r.updated_at = NOW();
