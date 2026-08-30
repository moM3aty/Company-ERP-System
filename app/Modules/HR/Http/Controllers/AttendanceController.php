<?php
// Path: app/Modules/HR/Http/Controllers/AttendanceController.php

namespace App\Modules\HR\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;
use Throwable;

class AttendanceController extends Controller
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
        if (preg_match('#/hr/attendance/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

  public function index(Request $request, Response $response): Response
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/hr/attendance/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/hr/attendance/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $dateFilter = trim($_GET['date'] ?? date('Y-m-d'));
        $statusFilter = trim($_GET['status'] ?? '');

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $attendances = [];
        $stats = (object)[
            'present_today' => 0,
            'absent_today' => 0,
            'late_today' => 0,
            'on_leave_today' => 0
        ];
        $totalPages = 1;

        if ($this->db) {
            try {
                $where = ["1=1"];

                if ($search !== '') {
                    $where[] = "(e.emp_code LIKE :s1 OR e.name_ar LIKE :s2)";
                }

                if ($dateFilter !== '') {
                    $where[] = "a.date = :date_val";
                }

                if ($statusFilter !== '') {
                    $where[] = "a.status = :status_val";
                }

                $whereSql = "WHERE " . implode(" AND ", $where);

                $statsDate = $dateFilter !== '' ? $dateFilter : date('Y-m-d');
                $statsData = $this->db->query("
                    SELECT 
                        SUM(IF(status = 'present', 1, 0)) as present_today,
                        SUM(IF(status = 'absent', 1, 0)) as absent_today,
                        SUM(IF(status = 'late', 1, 0)) as late_today,
                        SUM(IF(status = 'on_leave', 1, 0)) as on_leave_today
                    FROM hr_attendance
                    WHERE date = '{$statsDate}'
                ")->fetch(PDO::FETCH_OBJ);
                if ($statsData) $stats = $statsData;

                $countStmt = $this->db->prepare("
                    SELECT COUNT(*) FROM hr_attendance a
                    LEFT JOIN hr_employees e ON a.employee_id = e.id
                    $whereSql
                ");
                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $countStmt->bindValue(':s1', $searchVal);
                    $countStmt->bindValue(':s2', $searchVal);
                }
                if ($dateFilter !== '') $countStmt->bindValue(':date_val', $dateFilter);
                if ($statusFilter !== '') $countStmt->bindValue(':status_val', $statusFilter);
                
                $countStmt->execute();
                $totalCount = (int)$countStmt->fetchColumn();
                $totalPages = max(1, ceil($totalCount / $limit));

                $stmt = $this->db->prepare("
                    SELECT a.*, e.name_ar as employee_name, e.emp_code
                    FROM hr_attendance a
                    LEFT JOIN hr_employees e ON a.employee_id = e.id
                    $whereSql
                    ORDER BY a.date DESC, a.check_in DESC 
                    LIMIT :limit OFFSET :offset
                ");

                if ($search !== '') {
                    $searchVal = "%{$search}%";
                    $stmt->bindValue(':s1', $searchVal);
                    $stmt->bindValue(':s2', $searchVal);
                }
                if ($dateFilter !== '') $stmt->bindValue(':date_val', $dateFilter);
                if ($statusFilter !== '') $stmt->bindValue(':status_val', $statusFilter);
                $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

                $stmt->execute();
                $attendances = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            } catch (Throwable $e) {
                error_log("HR Attendance Index Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/attendance/index.php', [
            'attendances' => $attendances,
            'stats' => $stats,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'dateFilter' => $dateFilter,
            'statusFilter' => $statusFilter
        ], $response);
    }

   public function log(Request $request, Response $response): Response
    {
        $targetDate = trim($_GET['date'] ?? date('Y-m-d'));
        $employees = [];
        $attendanceMap = [];

        if ($this->db) {
            try {
                // جلب الموظفين النشطين
                $employees = $this->db->query("SELECT id, emp_code, name_ar FROM hr_employees WHERE status = 'active' ORDER BY name_ar ASC")->fetchAll(PDO::FETCH_OBJ) ?: [];
                
                // جلب سجلات الحضور الموجودة مسبقاً لهذا اليوم (لتعديلها إن لزم الأمر)
                $existingAtt = $this->db->prepare("SELECT * FROM hr_attendance WHERE date = ?");
                $existingAtt->execute([$targetDate]);
                $records = $existingAtt->fetchAll(PDO::FETCH_OBJ) ?: [];
                
                foreach ($records as $rec) {
                    $attendanceMap[$rec->employee_id] = $rec;
                }
            } catch (Throwable $e) {
                error_log("Attendance Log Error: " . $e->getMessage());
            }
        }

        return $this->renderView('/resources/views/hr/attendance/log.php', [
            'employees' => $employees,
            'attendanceMap' => $attendanceMap,
            'targetDate' => $targetDate
        ], $response);
    }

    // أضف دالة الحفظ الجماعي الجديدة:
    public function storeBulk(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            $targetDate = $data['date'] ?? date('Y-m-d');
            $attendances = $data['attendance'] ?? []; // مصفوفة بيانات الموظفين
            $userId = $_SESSION['user_id'] ?? 1;

            $this->db->beginTransaction();

            // تجهيز استعلام الإدخال واستعلام التحديث لتسريع العملية
            $insertStmt = $this->db->prepare("
                INSERT INTO hr_attendance (employee_id, date, check_in, check_out, status, work_hours, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $updateStmt = $this->db->prepare("
                UPDATE hr_attendance 
                SET check_in = ?, check_out = ?, status = ?, work_hours = ?, notes = ?
                WHERE id = ?
            ");

            foreach ($attendances as $empId => $att) {
                // حساب الساعات
                $workHours = 0.00;
                $checkIn = !empty($att['check_in']) ? $att['check_in'] : null;
                $checkOut = !empty($att['check_out']) ? $att['check_out'] : null;

                if ($checkIn && $checkOut) {
                    $inTime = strtotime($checkIn);
                    $outTime = strtotime($checkOut);
                    $workHours = round(max(0, ($outTime - $inTime) / 3600), 2);
                }

                $status = $att['status'] ?? 'present';
                $notes = trim($att['notes'] ?? '');

                if (!empty($att['record_id'])) {
                    // تحديث سجل موجود
                    $updateStmt->execute([$checkIn, $checkOut, $status, $workHours, $notes, (int)$att['record_id']]);
                } else {
                    // إذا لم يكن السجل موجوداً وتم اختيار حالة غير "غائب" افتراضياً، أو تم إدخال بيانات، نحفظه
                    // سنقوم بحفظ السجل في كل الأحوال لتوثيق الحالة (حاضر/غائب)
                    $insertStmt->execute([(int)$empId, $targetDate, $checkIn, $checkOut, $status, $workHours, $notes, $userId]);
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = "تم حفظ السجل الجماعي للحضور والانصراف بنجاح ليوم {$targetDate}.";

        } catch (Throwable $e) {
            if ($this->db && $this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/attendance/log');
        }

        return new RedirectResponse('/ERP/hr/attendance');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if (!$this->db) throw new Exception("اتصال قاعدة البيانات غير متوفر.");

            if (empty($data['employee_id']) || empty($data['date']) || empty($data['status'])) {
                throw new Exception("يرجى تعبئة الحقول الأساسية (الموظف، التاريخ، الحالة).");
            }

            // حساب عدد ساعات العمل إذا تم توفير الدخول والخروج
            $workHours = 0.00;
            $checkIn = !empty($data['check_in']) ? $data['check_in'] : null;
            $checkOut = !empty($data['check_out']) ? $data['check_out'] : null;

            if ($checkIn && $checkOut) {
                $inTime = strtotime($checkIn);
                $outTime = strtotime($checkOut);
                $diffHours = ($outTime - $inTime) / 3600;
                $workHours = round(max(0, $diffHours), 2);
            }

            // التأكد من عدم وجود سجل مسبق لنفس الموظف في نفس اليوم (لتجنب التكرار)
            $checkStmt = $this->db->prepare("SELECT id FROM hr_attendance WHERE employee_id = ? AND date = ?");
            $checkStmt->execute([(int)$data['employee_id'], $data['date']]);
            $existingId = $checkStmt->fetchColumn();

            if ($existingId) {
                // تحديث السجل الحالي (تسجيل خروج مثلاً)
                $stmt = $this->db->prepare("
                    UPDATE hr_attendance 
                    SET check_in = ?, check_out = ?, status = ?, work_hours = ?, notes = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $checkIn, $checkOut, $data['status'], $workHours, trim($data['notes'] ?? ''), $existingId
                ]);
                $_SESSION['flash_msg'] = "تم تحديث سجل حضور الموظف لهذا اليوم بنجاح.";
            } else {
                // إضافة سجل جديد
                $stmt = $this->db->prepare("
                    INSERT INTO hr_attendance 
                    (employee_id, date, check_in, check_out, status, work_hours, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    (int)$data['employee_id'],
                    $data['date'],
                    $checkIn,
                    $checkOut,
                    $data['status'],
                    $workHours,
                    trim($data['notes'] ?? ''),
                    $_SESSION['user_id'] ?? 1
                ]);
                $_SESSION['flash_msg'] = "تم تسجيل بصمة الحضور/الانصراف بنجاح.";
            }

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/hr/attendance/log');
        }

        return new RedirectResponse('/ERP/hr/attendance');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            if ($this->db) {
                $this->db->prepare("DELETE FROM hr_attendance WHERE id = ?")->execute([$id]);
                $_SESSION['flash_msg'] = "تم حذف سجل الحضور بنجاح.";
            }
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }

        return new RedirectResponse('/ERP/hr/attendance');
    }
// أضف هذه الدالة داخل AttendanceController
public function show(Request $request, Response $response, $id = null): Response
{
    $id = $this->resolveId($id);
    $attendance = null;

    if ($this->db) {
        $stmt = $this->db->prepare("
            SELECT a.*, e.name_ar as employee_name, e.emp_code
            FROM hr_attendance a
            LEFT JOIN hr_employees e ON a.employee_id = e.id
            WHERE a.id = ?
        ");
        $stmt->execute([$id]);
        $attendance = $stmt->fetch(PDO::FETCH_OBJ);
    }

    if (!$attendance) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash_err'] = "سجل الحضور غير موجود.";
        return new RedirectResponse('/ERP/hr/attendance');
    }

    return $this->renderView('/resources/views/hr/attendance/show.php', [
        'attendance' => $attendance
    ], $response);
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