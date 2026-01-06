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
2. Configurar credenciales de BD en `app/config/config.php`.
3. Servir la carpeta `public/` (por ejemplo con `php -S localhost:8000 -t public`).

## Credenciales iniciales
- Usuario administrador: `admin@aos.com`
- Contraseña temporal: `Cambiar123` (cambiar al primer inicio de sesión).

## Características clave
- Login obligatorio con `password_hash` / `password_verify` y sesiones.
- Roles: administrador, solicitante, aprobador, compras, recepcion.
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
- `sql/`: definición de esquema y seeds.

## Seguridad básica
- Validaciones del lado servidor para entradas obligatorias.
- Autorización por roles en acciones sensibles (aprobaciones, compras, recepción, administración).

## Notas
- Las rutas del front se resuelven mediante `index.php?page=<ruta>`.
- El logo por defecto está en `public/assets/aos-logo.svg`; puede cambiarse desde Administración.
