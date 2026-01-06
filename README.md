# Compras AOS

Aplicativo web ligero en PHP 8 + MySQL para control de compras alineado al flujo PR-PO-Recepción-Factura.

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
- Login con `password_hash` / `password_verify` y sesiones.
- Roles: admin, requester, approver, buyer, receiver, accountant.
- Módulos: Dashboard, Solicitudes de compra (PR), Cotizaciones, Órdenes de compra (PO), Recepciones, Facturas, Proveedores, Auditoría y Administración.
- Flujo PR (borrador → enviada → en aprobación → aprobada/rechazada) con bloqueo de creación de PO si la PR no está aprobada.
- Recepción parcial/total y cierre de OC con validación de recepción o justificación.
- Adjuntos (PDF/imagen) en `/public/uploads` registrados en tabla `attachments`.
- Bitácora de auditoría con detalle JSON en `audit_log`.
- Bootstrap con navbar y branding AOS configurable desde `settings`.

## Estructura
- `public/`: punto de entrada `index.php`, assets y uploads.
- `app/`: controladores, repositorios, vistas y utilidades.
- `sql/`: definición de esquema y seeds.

## Seguridad básica
- Validaciones del lado servidor para entradas obligatorias.
- Autorización por roles en acciones sensibles (aprobaciones, compras, recepción, contabilidad, administración).

## Notas
- Las rutas del front se resuelven mediante `index.php?page=<ruta>`.
- El logo por defecto está en `public/assets/aos-logo.svg`; puede cambiarse desde Administración.
