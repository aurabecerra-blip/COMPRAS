-- Reparación de usuarios para dominio corporativo aossas.com.
-- 1) Desactivar cuentas del dominio antiguo @aos.com (no se borran).
UPDATE users
SET is_active = 0
WHERE LOWER(email) REGEXP '@aos\.com$';

-- 2) Si existe aura.becerra@aossas.com, asegurar que sea administrador activo.
UPDATE users
SET role = 'administrador',
    is_active = 1
WHERE LOWER(email) = 'aura.becerra@aossas.com';

-- 3) Si no existe aura.becerra@aossas.com y no hay ningún admin activo,
--    crear el usuario mínimo requerido para recuperar el acceso administrativo.
INSERT INTO users (name, email, role, password_hash, is_active, created_at)
SELECT
    'Aura Becerra',
    'aura.becerra@aossas.com',
    'administrador',
    '$2y$10$0M6N6jQ7mD8.mIshW4VhVe6Tr6zVYw3Lj0R8dkUBaRdOyqK8BPVK6',
    1,
    NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE LOWER(email) = 'aura.becerra@aossas.com'
)
AND NOT EXISTS (
    SELECT 1 FROM users WHERE role = 'administrador' AND is_active = 1
);
