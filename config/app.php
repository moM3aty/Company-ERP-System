<?php
// Path: config/app.php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Nour Trust ERP',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    
    // تم التعديل هنا: إزالة كلمة public ليعمل مع Hostinger
    'url' => $_ENV['APP_URL'] ?? 'https://nourtrust.com/ERP',
    
    'timezone' => 'Africa/Cairo',
    
    'locale' => $_ENV['APP_LOCALE'] ?? 'ar',
    'fallback_locale' => 'en',
    'supported_locales' => ['ar', 'en'],
    
    'theme' => 'light',
    
    'logo' => 'image_ba1080.png',
    
    'tenant_mode' => 'multi_database',
    'tenant_db_prefix' => 'tenant_',


];