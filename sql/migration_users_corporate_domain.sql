-- Agrega soporte de estado activo/inactivo y elimina dependencia de usuarios demo.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER password_hash;


-- Migración de usuarios demo: desactivar cuentas de dominio @aos.com.
UPDATE users
SET is_active = 0
WHERE LOWER(email) LIKE '%@aos.com';

-- Opcional: borrar usuarios demo no referenciados por historial.
-- DELETE u FROM users u
-- LEFT JOIN purchase_requests pr ON pr.requester_id = u.id
-- LEFT JOIN receipts r ON r.received_by = u.id
-- LEFT JOIN attachments a ON a.uploaded_by = u.id
-- LEFT JOIN audit_log al ON al.user_id = u.id
-- WHERE LOWER(u.email) LIKE '%@aos.com'
--   AND pr.id IS NULL
--   AND r.id IS NULL
--   AND a.id IS NULL
--   AND al.id IS NULL;
