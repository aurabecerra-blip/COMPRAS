<?php
require __DIR__ . '/../app/bootstrap.php';

$page = $_GET['page'] ?? 'dashboard';

$authController = new AuthController($auth, $flash, $audit);
$dashboardController = new DashboardController($db);
$prController = new PurchaseRequestController(
    new PurchaseRequestRepository($db),
    new SupplierRepository($db),
    new QuotationRepository($db),
    new PurchaseOrderRepository($db),
    new AttachmentRepository($db),
    $audit,
    $auth,
    $flash,
    $settingsRepo
);
$poController = new PurchaseOrderController(
    $db,
    new PurchaseOrderRepository($db),
    new PurchaseRequestRepository($db),
    new ReceptionRepository($db),
    new InvoiceRepository($db),
    $audit,
    $auth,
    $flash
);
$supplierController = new SupplierController(new SupplierRepository($db), $flash, $audit, $auth);
$adminController = new AdminController($settingsRepo, new UserRepository($db), $flash, $audit, $auth);
$auditController = new AuditController($db, $auth);

if ($page === 'login') {
    $authController->login();
    exit;
}
if ($page === 'login_submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController->handleLogin();
    exit;
}
if ($page === 'logout') {
    $auth->logout();
    header('Location: /index.php?page=login');
    exit;
}

if (!$auth->user()) {
    header('Location: /index.php?page=login');
    exit;
}

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
    case 'purchase_request_start_approval':
        $prController->startApproval();
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
        $poController->show();
        break;
    case 'po_receive':
        $poController->receive();
        break;
    case 'po_invoice':
        $poController->addInvoice();
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
