<?php
// Path: public/index.php

/**
 * البداية (Entry Point) للتطبيق - مع محرك التقاط الأخطاء الخفية (Shutdown Handler)
 */

// 1. تفعيل إظهار كافة الأخطاء في أعلى مستوى
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// 2. كاشف الأخطاء القاتلة (يمنع الشاشة البيضاء ويطبع السبب فوراً)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // تنظيف أي بافر معلق
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: text/html; charset=UTF-8');
        echo "<div style='padding:30px; background:#ffffff; color:#dc2626; font-family:monospace; direction:ltr; text-align:left; border:3px solid #dc2626; margin:20px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.1);'>";
        echo "<h2 style='margin-0 0 10px 0;'>🚨 Fatal Error Captured</h2>";
        echo "<p style='font-size:1.1rem;'><b>Message:</b> " . htmlspecialchars($error['message']) . "</p>";
        echo "<p><b>File:</b> " . htmlspecialchars($error['file']) . "</p>";
        echo "<p><b>Line:</b> " . $error['line'] . "</p>";
        echo "</div>";
    }
});

header('Content-Type: text/html; charset=UTF-8');

$basePath = dirname(__DIR__);

// تحميل Autoloader
if (file_exists($basePath . '/vendor/autoload.php')) {
    require_once $basePath . '/vendor/autoload.php';
} else {
    die("لم يتم العثور على مجلد vendor/autoload.php");
}

use Core\Application;
use Core\Http\Request;
use Core\Http\Response;
use Core\Config\Config;
use Core\Log\Logger;
use Core\Http\Middleware\ErrorMiddleware;

// 3. قراءة ملف الـ .env
$envPath = $basePath . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \"'");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// 4. إنشاء كائن التطبيق
$app = new Application($basePath);
$GLOBALS['app'] = $app;

// 5. إعداد الكائنات الأساسية
$config = new Config($basePath . '/config');
$logger = new Logger($basePath . '/storage/logs');

$app->singleton(Config::class, $config);
$app->singleton(Logger::class, $logger);

// 6. الاتصال بقاعدة البيانات
try {
    $dbConfig = $config->get('database.connections.mysql');
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}";
    
    $pdo = new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $app->singleton(\PDO::class, $pdo);
} catch (\PDOException $e) {
    $logger->error("Database Connection Failed: " . $e->getMessage());
    die("<div style='padding:30px; color:red; font-family:sans-serif;'><h3>خطأ في الاتصال بقاعدة البيانات</h3><p>" . $e->getMessage() . "</p></div>");
}

$app->addGlobalMiddleware(new ErrorMiddleware($logger, true));

$router = $app->getRouter();

// 7. URI Normalization
$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($rawUri, PHP_URL_PATH);
$path = preg_replace('#^/ERP(/public)?(/index\.php)?#i', '', $path);
$path = preg_replace('#^/public(/index\.php)?#i', '', $path);
$path = empty($path) ? '/' : '/' . ltrim($path, '/');

$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
$_SERVER['REQUEST_URI'] = $path . $query;
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// 8. تحميل مسارات الموديولات ديناميكياً
$modulesPath = $basePath . '/app/Modules';
if (is_dir($modulesPath)) {
    $modules = array_diff(scandir($modulesPath), ['.', '..']);
    foreach ($modules as $module) {
        $routeFile = "{$modulesPath}/{$module}/Routes/routes.php";
        if (file_exists($routeFile)) {
            require_once $routeFile;
        }
    }
}

$webRoutes = $basePath . '/routes/web.php';
if (file_exists($webRoutes)) {
    require_once $webRoutes;
}

// 9. تشغيل التطبيق وإرسال الرد
$request = Request::capture();
$response = $app->run($request);

if ($response instanceof Response) {
    $response->send();
} else {
    echo $response;
}