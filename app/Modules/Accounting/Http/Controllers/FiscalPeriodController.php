<?php
// Path: app/Modules/Accounting/Http/Controllers/FiscalPeriodController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;
use Exception;
use Throwable;

class FiscalPeriodController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        ini_set('display_errors', 0);
        error_reporting(E_ALL);

        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    private function resolveId($id = null): ?int
    {
        if (!empty($id) && is_numeric($id)) return (int)$id;
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/fiscal-periods/(\d+)#', $uri, $matches)) return (int)$matches[1];
        return null;
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fiscal_periods_view');

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('#/fiscal-periods/(\d+)/edit#', $uri, $m)) return $this->edit($request, $response, (int)$m[1]);
        if (preg_match('#/fiscal-periods/(\d+)/update#', $uri, $m)) return $this->update($request, $response, (int)$m[1]);
        if (preg_match('#/fiscal-periods/(\d+)/close-year#', $uri, $m)) return $this->closeYear($request, $response, (int)$m[1]);
        if (preg_match('#/fiscal-periods/(\d+)/close#', $uri, $m)) return $this->closePeriod($request, $response, (int)$m[1]);
        if (preg_match('#/fiscal-periods/(\d+)/delete#', $uri, $m)) return $this->delete($request, $response, (int)$m[1]);
        if (preg_match('#/fiscal-periods/sub-period/(\d+)/toggle#', $uri, $m)) return $this->toggleSubPeriod($request, $response, (int)$m[1]);
        if (preg_match('#/fiscal-periods/(\d+)$#', $uri, $m)) return $this->show($request, $response, (int)$m[1]);

        $search = trim($_GET['search'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $companyId = current_company() ?? 1;

        try {
            $where = ["company_id = ?"];
            $params = [$companyId];

            if ($search !== '') {
                $where[] = "(period_name LIKE ? OR notes LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like]);
            }
            if ($statusFilter !== '') {
                $where[] = "status = ?";
                $params[] = $statusFilter;
            }

            $whereSql = "WHERE " . implode(" AND ", $where);

            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM fiscal_periods $whereSql");
            $countStmt->execute($params);
            $totalPages = max(1, ceil($countStmt->fetchColumn() / $limit));

            $stmt = $this->db->prepare("SELECT * FROM fiscal_periods $whereSql ORDER BY start_date DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $periods = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            $statsStmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_periods,
                    SUM(IF(status='open', 1, 0)) as open_periods,
                    SUM(IF(status='closed', 1, 0)) as closed_periods
                FROM fiscal_periods WHERE company_id = ?
            ");
            $statsStmt->execute([$companyId]);
            $stats = $statsStmt->fetch(PDO::FETCH_OBJ);

        } catch (Throwable $e) {
            $periods = [];
            $stats = (object)['total_periods'=>0, 'open_periods'=>0, 'closed_periods'=>0];
            $totalPages = 1;
        }

        $currentPage = $page;
        ob_start(); include $this->basePath . '/resources/views/accounting/fiscal_periods/index.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fiscal_periods_create');

        $period = null;
        ob_start(); include $this->basePath . '/resources/views/accounting/fiscal_periods/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fiscal_periods_create');

        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            if (empty($data['period_name']) || empty($data['start_date']) || empty($data['end_date'])) {
                throw new Exception(__('يرجى تعبئة كافة الحقول المطلوبة.', 'Please fill all required fields.'));
            }

            $startDate = date('Y-m-d', strtotime($data['start_date']));
            $endDate = date('Y-m-d', strtotime($data['end_date']));

            if ($endDate <= $startDate) {
                throw new Exception(__('تاريخ نهاية الفترة يجب أن يكون بعد تاريخ البداية.', 'End date must be after start date.'));
            }

            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) FROM fiscal_periods 
                WHERE company_id = ? AND NOT (end_date < ? OR start_date > ?)
            ");
            $checkStmt->execute([$companyId, $startDate, $endDate]);

            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception(__('توجد فترة مالية أخرى مسجلة تتداخل أيامها مع هذا النطاق الزمني.', 'Overlapping fiscal period exists.'));
            }

            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO fiscal_periods (company_id, period_name, start_date, end_date, notes, status) VALUES (?, ?, ?, ?, ?, 'open')");
            $stmt->execute([$companyId, trim($data['period_name']), $startDate, $endDate, trim($data['notes'] ?? '')]);
            $periodId = $this->db->lastInsertId();

            if (!empty($data['auto_generate_months'])) {
                $subStmt = $this->db->prepare("
                    INSERT INTO fiscal_sub_periods (fiscal_period_id, period_number, name_ar, start_date, end_date, status)
                    VALUES (?, ?, ?, ?, ?, 'open')
                ");

                $start = new \DateTime($startDate);
                $arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

                for ($i = 1; $i <= 12; $i++) {
                    $mStart = clone $start;
                    $mEnd = clone $start;
                    $mEnd->modify('last day of this month');

                    $monthName = "شهر " . $arabicMonths[$i - 1] . " (" . $mStart->format('Y-m') . ")";
                    $subStmt->execute([$periodId, $i, $monthName, $mStart->format('Y-m-d'), $mEnd->format('Y-m-d')]);

                    $start->modify('+1 month');
                }
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = __('تم إنشاء السنة المالية بنجاح وتوليد الشهور الفرعية.', 'Fiscal period created successfully.');
            return new RedirectResponse("/ERP/accounting/fiscal-periods/{$periodId}");
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/fiscal-periods/create');
        }
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fiscal_periods_view');

        $id = $this->resolveId($id);
        $companyId = current_company() ?? 1;

        $stmt = $this->db->prepare("SELECT * FROM fiscal_periods WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, $companyId]);
        $period = $stmt->fetch(PDO::FETCH_OBJ);

        if (!$period) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['flash_err'] = __('الفترة المالية غير موجودة.', 'Fiscal period not found.');
            return new RedirectResponse('/ERP/accounting/fiscal-periods');
        }

        $subPeriods = $this->db->query("SELECT * FROM fiscal_sub_periods WHERE fiscal_period_id = {$id} ORDER BY period_number ASC")->fetchAll(PDO::FETCH_OBJ);

        $drafts = $this->db->prepare("SELECT COUNT(*) FROM journal_entries WHERE status = 'draft' AND company_id = ? AND entry_date BETWEEN ? AND ?");
        $drafts->execute([$companyId, $period->start_date, $period->end_date]);
        $draftCount = (int)$drafts->fetchColumn();

        $posted = $this->db->prepare("SELECT COUNT(*) FROM journal_entries WHERE status = 'posted' AND company_id = ? AND entry_date BETWEEN ? AND ?");
        $posted->execute([$companyId, $period->start_date, $period->end_date]);
        $postedCount = (int)$posted->fetchColumn();

        $pnlStmt = $this->db->prepare("
            SELECT 
                a.type,
                SUM(ji.debit) as total_debit,
                SUM(ji.credit) as total_credit
            FROM journal_entry_items ji
            JOIN journal_entries je ON ji.journal_entry_id = je.id
            JOIN accounts a ON ji.account_id = a.id
            WHERE je.status = 'posted' AND je.company_id = ? AND je.entry_date BETWEEN ? AND ? AND a.type IN ('revenue', 'expense')
            GROUP BY a.type
        ");
        $pnlStmt->execute([$companyId, $period->start_date, $period->end_date]);
        $pnlData = $pnlStmt->fetchAll(PDO::FETCH_OBJ);

        $totalRevenue = 0; $totalExpense = 0;
        foreach ($pnlData as $r) {
            if ($r->type === 'revenue') $totalRevenue += ($r->total_credit - $r->total_debit);
            if ($r->type === 'expense') $totalExpense += ($r->total_debit - $r->total_credit);
        }
        $netProfit = $totalRevenue - $totalExpense;

        ob_start(); include $this->basePath . '/resources/views/accounting/fiscal_periods/show.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function toggleSubPeriod(Request $request, Response $response, $subId = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fiscal_periods_process');

        if (session_status() === PHP_SESSION_NONE) session_start();

        try {
            $stmt = $this->db->prepare("SELECT * FROM fiscal_sub_periods WHERE id = ?");
            $stmt->execute([$subId]);
            $sub = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$sub) throw new Exception(__('الشهر المالي غير موجود.', 'Sub-period not found.'));

            $newStatus = ($sub->status === 'open') ? 'soft_lock' : 'open';
            $this->db->prepare("UPDATE fiscal_sub_periods SET status = ? WHERE id = ?")->execute([$newStatus, $subId]);

            $_SESSION['flash_msg'] = ($newStatus === 'soft_lock') ? __('تم القفل المؤقت للشهر المالي بنجاح.', 'Sub-period temporarily locked.') : __('تم إعادة فتح الشهر المالي للقيود.', 'Sub-period reopened.');
            return new RedirectResponse("/ERP/accounting/fiscal-periods/{$sub->fiscal_period_id}");
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/fiscal-periods');
        }
    }

    public function closePeriod(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fiscal_periods_process');
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $period = $this->db->query("SELECT * FROM fiscal_periods WHERE id = $id AND company_id = $companyId")->fetch(PDO::FETCH_OBJ);
            if (!$period || $period->status === 'closed') throw new Exception(__('الفترة مغلقة بالفعل.', 'Period already closed.'));

            $drafts = $this->db->prepare("SELECT COUNT(*) FROM journal_entries WHERE status = 'draft' AND company_id = ? AND entry_date BETWEEN ? AND ?");
            $drafts->execute([$companyId, $period->start_date, $period->end_date]);
            if ($drafts->fetchColumn() > 0) {
                throw new Exception(__('لا يمكن إغلاق الفترة لوجود مسودات قيود لم تُرحّل.', 'Cannot close due to unposted draft entries.'));
            }

            $this->db->prepare("UPDATE fiscal_periods SET status = 'closed', closed_at = NOW() WHERE id = ?")->execute([$id]);
            $_SESSION['flash_msg'] = __('تم إغلاق الفترة المالية بنجاح ولن يُسمح بإضافة حركات جديدة.', 'Period closed successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/accounting/fiscal-periods');
    }

    public function closeYear(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fiscal_periods_process');
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $period = $this->db->query("SELECT * FROM fiscal_periods WHERE id = $id AND company_id = $companyId")->fetch(PDO::FETCH_OBJ);
            if (!$period || $period->status === 'closed') throw new Exception(__('السنة المالية مغلقة بالفعل.', 'Year already closed.'));

            $drafts = $this->db->prepare("SELECT COUNT(*) FROM journal_entries WHERE status = 'draft' AND company_id = ? AND entry_date BETWEEN ? AND ?");
            $drafts->execute([$companyId, $period->start_date, $period->end_date]);
            if ($drafts->fetchColumn() > 0) {
                throw new Exception(__('لا يمكن الإغلاق النهائي! توجد مسودات قيود غير مرحلة.', 'Cannot close year with unposted drafts.'));
            }

            $this->db->beginTransaction();

            $accStmt = $this->db->prepare("
                SELECT a.id, a.code, a.name_ar, a.type,
                       SUM(ji.debit) as t_debit, SUM(ji.credit) as t_credit
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                JOIN accounts a ON ji.account_id = a.id
                WHERE je.status = 'posted' AND je.company_id = ? AND je.entry_date BETWEEN ? AND ? AND a.type IN ('revenue', 'expense')
                GROUP BY a.id, a.code, a.name_ar, a.type
            ");
            $accStmt->execute([$companyId, $period->start_date, $period->end_date]);
            $nominalAccounts = $accStmt->fetchAll(PDO::FETCH_OBJ);

            $retainedAcc = $this->db->query("SELECT id FROM accounts WHERE company_id = {$companyId} AND (code LIKE '3%' OR type = 'equity') AND (name_ar LIKE '%أرباح%' OR name_ar LIKE '%مرحلة%') LIMIT 1")->fetch(PDO::FETCH_OBJ);
            if (!$retainedAcc) {
                $retainedAcc = $this->db->query("SELECT id FROM accounts WHERE company_id = {$companyId} AND type = 'equity' LIMIT 1")->fetch(PDO::FETCH_OBJ);
            }
            if (!$retainedAcc) throw new Exception(__('تعذر العثور على حساب حقوق ملكية بدليل الحسابات للإقفال.', 'Retained earnings equity account not found.'));

            $entryNum = 'CLS-' . date('Y', strtotime($period->end_date));
            $desc = "قيد الإغلاق السنوي وتصفير قائمة الدخل لسنة " . $period->period_name;

            $jeStmt = $this->db->prepare("INSERT INTO journal_entries (company_id, entry_number, entry_date, description, total_amount, status) VALUES (?, ?, ?, ?, 0, 'posted')");
            $jeStmt->execute([$companyId, $entryNum, $period->end_date, $desc]);
            $closingJeId = $this->db->lastInsertId();

            $itemStmt = $this->db->prepare("INSERT INTO journal_entry_items (journal_entry_id, account_id, description, debit, credit) VALUES (?, ?, ?, ?, ?)");
            $totalClosingAmount = 0;

            foreach ($nominalAccounts as $acc) {
                if ($acc->type === 'revenue') {
                    $bal = $acc->t_credit - $acc->t_debit;
                    if ($bal > 0) {
                        $itemStmt->execute([$closingJeId, $acc->id, "إقفال إيراد", $bal, 0]);
                        $totalClosingAmount += $bal;
                    }
                } elseif ($acc->type === 'expense') {
                    $bal = $acc->t_debit - $acc->t_credit;
                    if ($bal > 0) {
                        $itemStmt->execute([$closingJeId, $acc->id, "إقفال مصروف", 0, $bal]);
                    }
                }
            }

            $netStmt = $this->db->prepare("
                SELECT 
                    SUM(CASE WHEN a.type='revenue' THEN (ji.credit - ji.debit) ELSE 0 END) -
                    SUM(CASE WHEN a.type='expense' THEN (ji.debit - ji.credit) ELSE 0 END) as net
                FROM journal_entry_items ji
                JOIN journal_entries je ON ji.journal_entry_id = je.id
                JOIN accounts a ON ji.account_id = a.id
                WHERE je.status = 'posted' AND je.company_id = ? AND je.entry_date BETWEEN ? AND ?
            ");
            $netStmt->execute([$companyId, $period->start_date, $period->end_date]);
            $finalNet = (float)$netStmt->fetchColumn();

            if ($finalNet > 0) {
                $itemStmt->execute([$closingJeId, $retainedAcc->id, "صافي أرباح العام", 0, $finalNet]);
            } else if ($finalNet < 0) {
                $itemStmt->execute([$closingJeId, $retainedAcc->id, "صافي خسائر العام", abs($finalNet), 0]);
            }

            $this->db->prepare("UPDATE journal_entries SET total_amount = ? WHERE id = ?")->execute([$totalClosingAmount, $closingJeId]);
            $this->db->prepare("UPDATE fiscal_sub_periods SET status = 'closed', closed_at = NOW() WHERE fiscal_period_id = ?")->execute([$id]);
            $this->db->prepare("UPDATE fiscal_periods SET status = 'closed', closed_at = NOW(), closing_journal_id = ? WHERE id = ?")->execute([$closingJeId, $id]);

            $this->db->commit();
            $_SESSION['flash_msg'] = __('تم إغلاق السنة المالية نهائياً وتوليد قيد الإقفال السنوي برقم ', 'Year closed with entry: ') . $entryNum;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse("/ERP/accounting/fiscal-periods/{$id}");
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fiscal_periods_edit');
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $stmt = $this->db->prepare("SELECT * FROM fiscal_periods WHERE id = ? AND company_id = ?");
            $stmt->execute([$id, $companyId]);
            $period = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$period) throw new Exception(__('الفترة المالية غير موجودة.', 'Period not found.'));
            if ($period->status === 'closed') throw new Exception(__('لا يمكن تعديل فترة مالية مغلقة.', 'Cannot edit closed period.'));

        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/fiscal-periods');
        }

        ob_start(); include $this->basePath . '/resources/views/accounting/fiscal_periods/create.php';
        $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fiscal_periods_edit');
        $id = $this->resolveId($id);
        $data = $_POST;
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $period = $this->db->query("SELECT status FROM fiscal_periods WHERE id = $id AND company_id = $companyId")->fetch(PDO::FETCH_OBJ);
            if (!$period || $period->status === 'closed') throw new Exception(__('لا يمكن تعديل فترة مالية مغلقة.', 'Cannot edit closed period.'));

            $startDate = date('Y-m-d', strtotime($data['start_date']));
            $endDate = date('Y-m-d', strtotime($data['end_date']));

            if ($endDate <= $startDate) throw new Exception(__('تاريخ النهاية يجب أن يكون بعد تاريخ البداية.', 'End date must be after start date.'));

            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) FROM fiscal_periods 
                WHERE company_id = ? AND NOT (end_date < ? OR start_date > ?) AND id != ?
            ");
            $checkStmt->execute([$companyId, $startDate, $endDate, $id]);

            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception(__('توجد فترة مالية أخرى مسجلة تتداخل أيامها مع هذا النطاق الزمني.', 'Overlapping period exists.'));
            }

            $stmt = $this->db->prepare("UPDATE fiscal_periods SET period_name=?, start_date=?, end_date=?, notes=? WHERE id=?");
            $stmt->execute([trim($data['period_name']), $startDate, $endDate, trim($data['notes'] ?? ''), $id]);

            $_SESSION['flash_msg'] = __('تم تحديث بيانات الفترة المالية بنجاح.', 'Period updated successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/accounting/fiscal-periods/{$id}/edit");
        }
        return new RedirectResponse('/ERP/accounting/fiscal-periods');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_fiscal_periods_delete');
        $id = $this->resolveId($id);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;

        try {
            $period = $this->db->query("SELECT status FROM fiscal_periods WHERE id = $id AND company_id = $companyId")->fetch(PDO::FETCH_OBJ);
            if ($period && $period->status === 'closed') throw new Exception(__('لا يمكن حذف فترة مالية مغلقة.', 'Cannot delete closed period.'));

            $this->db->prepare("DELETE FROM fiscal_periods WHERE id = ? AND company_id = ?")->execute([$id, $companyId]);
            $_SESSION['flash_msg'] = __('تم حذف الفترة المالية والشهور التابعة لها بنجاح.', 'Period deleted successfully.');
        } catch (Throwable $e) {
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/accounting/fiscal-periods');
    }
}