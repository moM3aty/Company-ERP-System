<?php
// Path: app/Modules/HR/Http/Controllers/DocumentController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class DocumentController extends Controller
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
        if (preg_match('#/hr/documents/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/hr/documents/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/hr/documents/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/hr/documents/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/documents/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $typeFilter = trim($_GET['document_type'] ?? '');
        $employeeFilter = trim($_GET['employee_id'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $documents = [];
        $employees = [];
        $stats = (object)[
            'total_docs' => 0,
            'active_docs' => 0,
            'expired_docs' => 0,
            'pending_docs' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $employees = $this->db->query("SELECT id, emp_code, name_ar FROM hr_employees ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(d.document_code LIKE :s1 OR d.title_ar LIKE :s2 OR e.name_ar LIKE :s3 OR e.emp_code LIKE :s4)";
                }

                if ($statusFilter !== '') {
                    $where[] = "d.status = :status_val";
                }

                if ($typeFilter !== '') {
                    $where[] = "d.document_type = :type_val";
                }

                if ($employeeFilter !== '') {
                    $where[] = "d.employee_id = :emp_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM hr_documents d
                    LEFT JOIN hr_employees e ON d.employee_id = e.id
                    $whereSql
                ");
                
                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                    $countStmt->bindValue(':s3', $searchVal);
                    $countStmt->bindValue(':s4', $searchVal);
                }
                if ($statusFilter !== '') $countStmt->bindValue(':status_val', $statusFilter);
                if ($typeFilter !== '') $countStmt->bindValue(':type_val', $typeFilter);
                if ($employeeFilter !== '') $countStmt->bindValue(':emp_val', (int)$employeeFilter, PDO::PARAM_INT);

                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT d.*, e.name_ar as employee_name, e.emp_code, dep.name_ar as dept_name
                    FROM hr_documents d
                    LEFT JOIN hr_employees e ON d.employee_id = e.id
                    LEFT JOIN hr_departments dep ON e.department_id = dep.id
                    $whereSql
                    ORDER BY d.id DESC 
                    LIMIT :limit OFFSET :offset
                ");

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $stmt->bindValue(':s1', $searchVal);
                    $stmt->bindValue(':s2', $searchVal);
                    $stmt->bindValue(':s3', $searchVal);
                    $stmt->bindValue(':s4', $searchVal);
                }
                if ($statusFilter !== '') $stmt->bindValue(':status_val', $statusFilter);
                if ($typeFilter !== '') $stmt->bindValue(':type_val', $typeFilter);
                if ($employeeFilter !== '') $stmt->bindValue(':emp_val', (int)$employeeFilter, PDO::PARAM_INT);
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $documents = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

                $statsData = $this->db->query("
                    SELECT 
                        COUNT(*) as total_docs,
                        SUM(IF(status = 'active', 1, 0)) as active_docs,
                        SUM(IF(status = 'expired', 1, 0)) as expired_docs,
                        SUM(IF(status = 'pending_renewal', 1, 0)) as pending_docs
                    FROM hr_documents
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

            } catch (Throwable $e) {
                error_log("HR Documents Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/documents/index.php', [
            'documents' => $documents,
            'employees' => $employees,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'typeFilter' => $typeFilter,
            'employeeFilter' => $employeeFilter
        ], $response);
    }

    public function create(Request $request, Response $response): Response
    {
        $employees = [];
        $autoCode = 'DOC-' . date('Y') . '-' . str_pad((string)(($this->getDocumentCount()) + 1), 4, '0', STR_PAD_LEFT);

        if ($this->db) {
            try {
                $employees = $this->db->query("SELECT id, emp_code, name_ar FROM hr_employees WHERE status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
            } catch (Throwable $e) {}
        }

        return $this->renderView('/resources/views/hr/documents/create.php', [
            'document' => null,
            'employees' => $employees,
            'autoCode' => $autoCode
        ], $response);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['document_code']) || empty($data['employee_id']) || empty($data['title_ar']) || empty($data['document_type'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية للوثيقة.");
            }

            $empCodeFolder = 'general';
            if (!empty($data['employee_id'])) {
                $stmtEmp = $this->db->prepare("SELECT emp_code FROM hr_employees WHERE id = ?");
                $stmtEmp->execute([(int)$data['employee_id']]);
                $empCode = $stmtEmp->fetchColumn();
                if ($empCode) {
                    $empCodeFolder = preg_replace('/[^A-Za-z0-9_\-]/', '_', $empCode);
                }
            }

            $filePath = null;
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = $this->basePath . '/uploads/hr_documents/' . $empCodeFolder . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                $fileName = time() . '_' . uniqid() . '.' . $ext;
                $filePath = '/uploads/hr_documents/' . $empCodeFolder . '/' . $fileName;
                move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $fileName);
            }

            $stmt = $this->db->prepare("
                INSERT INTO hr_documents (document_code, employee_id, document_type, title_ar, file_path, issue_date, expiry_date, status, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($data['document_code']),
                (int)$data['employee_id'],
                $data['document_type'],
                trim($data['title_ar']),
                $filePath,
                !empty($data['issue_date']) ? $data['issue_date'] : null,
                !empty($data['expiry_date']) ? $data['expiry_date'] : null,
                $data['status'] ?? 'active',
                trim($data['notes'] ?? ''),
                $_SESSION['user_id'] ?? 1
            ]);

            $_SESSION['flash_msg'] = "تم رفع وأرشفة الوثيقة بنجاح داخل مجلد الموظف [{$empCodeFolder}].";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/documents/create');
        }

        return new RedirectResponse('/ERP/hr/documents');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        $document = null;
        $employees = [];

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmt = $this->db->prepare("SELECT * FROM hr_documents WHERE id = ?");
            $stmt->execute([$id]);
            $document = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$document) throw new Exception("بيانات الوثيقة غير موجودة.");

            $employees = $this->db->query("SELECT id, emp_code, name_ar FROM hr_employees ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/documents');
        }

        return $this->renderView('/resources/views/hr/documents/create.php', [
            'document' => $document,
            'employees' => $employees,
            'autoCode' => $document->document_code
        ], $response);
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $stmtCurrent = $this->db->prepare("SELECT file_path FROM hr_documents WHERE id = ?");
            $stmtCurrent->execute([$id]);
            $currentDoc = $stmtCurrent->fetch(PDO::FETCH_OBJ);
            $filePath = $currentDoc ? $currentDoc->file_path : null;

            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $empCodeFolder = 'general';
                if (!empty($data['employee_id'])) {
                    $stmtEmp = $this->db->prepare("SELECT emp_code FROM hr_employees WHERE id = ?");
                    $stmtEmp->execute([(int)$data['employee_id']]);
                    $empCode = $stmtEmp->fetchColumn();
                    if ($empCode) {
                        $empCodeFolder = preg_replace('/[^A-Za-z0-9_\-]/', '_', $empCode);
                    }
                }

                $uploadDir = $this->basePath . '/uploads/hr_documents/' . $empCodeFolder . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                $fileName = time() . '_' . uniqid() . '.' . $ext;
                $filePath = '/uploads/hr_documents/' . $empCodeFolder . '/' . $fileName;
                move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $fileName);
            }

            $stmt = $this->db->prepare("
                UPDATE hr_documents 
                SET employee_id = ?, document_type = ?, title_ar = ?, file_path = ?, issue_date = ?, expiry_date = ?, status = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                (int)$data['employee_id'],
                $data['document_type'],
                trim($data['title_ar']),
                $filePath,
                !empty($data['issue_date']) ? $data['issue_date'] : null,
                !empty($data['expiry_date']) ? $data['expiry_date'] : null,
                $data['status'] ?? 'active',
                trim($data['notes'] ?? ''),
                $id
            ]);

            $_SESSION['flash_msg'] = "تم تحديث بيانات الوثيقة بنجاح.";
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/hr/documents/{$id}/edit");
        }

        return new RedirectResponse('/ERP/hr/documents');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM hr_documents WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف الوثيقة بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/documents');
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        $document = null;

        if ($this->db) {
            $stmt = $this->db->prepare("
                SELECT d.*, e.name_ar as employee_name, e.emp_code, dep.name_ar as dept_name
                FROM hr_documents d
                LEFT JOIN hr_employees e ON d.employee_id = e.id
                LEFT JOIN hr_departments dep ON e.department_id = dep.id
                WHERE d.id = ?
            ");
            $stmt->execute([$id]);
            $document = $stmt->fetch(PDO::FETCH_OBJ);
        }

        if (!$document) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = "الوثيقة غير موجودة.";
            return new RedirectResponse('/ERP/hr/documents');
        }

        return $this->renderView('/resources/views/hr/documents/show.php', [
            'document' => $document
        ], $response);
    }

    private function getDocumentCount(): int
    {
        if (!$this->db) return 0;
        try {
            return (int)$this->db->query("SELECT COUNT(*) FROM hr_documents")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function renderView(string $viewPath, array $data, Response $response): Response
    {
        extract($data);
        $fullPath = $this->basePath . $viewPath;

        if (!file_exists($fullPath)) {
            die("<div style='padding:30px; background:#fff; color:#dc2626;'>View File Missing: " . htmlspecialchars($fullPath) . "</div>");
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
            die("<div style='padding:30px; background:#fff; color:#dc2626;'>View Render Error: " . htmlspecialchars($e->getMessage()) . "</div>");
        }
    }
}