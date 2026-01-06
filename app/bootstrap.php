<?php
session_start();

$config = require __DIR__ . '/config/config.php';

if (!defined('BASE_URL')) {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $normalizedBase = rtrim($scriptDir, '/');
    define('BASE_URL', $normalizedBase === '' ? '/' : $normalizedBase);
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $normalizedBase = rtrim(BASE_URL, '/');
        $normalizedPath = '/' . ltrim($path, '/');
        if (str_starts_with($normalizedPath, $normalizedBase . '/')) {
            return $normalizedPath;
        }
        return $normalizedBase . $normalizedPath;
    }
}

if (!function_exists('route_to')) {
    function route_to(string $page = 'dashboard', array $params = []): string
    {
        $query = http_build_query(array_merge(['page' => $page], $params));
        return base_url('/index.php' . ($query ? '?' . $query : ''));
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        return base_url('/' . ltrim($path, '/'));
    }
}

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/lib/' . $class . '.php',
        __DIR__ . '/repositories/' . $class . '.php',
        __DIR__ . '/controllers/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

$db = new Database($config['db']['dsn'], $config['db']['user'], $config['db']['pass']);
$flash = new Flash();
$auth = new Auth($db, $flash);
$audit = new AuditLogger($db);
$settingsRepo = new SettingsRepository($db);
$mailer = new Mailer($config['mail']);

if (!function_exists('setting_list')) {
    function setting_list(SettingsRepository $repo, string $key, array $fallback = []): array
    {
        $raw = $repo->get($key, '');
        if ($raw === '') {
            return $fallback;
        }
        $items = preg_split('/[\r\n,]+/', $raw) ?: [];
        $items = array_values(array_unique(array_filter(array_map('trim', $items), fn($i) => $i !== '')));
        return $items ?: $fallback;
    }
}
