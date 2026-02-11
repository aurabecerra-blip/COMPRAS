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
3. (Opcional) Si necesitas recuperar acceso administrativo, ejecuta:
   ```sql
   SOURCE sql/repair_users_admin_and_domains.sql;
   ```
   Esto garantiza el usuario `admin.portal@aossas.com` con contraseña `AdminAOS2026!`.
4. Configurar credenciales de BD en `app/config/config.php`.
5. Servir la carpeta `public/` (por ejemplo con `php -S localhost:8000 -t public`).

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
- Módulo de **Evaluación / Reevaluación de proveedores** con criterios ponderados, histórico por proveedor/fecha, trazabilidad de líder evaluador y envío automático por correo al proveedor.
- Bootstrap 5 con branding AOS configurable (`company_name`, `brand_logo_path`, colores).

## Evaluación de proveedores
- Acceso: menú **Evaluación proveedores** para roles `lider` y `administrador`.
- Cada evaluación guarda un campo de **observaciones** y genera automáticamente un **PDF** con logo AOS en `/public/uploads/evaluations`.
- Encabezado registrado por evaluación:
  - proveedor (nombre, NIT, servicio),
  - fecha automática,
  - líder evaluador (usuario autenticado),
  - puntaje total,
  - estado (`Aprobado`, `Condicional`, `No aprobado`).
- Cálculo automático (máximo 100 puntos):
  1. Tiempos de entrega (20): a tiempo = 20, incumplimiento = descuento de 2 por evento (mínimo 0).
  2. Calidad (40): cumple = 40, no cumple = 0.
  3. Postventa/garantías (10): cumplimiento oportuno = 10, parcial/no cumple = 0.
  4. Atención a SQR (10): sin quejas = 10, atención 1-5 días = 5, no oportuna = 0.
  5. Documentos (20): completos = 20, incompletos/demora = 0.
- Clasificación automática:
  - `>= 80`: Aprobado
  - `60-79`: Condicional
  - `< 60`: No aprobado
- Al guardar, se envía correo automático usando la configuración central de notificaciones (`settings`) y el tipo `supplier_evaluation_completed`.

### Prueba rápida del módulo
1. Crear/actualizar un usuario con rol `lider` o `administrador`.
2. Registrar un proveedor con correo válido, NIT y servicio.
3. Ingresar a **Evaluación proveedores**, diligenciar criterios y guardar.
4. Verificar en pantalla el histórico, puntaje y estado.
5. Verificar en `notification_logs` que se registró el envío al correo del proveedor.

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

## Módulo de Evaluación y Selección de Proveedores por Cotizaciones (mínimo 3)

### Instalación
1. Ejecuta migración del módulo:
   ```sql
   SOURCE sql/migration_provider_selection_module.sql;
   ```
2. (Opcional) carga datos demo:
   ```sql
   SOURCE sql/seed_provider_selection_demo.sql;
   ```

### Flujo de uso
1. Ingresa a **Solicitudes** y abre el botón **Cotizaciones y Selección de Proveedor**.
2. Registra cotizaciones (mínimo 3 proveedores diferentes para cerrar):
   - proveedor,
   - tipo de compra,
   - valor/moneda,
   - plazo,
   - forma de pago,
   - anexos (pdf/jpg/png/xlsx, máximo 10MB por archivo).
3. En **Evaluación comparativa** diligencia criterios por proveedor y usa **Guardar borrador**.
4. Usa **Cerrar y seleccionar ganador** para:
   - validar mínimo de 3 proveedores distintos,
   - resolver ganador por mayor puntaje,
   - desempatar por Precios,
   - forzar ganador manual con justificación si persiste empate,
   - generar PDF de análisis en:
     `/public/storage/seleccion_proveedor/{purchase_request_id}/analisis_seleccion_{timestamp}.pdf`.
5. Descarga el PDF desde el mismo módulo (enlace **Descargar análisis PDF**).

### Almacenamiento de archivos
- Cotizaciones: `/public/storage/cotizaciones/{purchase_request_id}/{provider_id}/`
- PDF evaluación cerrada: `/public/storage/seleccion_proveedor/{purchase_request_id}/`

### Componentes implementados
- Controladores: `ProviderQuoteController`, `ProviderSelectionController`
- Repositorios: `ProviderQuoteRepository`, `ProviderSelectionRepository`
- Servicios: `ProviderSelectionScoringService`, `PdfGeneratorService`
- Vista principal: `app/views/provider_selection/index.php`

