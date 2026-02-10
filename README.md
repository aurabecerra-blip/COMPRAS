# Compras AOS

Aplicativo web en PHP 8 + MySQL para control de compras alineado al flujo ISO 9001 de Solicitud → Cotización → Orden de compra → Recepción.

## Requisitos
- PHP 8 con extensiones `pdo_mysql` y `mbstring`.
- Servidor MySQL.

## Instalación
1. Crear la base de datos y ejecutar el esquema:
   ```sql
   CREATE DATABASE compras CHARACTER SET utf8mb4;
   USE compras;
   SOURCE sql/schema.sql;
   ```
2. Ejecutar la migración de usuarios corporativos:
   ```sql
   SOURCE sql/migration_users_corporate_domain.sql;
   ```
3. Configurar credenciales de BD en `app/config/config.php`.
4. Servir la carpeta `public/` (por ejemplo con `php -S localhost:8000 -t public`).

## Primer uso
- Si no existe ningún `administrador` activo, el sistema mostrará un wizard para crear el primer administrador.
- El correo debe pertenecer al dominio corporativo `@aossas.com`.

## Gestión de usuarios
- Módulo: **Configuración → Usuarios** (solo rol administrador).
- Roles fijos: `administrador`, `aprobador`, `compras`, `recepcion`, `solicitante`.
- Reglas de seguridad:
  - Email único.
  - Solo emails `@aossas.com` (case-insensitive).
  - Contraseñas protegidas con `password_hash` / `password_verify`.
  - Usuarios demo `@aos.com` se migran a estado inactivo.

## Características clave
- Login obligatorio con sesiones.
- Flujo de Solicitud de compra (BORRADOR → ENVIADA → APROBADA / RECHAZADA / CANCELADA) con validaciones de justificación, área, centro de costo e ítems.
- Cotizaciones con PDF, monto y plazo; selección de proveedor y generación de OC desde solicitud aprobada.
- Flujo de OC: CREADA → ENVIADA A PROVEEDOR → RECIBIDA PARCIAL → RECIBIDA TOTAL → CERRADA, con control por recepciones y justificación de cierre parcial.
- Recepción por ítem con evidencia opcional (PDF/imagen).
- Adjuntos almacenados en `/public/uploads` y auditados.
- Bitácora de auditoría con detalle JSON en `audit_log`.
- Bootstrap 5 con branding AOS configurable (`company_name`, `brand_logo_path`, colores).

## Estructura
- `public/`: punto de entrada `index.php`, assets y uploads.
- `app/`: controladores, repositorios, vistas y utilidades.
- `sql/`: definición de esquema, migraciones y seeds.

## Seguridad básica
- Validaciones del lado servidor para entradas obligatorias.
- Autorización por roles en acciones sensibles (aprobaciones, compras, recepción, administración).

## Notas
- Las rutas del front se resuelven mediante `index.php?page=<ruta>`.
- El logo por defecto está en `public/assets/aos-logo.svg`; puede cambiarse desde Configuración.
