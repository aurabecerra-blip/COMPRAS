<?php
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $normalizedBase = rtrim($scriptDir, '/');
    define('BASE_URL', $normalizedBase === '' ? '/' : $normalizedBase);
}

require __DIR__ . '/../app/bootstrap.php';

$page = $_GET['page'] ?? ($auth->user() ? 'dashboard' : 'login');

$authMiddleware = new AuthMiddleware($auth, $flash);

$authController = new AuthController($auth, $flash, $audit);
$dashboardController = new DashboardController($db, $authMiddleware);
$prController = new PurchaseRequestController(
    new PurchaseRequestRepository($db),
    new SupplierRepository($db),
    new QuotationRepository($db),
    new PurchaseOrderRepository($db),
    new AttachmentRepository($db),
    $audit,
    $auth,
    $flash,
    $settingsRepo,
    $authMiddleware
);
$poController = new PurchaseOrderController(
    $db,
    new PurchaseOrderRepository($db),
    new PurchaseRequestRepository($db),
    new ReceptionRepository($db),
    $audit,
    $auth,
    $flash,
    $authMiddleware
);
$supplierController = new SupplierController(new SupplierRepository($db), $flash, $audit, $auth, $authMiddleware);
$adminController = new AdminController($settingsRepo, new UserRepository($db), $flash, $audit, $auth, $authMiddleware);
$auditController = new AuditController($db, $auth, $authMiddleware);

if ($page === 'login') {
    if ($auth->user()) {
        header('Location: ' . route_to('dashboard'));
        exit;
    }
    $authController->login();
    exit;
}
if ($page === 'do_login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController->handleLogin();
    exit;
}
if ($page === 'logout') {
    $authController->logout();
}

$authMiddleware->check();

switch ($page) {
    case 'dashboard':
        $dashboardController->index();
        break;
    case 'purchase_requests':
        $prController->index();
        break;
    case 'purchase_request_create':
        $prController->create();
        break;
    case 'purchase_request_store':
        $prController->store();
        break;
    case 'purchase_request_edit':
        $prController->edit();
        break;
    case 'purchase_request_update':
        $prController->update();
        break;
    case 'purchase_request_send':
        $prController->send();
        break;
    case 'purchase_request_approve':
        $prController->approve();
        break;
    case 'purchase_request_reject':
        $prController->reject();
        break;
    case 'purchase_request_select_supplier':
        $prController->selectSupplier();
        break;
    case 'quotations':
        $prController->quotations();
        break;
    case 'quotation_store':
        $prController->addQuotation();
        break;
    case 'purchase_order_create':
        $prController->createPo();
        break;
    case 'purchase_orders':
        $poController->index();
        break;
    case 'po_send':
        $poController->sendToSupplier();
        break;
    case 'po_receive':
        $poController->receive();
        break;
    case 'po_close':
        $poController->close();
        break;
    case 'suppliers':
        $supplierController->index();
        break;
    case 'supplier_store':
        $supplierController->store();
        break;
    case 'audit':
        $auditController->index();
        break;
    case 'admin':
        $adminController->index();
        break;
    case 'admin_settings':
        $adminController->updateSettings();
        break;
    case 'admin_user_store':
        $adminController->storeUser();
        break;
    default:
        http_response_code(404);
        echo 'Página no encontrada';
}
