<?php
// Path: app/Modules/Projects/Http/Controllers/ProjectInvoiceController.php

namespace App\Modules\Projects\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class ProjectInvoiceController extends Controller
{
    private string $basePath;
    private ?PDO $db = null;

    public function __construct()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
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
        if (preg_match('#/projects/invoices/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/projects/invoices/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/projects/invoices/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/projects/invoices/(\d+)/status#', $uri, $m)) return $this->updateStatus($request, $response, (int)$m[1]);
        if (preg_match('#/projects/invoices/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/projects/invoices/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $projectId = trim($_GET['project_id'] ?? '');
        $typeFilter = trim($_GET['invoice_type'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $invoices = [];
        $projects = [];
        $stats = (object)[
            'total_invoices' => 0,
            'total_net_amount' => 0,
            'total_paid' => 0,
            'total_remaining' => 0,
            'approved_count' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $projects = $this->db->query("SELECT id, code, name_ar FROM projects ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];

                $where = ["1=1"];
                $params = [];

                if ($search !== '') {
                    $where[] = "(i.invoice_number LIKE ? OR i.description LIKE ? OR p.name_ar LIKE ? OR c.name_ar LIKE ?)";
                    $like = "%{$search}%";
                    $params = array_merge($params, [$like, $like, $like, $like]);
                }

                if ($projectId !== '') {
                    $where[] = "i.project_id = ?";
                    $params[] = (int)$projectId;
                }

                if ($typeFilter !== '') {
                    $where[] = "i.invoice_type = ?";
                    $params[] = $typeFilter;
                }

                if ($statusFilter !== '') {
                    $where[] = "i.status = ?";
                    $params[] = $statusFilter;
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) 
                    FROM project_invoices i
                    LEFT JOIN projects p ON i.project_id = p.id
                    LEFT JOIN customers c ON i.customer_id = c.id
                    $whereSql
                ");
                $countStmt->execute($params);
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT i.*, p.name_ar as project_name, p.code as project_code, c.name_ar as customer_name
                    FROM project_invoices i
                    LEFT JOIN projects p ON i.project_id = p.id
                    LEFT JOIN customers c ON i.customer_id = c.id
                    $whereSql
                    ORDER BY i.invoice_date DESC, i.id DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute($params);
                $invoices = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_invoices,
                        COALESCE(SUM(net_amount), 0) as total_net_amount,
                        COALESCE(SUM(paid_amount), 0) as total_paid,
                        COALESCE(SUM(net_amount - paid_amount), 0) as total_remaining,
                        SUM(IF(status IN ('approved','partially_paid','paid'), 1, 0)) as approved_count
                    FROM project_invoices
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) {
                    $stats = $statsData;
                }

            } catch (Throwable $e) {
                error_log("Project Invoices Index Error: " . $e->getMessage());
            }
        }

        $currentPage = $page;

        return $this->renderView('/resources/views/projects/invoices/index.php', [
            'invoices' => $invoices,
            'projects' => $projects,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage,
            'search' => $search,
            'projectId' => $projectId,
            'typeFilter' => $typeFilter,
            'statusFilter' => $statusFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        $invoice = null; 
        $projects = [];
        $customers = [];
        $autoCode = 'INV-PRJ-' . date('Y') . '-0001';

        if ($this->db) {
            try {
                $projects = $this->db->query("SELECT id, code, name_ar, customer_id, contract_value FROM projects ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $customers = $this->db->query("SELECT id, name_ar FROM customers ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                $nextSeq = (int)$this->db->query("SELECT COUNT(*) FROM project_invoices")->fetchColumn() + 1;
                $autoCode = 'INV-PRJ-' . date('Y') . '-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/projects/invoices/create.php', [
            'invoice' => $invoice,
            'projects' => $projects,
            'customers' => $customers,
            'autoCode' => $autoCode
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['invoice_number']) || empty($data['project_id']) || empty($data['invoice_date'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية للمستخلص/الفاتورة.");
            }

            $total = !empty($data['total_amount']) ? (float)$data['total_amount'] : 0.00;
            $deductions = !empty($data['deductions_amount']) ? (float)$data['deductions_amount'] : 0.00;
            $tax = !empty($data['tax_amount']) ? (float)$data['tax_amount'] : 0.00;
            $net = max(0, ($total - $deductions) + $tax);

            // جلب العميل المعين للمشروع تلقائياً إن لم يحدد
            $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
            if (!$customerId) {
                $pStmt = $this->db->prepare("SELECT customer_id FROM projects WHERE id = ?");
                $pStmt->execute([(int)$data['project_id']]);
                $customerId = $pStmt->fetchColumn() ?: null;
            }

            $stmt = $this->db->prepare("
                INSERT INTO project_invoices 
                (invoice_number, project_id, customer_id, invoice_date, due_date, period_start, period_end, invoice_type, total_amount, deductions_amount, tax_amount, net_amount, paid_amount, status, description, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['invoice_number']),
                (int)$data['project_id'],
                $customerId,
                $data['invoice_date'],
                !empty($data['due_date']) ? $data['due_date'] : null,
                !empty($data['period_start']) ? $data['period_start'] : null,
                !empty($data['period_end']) ? $data['period_end'] : null,
                $data['invoice_type'] ?? 'progress_claim',
                $total,
                $deductions,
                $tax,
                $net,
                $data['status'] ?? 'submitted',
                trim($data['description'] ?? ''),
                trim($data['notes'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم إنشاء وتسجيل المستخلص/الفاتورة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/projects/invoices/create');
        }

        return new RedirectResponse('/ERP/projects/invoices');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $invoice = null;
        $projects = [];
        $customers = [];
        $autoCode = '';

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM project_invoices WHERE id = ?");
            $stmt->execute([$id]);
            $invoice = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$invoice) throw new Exception("بيانات المستخلص/الفاتورة غير موجودة.");

            $projects = $this->db->query("SELECT id, code, name_ar FROM projects ORDER BY id DESC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $customers = $this->db->query("SELECT id, name_ar FROM customers ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            $autoCode = $invoice->invoice_number;

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/projects/invoices');
        }

        return $this->renderView('/resources/views/projects/invoices/create.php', [
            'invoice' => $invoice,
            'projects' => $projects,
            'customers' => $customers,
            'autoCode' => $autoCode
        ], $response);
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $total = !empty($data['total_amount']) ? (float)$data['total_amount'] : 0.00;
            $deductions = !empty($data['deductions_amount']) ? (float)$data['deductions_amount'] : 0.00;
            $tax = !empty($data['tax_amount']) ? (float)$data['tax_amount'] : 0.00;
            $net = max(0, ($total - $deductions) + $tax);
            $paid = !empty($data['paid_amount']) ? (float)$data['paid_amount'] : 0.00;

            $stmt = $this->db->prepare("
                UPDATE project_invoices 
                SET project_id = ?, customer_id = ?, invoice_date = ?, due_date = ?, period_start = ?, period_end = ?, invoice_type = ?, total_amount = ?, deductions_amount = ?, tax_amount = ?, net_amount = ?, paid_amount = ?, status = ?, description = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$data['project_id'],
                !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                $data['invoice_date'],
                !empty($data['due_date']) ? $data['due_date'] : null,
                !empty($data['period_start']) ? $data['period_start'] : null,
                !empty($data['period_end']) ? $data['period_end'] : null,
                $data['invoice_type'] ?? 'progress_claim',
                $total,
                $deductions,
                $tax,
                $net,
                $paid,
                $data['status'] ?? 'draft',
                trim($data['description'] ?? ''),
                trim($data['notes'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث بيانات المستخلص بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/projects/invoices/{$id}/edit");
        }

        return new RedirectResponse('/ERP/projects/invoices');
    }

    public function updateStatus(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $newStatus = $data['status'] ?? 'draft';
            $paidAmt = isset($data['paid_amount']) ? (float)$data['paid_amount'] : null;

            if ($paidAmt !== null) {
                $stmt = $this->db->prepare("UPDATE project_invoices SET status = ?, paid_amount = ? WHERE id = ?");
                $stmt->execute([$newStatus, $paidAmt, $id]);
            } else {
                $stmt = $this->db->prepare("UPDATE project_invoices SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
            }

            $_SESSION['flash_msg'] = "تم تحديث حالة المستخلص بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse("/ERP/projects/invoices/{$id}");
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM project_invoices WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف المستخلص/الفاتورة بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/projects/invoices');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $invoice = null;

        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT i.*, p.name_ar as project_name, p.code as project_code, p.contract_value, c.name_ar as customer_name
                FROM project_invoices i
                LEFT JOIN projects p ON i.project_id = p.id
                LEFT JOIN customers c ON i.customer_id = c.id
                WHERE i.id = ?
            ");
            $stmt->execute([$id]);
            $invoice = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$invoice) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "سجل المستخلص غير موجود.";
            return new RedirectResponse('/ERP/projects/invoices');
        }

        return $this->renderView('/resources/views/projects/invoices/show.php', [
            'invoice' => $invoice
        ], $response);
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("<div style='padding:30px; background:#fff; color:#dc2626; font-family:monospace; direction:ltr;'><h3>View File Missing:</h3>" . htmlspecialchars($fullPath) . "</div>");
        }

        try {
            ob_start();
            include $fullPath;
            $content = ob_get_clean();

            ob_start();
            include $this->basePath . '/resources/views/layouts/app.php';
            return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html; charset=UTF-8');
        } catch (Throwable $e) {
            ob_end_clean();
            die("<div style='padding:30px; background:#fff; color:#dc2626; font-family:monospace; direction:ltr;'>
                    <h3>Project Invoices View Error:</h3>
                    <p><b>Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>
                    <p><b>File:</b> " . htmlspecialchars($e->getFile()) . "</p>
                    <p><b>Line:</b> " . $e->getLine() . "</p>
                 </div>");
        }
    }
}