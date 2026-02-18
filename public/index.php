<?php
if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $normalizedBase = rtrim($scriptDir, '/');
    define('BASE_URL', $normalizedBase === '' ? '/' : $normalizedBase);
}

require __DIR__ . '/../app/bootstrap.php';

$page = $_GET['page'] ?? ($auth->user() ? 'dashboard' : 'login');

$authMiddleware = new AuthMiddleware($auth, $flash);
$trackingController = new TrackingController(new PurchaseRequestRepository($db), $settingsRepo, $flash);

$authController = new AuthController($auth, $flash, $audit, $userRepo);
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
    $notificationService,
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

$providerQuoteController = new ProviderQuoteController(
    new ProviderQuoteRepository($db),
    new PurchaseRequestRepository($db),
    new SupplierRepository($db),
    new ProviderSelectionRepository($db),
    $audit,
    $auth,
    $flash,
    $authMiddleware
);
$providerSelectionController = new ProviderSelectionController(
    new PurchaseRequestRepository($db),
    new SupplierRepository($db),
    new ProviderQuoteRepository($db),
    new ProviderSelectionRepository($db),
    new ProviderSelectionScoringService(),
    new PdfGeneratorService($settingsRepo, $companyRepo),
    $audit,
    $auth,
    $flash,
    $authMiddleware
);
$supplierEvaluationController = new SupplierEvaluationController(
    new SupplierRepository($db),
    new SupplierEvaluationRepository($db),
    new SupplierEvaluationCalculator(),
    $notificationService,
    new SupplierEvaluationPdfBuilder($settingsRepo, $companyRepo),
    $flash,
    $audit,
    $auth,
    $authMiddleware
);

$adminController = new AdminController(
    $settingsRepo,
    $companyRepo,
    $userRepo,
    $notificationTypes,
    $notificationLogs,
    $notificationService,
    $flash,
    $audit,
    $auth,
    $authMiddleware
);
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

if ($page === 'first_use') {
    $authController->firstUse();
    exit;
}
if ($page === 'do_first_use' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController->handleFirstUse();
    exit;
}
if ($page === 'logout') {
    $authController->logout();
}

$publicPages = ['track', 'supplier_evaluation_pdf'];
if (in_array($page, $publicPages, true)) {
    if ($page === 'track') {
        $trackingController->show();
    } elseif ($page === 'supplier_evaluation_pdf') {
        $supplierEvaluationController->downloadPdf();
    }
    exit;
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
    case 'supplier_update':
        $supplierController->update();
        break;
    case 'supplier_delete':
        $supplierController->delete();
        break;
    case 'suppliers_export_template':
        $supplierController->exportTemplate();
        break;
    case 'suppliers_export':
        $supplierController->exportAll();
        break;
    case 'suppliers_import':
        $supplierController->importBulk();
        break;

    case 'provider_selection':
        $providerQuoteController->index();
        break;
    case 'provider_quote_store':
        $providerQuoteController->store();
        break;
    case 'provider_selection_evaluate':
        $providerSelectionController->evaluate();
        break;
    case 'provider_selection_close':
        $providerSelectionController->close();
        break;
    case 'provider_selection_pdf':
        $providerSelectionController->pdf();
        break;

    case 'supplier_evaluations':
        $supplierEvaluationController->index();
        break;
    case 'supplier_evaluation_store':
        $supplierEvaluationController->store();
        break;
    case 'supplier_evaluation_pdf':
        $supplierEvaluationController->downloadPdf();
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
    case 'admin_users':
        $adminController->users();
        break;
    case 'admin_company_switch':
        $adminController->switchActiveCompany();
        break;
    case 'admin_notifications':
        $adminController->updateNotifications();
        break;
    case 'admin_notifications_test':
        $adminController->sendTestNotification();
        break;
    case 'admin_notification_types':
        $adminController->updateNotificationTypes();
        break;
    case 'admin_notification_type_create':
        $adminController->createNotificationType();
        break;
    case 'admin_user_store':
        $adminController->storeUser();
        break;
    case 'admin_user_update':
        $adminController->updateUser();
        break;
    default:
        http_response_code(404);
        echo 'Página no encontrada';
}