## Nuevos módulos separados (2026)

### 1) Módulo A · Reevaluación de Proveedor
- Rutas:
  - `index.php?page=reevaluations`
  - `index.php?page=reevaluation_store` (POST)
- Componentes:
  - Controller: `app/controllers/ReevaluationController.php`
  - Repository: `app/repositories/ReevaluationRepository.php`
  - Service: `app/lib/ReevaluationService.php`
  - Vista: `app/views/reevaluations/index.php`
- Funcionalidades:
  - Cálculo por ítem y total (0-100) en frontend (JS) y backend.
  - Guarda cabecera + detalle de criterios.
  - Genera PDF en `/public/storage/reevaluaciones`.
  - Envía correo al proveedor con resumen y PDF adjunto. Si falla email, la reevaluación queda guardada y se registra estado/error.

### 2) Módulo B · Evaluación de Selección de Proveedor
- Rutas:
  - `index.php?page=supplier_selection&id={purchase_request_id}`
  - `index.php?page=supplier_selection_quote_store` (POST)
  - `index.php?page=supplier_selection_decide` (POST)
- Componentes:
  - Controller: `app/controllers/SupplierSelectionController.php`
  - Repository: `app/repositories/SupplierSelectionRepository.php`
  - Service: `app/lib/SupplierSelectionService.php`
  - Vista: `app/views/supplier_selection/index.php`
- Reglas clave:
  - Mínimo 3 cotizaciones de 3 proveedores distintos para poder decidir.
  - Evidencias por archivo (PDF/imagen/Excel, máximo 10MB).
  - Puntajes por criterio (precio, entrega, pago, garantía, técnico), total, ranking y ganador.
  - Si el ganador no es el de mayor puntaje, exige justificación.
  - Genera Acta PDF en `/public/storage/actas_seleccion/{purchase_request_id}`.

### Migración SQL
Ejecuta:
```sql
SOURCE sql/migration_supplier_reevaluation_and_selection_modules.sql;
```


## Módulo: Selección de Proveedor por Cotizaciones (después de solicitud APROBADA)

> Alcance implementado: **solo selección por cotizaciones**. Este flujo no requiere crear ni usar módulo de reevaluación para operar.

### Migraciones
Ejecuta en este orden:
```sql
SOURCE sql/migration_supplier_reevaluation_and_selection_modules.sql;
SOURCE sql/migration_supplier_selection_evaluation_upgrade.sql;
SOURCE sql/migration_supplier_selection_quotations_upgrade.sql;
```

### Endpoints/rutas
- `index.php?page=supplier_selection&id={purchase_request_id}`: pantalla principal del proceso.
- `index.php?page=supplier_selection_quote_store` (POST): registro de cotización con adjuntos y validaciones.
- `index.php?page=supplier_selection_decide` (POST): cálculo backend de puntajes, ranking, selección de ganador y acta PDF.
- `index.php?page=supplier_selection_pdf&id={purchase_request_id}`: visualización del Acta PDF.

### Reglas implementadas
- Solo se habilita para solicitudes de compra en estado `APROBADA`.
- Estado del proceso: `BORRADOR` → `EN_EVALUACION` (al tener 3 proveedores distintos) → `SELECCIONADO`.
- Validación obligatoria de mínimo 3 cotizaciones de proveedores distintos para cerrar selección.
- Campos nuevos de cotización: descuento (tipo/valor), experiencia, certificaciones (técnicas/comerciales/lista), y archivos por tipo.
- Archivo de cotización obligatorio siempre.
- Si hay certificaciones: lista + archivo de certificaciones obligatorios.
- Criterio postventa/garantías con tres niveles obligatorios:
  - Cumple oportunamente con todas las garantías y soporte técnico = 10
  - Cumple parcialmente con las garantías y soporte técnico = 5
  - No cumple con las garantías ni soporte técnico = 0
- Puntajes por criterio (máximo 100):
  - Precio neto (35), Descuento (10), Entrega (20), Pago (10), Garantía/postventa (10), Experiencia (10), Certificaciones (5).
- Selección por mayor puntaje por defecto; si se escoge otro proveedor, exige justificación.
- Acta PDF almacenada en: `/public/storage/actas_seleccion/{purchase_request_id}`.
- Si falla el PDF, la selección queda guardada y se registra log de error.
