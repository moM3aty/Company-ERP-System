<?php
// Path: app/Modules/Accounting/Http/Controllers/JournalController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use Core\Security\Auth;
use PDO;

class JournalController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct() 
    { 
        global $basePath, $app; 
        $this->basePath = $basePath ?? dirname(__DIR__, 4); 
        $this->db = $app->get(PDO::class);
    }

    public function index(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_journals_view');

        $pageTitle = __('قيود اليومية', 'Journal Entries');
        $companyId = current_company() ?? 1;
        
        try {
            $stmt = $this->db->prepare("
                SELECT je.id, je.reference as entry_no, je.entry_date as date, je.description, je.status,
                       (SELECT SUM(debit) FROM journal_entry_lines WHERE journal_entry_id = je.id) as total_debit,
                       (SELECT SUM(credit) FROM journal_entry_lines WHERE journal_entry_id = je.id) as total_credit,
                       'System Admin' as created_by
                FROM journal_entries je
                WHERE je.company_id = ?
                ORDER BY je.created_at DESC
            ");
            $stmt->execute([$companyId]);
            $journals = $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            $journals = [];
        }

        ob_start();
        $viewPath = $this->basePath . '/resources/views/accounting/journal-entries/index.php';
        if (file_exists($viewPath)) include $viewPath;
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        $html = ob_get_clean();

        return $response->setContent($html)->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_journals_create');

        $pageTitle = __('إنشاء قيد يومية جديد', 'Create Journal Entry');
        $companyId = current_company() ?? 1;

        try {
            $accStmt = $this->db->prepare("SELECT id, code, name_en, name_ar FROM accounts WHERE is_active = 1 AND is_parent = 0 AND company_id = ? ORDER BY code ASC");
            $accStmt->execute([$companyId]);
            $accounts = $accStmt->fetchAll(PDO::FETCH_ASSOC);

            $ccStmt = $this->db->prepare("SELECT id, code, name_en, name_ar FROM cost_centers WHERE is_active = 1 AND company_id = ? ORDER BY code ASC");
            $ccStmt->execute([$companyId]);
            $costCenters = $ccStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $accounts = [];
            $costCenters = [];
        }

        ob_start();
        $viewPath = $this->basePath . '/resources/views/accounting/journal-entries/create.php';
        
        if (file_exists($viewPath) && filesize($viewPath) > 0) {
            include $viewPath;
        }
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        $html = ob_get_clean();

        return $response->setContent($html)->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response 
    { 
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_journals_create');

        $data = $request->getParsedBody();
        if (session_status() === PHP_SESSION_NONE) session_start();
        $companyId = current_company() ?? 1;
        
        try {
            $this->db->beginTransaction();

            $date = $data['date'] ?? date('Y-m-d');
            
            // 1. الرقابة على الفترات المالية (Fiscal Period Engine Protection)
            $checkPeriodStmt = $this->db->prepare("SELECT status FROM fiscal_periods WHERE company_id = ? AND ? BETWEEN start_date AND end_date LIMIT 1");
            $checkPeriodStmt->execute([$companyId, $date]);
            $periodStatus = $checkPeriodStmt->fetchColumn();

            // Ignore check if fiscal_periods table is empty or missing during dev
            if ($periodStatus !== false) {
                if ($periodStatus === 'closed' || $periodStatus === 'locked') {
                    throw new \Exception(__('لا يمكن حفظ القيد: الفترة المالية للتاريخ المختار مغلقة أو مقفلة.', "Cannot save entry: The fiscal period for ($date) is closed or locked. Please contact Finance Admin."));
                }
            }

            // 2. إدخال القيد (Header)
            $ref = $data['reference'] ?? 'JE-' . time();
            $stmt = $this->db->prepare("INSERT INTO journal_entries (company_id, reference, entry_date, description, status) VALUES (?, ?, ?, ?, 'posted')");
            $stmt->execute([$companyId, $ref, $date, $data['description']]);
            $jeId = $this->db->lastInsertId();

            // 3. إدخال الأسطر وتحديث أرصدة الحسابات (Lines & Balances)
            $lineStmt = $this->db->prepare("INSERT INTO journal_entry_lines (journal_entry_id, account_id, cost_center_id, description, debit, credit) VALUES (?, ?, ?, ?, ?, ?)");
            $updateBalanceStmt = $this->db->prepare("UPDATE accounts SET current_balance = current_balance + ? - ? WHERE id = ? AND company_id = ?");

            $totalDebit = 0;
            $totalCredit = 0;

            if (!empty($data['lines'])) {
                foreach ($data['lines'] as $line) {
                    if (empty($line['account_id'])) continue;
                    
                    $dr = (float)($line['debit'] ?? 0);
                    $cr = (float)($line['credit'] ?? 0);
                    $totalDebit += $dr;
                    $totalCredit += $cr;

                    $lineStmt->execute([
                        $jeId,
                        $line['account_id'],
                        empty($line['cost_center_id']) ? null : $line['cost_center_id'],
                        $line['description'] ?? '',
                        $dr,
                        $cr
                    ]);
                    
                    $updateBalanceStmt->execute([$dr, $cr, $line['account_id'], $companyId]);
                }
            }

            if (abs($totalDebit - $totalCredit) > 0.001) {
                throw new \Exception(__('القيد غير متزن!', "Entry not balanced!") . " Dr: $totalDebit, Cr: $totalCredit");
            }

            $this->db->commit();
            $_SESSION['flash_msg'] = __('تم إنشاء قيد اليومية بنجاح.', "Journal Entry $ref created successfully.");
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/accounting/journal-entries/create');
        }

        return new RedirectResponse('/ERP/accounting/journal-entries'); 
    }

    public function show(Request $request, Response $response, $id = null): Response
    {
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_journals_view');

        $isAr = ($_SESSION['locale'] ?? 'ar') === 'ar';
        $pageTitle = $isAr ? "تفاصيل القيد #$id" : "Journal Entry Details #$id";
        
        ob_start();
        ?>
        <div dir="<?= $isAr ? 'rtl' : 'ltr' ?>" style="padding: 40px; background: var(--color-surface); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); text-align: start;">
            <h2 style="color: var(--color-primary-900); margin-bottom: 16px;">
                <i class="ph-duotone ph-book-open text-primary"></i> <?= $pageTitle ?>
            </h2>
            <p style="color: var(--color-text-muted); margin-bottom: 32px;">
                <?= __('هذه شاشة عرض تفاصيل القيد. (التصميم التفصيلي قيد التطوير).', 'Detailed view of the journal entry. (Under construction).') ?>
            </p>
            <a href="/ERP/workspace/approvals" class="erp-btn" style="background: var(--color-primary-600); color: white; border: none; padding: 10px 24px; border-radius: 4px; text-decoration: none; font-weight: bold;">
                <i class="ph-bold <?= $isAr ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i> <?= __('العودة للموافقات', 'Back to Approvals') ?>
            </a>
        </div>
        <?php
        $content = ob_get_clean();

        ob_start();
        include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }
}