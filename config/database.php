<?php
// Path: config/database.php

// جلب البيانات من ملف الـ env لضمان الأمان وعدم تعارض الكلمات المرور
return [
    'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',
    
    'connections' => [
        'mysql' => [
            'driver'    => 'mysql',
            'host'      => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port'      => $_ENV['DB_PORT'] ?? '3306',
            'database'  => $_ENV['DB_DATABASE'] ?? 'u582652079_erp',
            'username'  => $_ENV['DB_USERNAME'] ?? 'u582652079_erpAdmin',
            'password'  => $_ENV['DB_PASSWORD'] ?? 'dsj=Ay1!3S^',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ],
    ],
];