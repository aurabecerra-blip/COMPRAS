<?php
return [
    'db' => [
        'dsn' => 'mysql:host=localhost;dbname=compras;charset=utf8mb4',
        'user' => 'root',
        'pass' => '',
    ],
    'upload_dir' => __DIR__ . '/../../public/uploads',
    'mail' => [
        'from_email' => 'compras@aos.com',
        'from_name' => 'Sistema de Compras',
        'log_path' => __DIR__ . '/../../storage/mail.log',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_password' => '',
        'use_tls' => true,
    ],
];
