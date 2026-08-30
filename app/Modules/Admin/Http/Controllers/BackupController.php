<?php
// Path: app/Modules/Admin/Http/Controllers/BackupController.php

namespace App\Modules\Admin\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class BackupController extends Controller
{
    private string $basePath;
    private ?PDO $db = null;

    public function __construct()
    {
        ini_set('display_errors', 1);
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            if ($this->db) {
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
    }

    private function resolveId($id = null): ?int
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/admin/backups/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        if (preg_match('#/admin/backups/create#', $uri)) return $this->create($request, $response);
        if (preg_match('#/admin/backups/(\d+)/download#', $uri, $m)) return $this->download($request, $response, (int)$m[1]);
        if (preg_match('#/admin/backups/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/admin/backups/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $backups = [];
        $totalPages = 1;
        $stats = (object)[
            'total' => 0,
            'completed' => 0,
            'latest' => 'لا يوجد'
        ];

        if ($this->db) {
            try {
                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(IF(status = 'completed', 1, 0)) as completed,
                        MAX(created_at) as latest
                    FROM sys_backups
                ")->fetch(PDO::FETCH_OBJ);

                if ($statsData) {
                    $stats->total = (int)($statsData->total ?? 0);
                    $stats->completed = (int)($statsData->completed ?? 0);
                    $stats->latest = $statsData->latest ? date('Y-m-d H:i', strtotime($statsData->latest)) : 'لا يوجد';
                }

                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(filename LIKE :s1 OR created_by LIKE :s2)";
                }

                if ($statusFilter !== '') {
                    $where[] = "status = :status_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("SELECT COUNT(*) FROM sys_backups $whereSql");
                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                }
                if ($statusFilter !== '') $countStmt->bindValue(':status_val', $statusFilter);

                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT * FROM sys_backups
                    $whereSql
                    ORDER BY id DESC 
                    LIMIT :limit OFFSET :offset
                ");

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $stmt->bindValue(':s1', $searchVal);
                    $stmt->bindValue(':s2', $searchVal);
                }
                if ($statusFilter !== '') $stmt->bindValue(':status_val', $statusFilter);
                
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $backups = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            } catch (Throwable $e) {
                error_log("Backup Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/admin/backups/index.php', [
            'backups' => $backups,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $filename = 'backup_erp_' . date('Y-m-d_H-i-s') . '.sql';
            $backupDir = $this->basePath . '/public/uploads/backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0777, true);
            }

            $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $tablesCount = count($tables);

            $sqlScript = "-- ========================================================\n";
            $sqlScript .= "-- NOUR TRUST ERP Database Backup\n";
            $sqlScript .= "-- Created Date: " . date('Y-m-d H:i:s') . "\n";
            $sqlScript .= "-- Total Tables: {$tablesCount}\n";
            $sqlScript .= "-- ========================================================\n\n";
            $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $createTable = $this->db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
                $sqlScript .= "\n-- Table structure for table `{$table}` --\n";
                $sqlScript .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sqlScript .= $createTable[1] . ";\n\n";

                $rows = $this->db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows)) {
                    $sqlScript .= "-- Dumping data for table `{$table}` --\n";
                    foreach ($rows as $row) {
                        $keys = array_keys($row);
                        $values = array_values($row);
                        $escapedVals = array_map(function($v) { 
                            return $v === null ? "NULL" : "'" . addslashes($v) . "'"; 
                        }, $values);
                        $sqlScript .= "INSERT INTO `{$table}` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $escapedVals) . ");\n";
                    }
                }
            }

            $sqlScript .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

            $filePath = $backupDir . $filename;
            file_put_contents($filePath, $sqlScript);

            $bytes = filesize($filePath);
            $sizeFormatted = $bytes >= 1048576 
                ? number_format($bytes / 1048576, 2) . ' MB' 
                : number_format($bytes / 1024, 2) . ' KB';

            $userStr = $_SESSION['user_name'] ?? $_SESSION['user_id'] ?? 'System Admin';

            $stmt = $this->db->prepare("
                INSERT INTO sys_backups (filename, file_size, tables_count, created_by, status) 
                VALUES (?, ?, ?, ?, 'completed')
            ");
            $stmt->execute([$filename, $sizeFormatted, $tablesCount, $userStr]);

            $_SESSION['flash_msg'] = "تم توليد وتوثيق النسخة الاحتياطية ({$filename}) بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء توليد النسخة الاحتياطية: " . $e->getMessage();
        }

        return new RedirectResponse('/ERP/admin/backups');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $backup = null;
        $fileExists = false;

        try {
            if ($this->db) {
                $stmt = $this->db->prepare("SELECT * FROM sys_backups WHERE id = ?");
                $stmt->execute([$id]);
                $backup = $stmt->fetch(PDO::FETCH_OBJ);

                if ($backup) {
                    $filePath = $this->basePath . '/public/uploads/backups/' . $backup->filename;
                    $fileExists = file_exists($filePath);
                }
            }
        } catch (Throwable $e) {}

        if (!$backup) {
            $_SESSION['flash_err'] = "ملف النسخة الاحتياطية غير موجود بالمستندات.";
            return new RedirectResponse('/ERP/admin/backups');
        }

        return $this->renderView('/resources/views/admin/backups/show.php', [
            'backup' => $backup,
            'fileExists' => $fileExists
        ], $response);
    }

    public function download(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $stmt = $this->db->prepare("SELECT filename FROM sys_backups WHERE id = ?");
                $stmt->execute([$id]);
                $backup = $stmt->fetch(PDO::FETCH_OBJ);

                if ($backup) {
                    $filePath = $this->basePath . '/public/uploads/backups/' . $backup->filename;
                    if (file_exists($filePath)) {
                        while (ob_get_level() > 0) {
                            ob_end_clean();
                        }

                        header('Content-Description: File Transfer');
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                        header('Expires: 0');
                        header('Cache-Control: must-revalidate');
                        header('Pragma: public');
                        header('Content-Length: ' . filesize($filePath));
                        readfile($filePath);
                        exit;
                    }
                }
            }
            $_SESSION['flash_err'] = "الملف الفيزيائي غير موجود بالسيرفر.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء التحميل: " . $e->getMessage();
        }

        return new RedirectResponse('/ERP/admin/backups');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $stmt = $this->db->prepare("SELECT filename FROM sys_backups WHERE id = ?");
                $stmt->execute([$id]);
                $backup = $stmt->fetch(PDO::FETCH_OBJ);

                if ($backup) {
                    $filePath = $this->basePath . '/public/uploads/backups/' . $backup->filename;
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }

                $this->db->prepare("DELETE FROM sys_backups WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف سجل وملف النسخة الاحتياطية بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = "خطأ أثناء الحذف: " . $e->getMessage();
        }

        return new RedirectResponse('/ERP/admin/backups');
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("View File Missing: " . htmlspecialchars($fullPath));
        }

        try {
            // إسناد المخرجات لـ $content لتتوافق مع layouts/app.php
            ob_start();
            include $fullPath;
            $content = ob_get_clean();

            ob_start();
            include $this->basePath . '/resources/views/layouts/app.php';
            $finalOutput = ob_get_clean();

            return $response->setContent($finalOutput)->setHeader('Content-Type', 'text/html; charset=UTF-8');
        } catch (Throwable $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            die("View Render Error: " . htmlspecialchars($e->getMessage()));
        }
    }
}