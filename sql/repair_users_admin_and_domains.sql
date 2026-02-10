-- Reparación de usuarios para dominio corporativo aossas.com.
-- Credenciales de acceso administrativo de emergencia:
--   Usuario: admin.portal@aossas.com
--   Contraseña: AdminAOS2026!
-- 1) Desactivar cuentas del dominio antiguo @aos.com (no se borran).
UPDATE users
SET is_active = 0
WHERE LOWER(email) REGEXP '@aos\.com$';

-- 2) Si existe admin.portal@aossas.com, asegurar que sea administrador activo y con clave conocida.
UPDATE users
SET role = 'administrador',
    is_active = 1,
    password_hash = '$2y$12$9Z1x51SbopNoAHJC.CTzoesAydp9mPlr2tRjfoVgCBn3XaY5MX/pa'
WHERE LOWER(email) = 'admin.portal@aossas.com';

-- 3) Si no existe admin.portal@aossas.com y no hay ningún admin activo,
--    crear el usuario mínimo requerido para recuperar el acceso administrativo.
INSERT INTO users (name, email, role, password_hash, is_active, created_at)
SELECT
    'Administrador Portal',
    'admin.portal@aossas.com',
    'administrador',
    '$2y$12$9Z1x51SbopNoAHJC.CTzoesAydp9mPlr2tRjfoVgCBn3XaY5MX/pa',
    1,
    NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE LOWER(email) = 'admin.portal@aossas.com'
)
AND NOT EXISTS (
    SELECT 1 FROM users WHERE role = 'administrador' AND is_active = 1
);
