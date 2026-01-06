<?php
session_start();

$config = require __DIR__ . '/config/config.php';

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
