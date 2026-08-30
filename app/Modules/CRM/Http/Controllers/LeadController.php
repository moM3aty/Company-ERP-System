<?php
//app/Modules/CRM/Http/Controllers/LeadController.php
namespace App\Modules\CRM\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RedirectResponse;
use PDO;
use Exception;

class LeadController extends Controller
{
    private string $basePath;
    private PDO $db;

    public function __construct()
    {
        global $basePath, $app;
        $this->basePath = $basePath ?? dirname(__DIR__, 4);
        if ($app && $app->has(PDO::class)) {
            $this->db = $app->get(PDO::class);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public function index(Request $request, Response $response): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "
                SELECT l.*, COALESCE(u.username, u.email, 'Unknown') as owner_name 
                FROM crm_leads l 
                LEFT JOIN users u ON l.owner_id = u.id 
                WHERE l.company_id = ?
            ";
            $params = [$companyId];

            if ($branchId) {
                $sql .= " AND l.branch_id = ?";
                $params[] = $branchId;
            }

            $sql .= " ORDER BY l.status ASC, l.score DESC, l.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $leads = $stmt->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (Exception $e) {
            $leads = [];
        }

        ob_start(); include $this->basePath . '/resources/views/crm/leads/index.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function create(Request $request, Response $response): Response
    {
        $lead = null; 
        try {
            $users = $this->db->query("SELECT id, COALESCE(username, email, 'Unknown') as name FROM users WHERE status = 'active'")->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (Exception $e) { $users = []; }

        ob_start(); include $this->basePath . '/resources/views/crm/leads/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $isAr = isRtl();
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $score = ($data['source'] === 'Referral') ? 50 : 10;
            $email = (isset($data['email']) && trim($data['email']) !== '') ? trim($data['email']) : null;
            $phone = (isset($data['phone']) && trim($data['phone']) !== '') ? trim($data['phone']) : null;

            if ($email) {
                $sql = "
                    SELECT 'lead' as type FROM crm_leads WHERE email = ? AND company_id = ?
                    UNION 
                    SELECT 'customer' as type FROM customers WHERE email = ? AND company_id = ?
                    LIMIT 1
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$email, $companyId, $email, $companyId]);
                $found = $stmt->fetchColumn();
                if ($found) throw new Exception($isAr ? "البريد الإلكتروني مسجل مسبقاً كـ (" . ($found == 'lead' ? 'عميل محتمل' : 'عميل فعلي') . ")" : "Email is already registered.");
            }

            if ($phone) {
                $sql = "
                    SELECT 'lead' as type FROM crm_leads WHERE phone = ? AND company_id = ?
                    UNION 
                    SELECT 'customer' as type FROM customers WHERE phone = ? AND company_id = ?
                    LIMIT 1
                ";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$phone, $companyId, $phone, $companyId]);
                $found = $stmt->fetchColumn();
                if ($found) throw new Exception($isAr ? "رقم الهاتف مسجل مسبقاً كـ (" . ($found == 'lead' ? 'عميل محتمل' : 'عميل فعلي') . ")" : "Phone is already registered.");
            }

            $stmt = $this->db->prepare("
                INSERT INTO crm_leads (company_id, branch_id, company_name, contact_person, email, phone, source, status, score, next_follow_up, owner_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $companyId, $branchId, $data['company_name'], $data['contact_person'], $email, $phone, 
                $data['source'] ?? 'Website', $data['status'] ?? 'new', $score, 
                $data['next_follow_up'] ?? date('Y-m-d', strtotime('+1 day')), empty($data['owner_id']) ? null : $data['owner_id']
            ]);

            $_SESSION['flash_msg'] = $isAr ? "تم تسجيل العميل المحتمل بنجاح!" : "Lead added successfully!";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/crm/leads/create');
        }
        
        return new RedirectResponse('/ERP/crm/leads');
    }

    public function edit(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "SELECT * FROM crm_leads WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];

            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $lead = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$lead) throw new Exception("العميل المحتمل غير موجود لهذا الفرع.");

            $users = $this->db->query("SELECT id, COALESCE(username, email, 'Unknown') as name FROM users WHERE status = 'active'")->fetchAll(PDO::FETCH_OBJ) ?: [];
        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse('/ERP/crm/leads');
        }

        ob_start(); include $this->basePath . '/resources/views/crm/leads/create.php'; $content = ob_get_clean();
        ob_start(); include $this->basePath . '/resources/views/layouts/app.php';
        return $response->setContent(ob_get_clean())->setHeader('Content-Type', 'text/html');
    }

    public function update(Request $request, Response $response, $id = null): Response
    {
        $data = $request->getParsedBody();
        $isAr = isRtl();
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $email = (isset($data['email']) && trim($data['email']) !== '') ? trim($data['email']) : null;
            $phone = (isset($data['phone']) && trim($data['phone']) !== '') ? trim($data['phone']) : null;

            if ($email) {
                $stmt = $this->db->prepare("
                    SELECT 'lead' as type FROM crm_leads WHERE email = ? AND id != ? AND company_id = ?
                    UNION 
                    SELECT 'customer' as type FROM customers WHERE email = ? AND company_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$email, $id, $companyId, $email, $companyId]);
                $found = $stmt->fetchColumn();
                if ($found) throw new Exception($isAr ? "البريد مسجل مسبقاً كـ (" . ($found == 'lead' ? 'عميل محتمل آخر' : 'عميل فعلي') . ")" : "Email registered to another.");
            }

            if ($phone) {
                $stmt = $this->db->prepare("
                    SELECT 'lead' as type FROM crm_leads WHERE phone = ? AND id != ? AND company_id = ?
                    UNION 
                    SELECT 'customer' as type FROM customers WHERE phone = ? AND company_id = ?
                    LIMIT 1
                ");
                $stmt->execute([$phone, $id, $companyId, $phone, $companyId]);
                $found = $stmt->fetchColumn();
                if ($found) throw new Exception($isAr ? "الهاتف مسجل مسبقاً كـ (" . ($found == 'lead' ? 'عميل محتمل آخر' : 'عميل فعلي') . ")" : "Phone registered to another.");
            }

            $sql = "
                UPDATE crm_leads 
                SET company_name = ?, contact_person = ?, email = ?, phone = ?, source = ?, status = ?, next_follow_up = ?, owner_id = ? 
                WHERE id = ? AND company_id = ?
            ";
            $params = [
                $data['company_name'], $data['contact_person'], $email, $phone, 
                $data['source'] ?? 'Website', $data['status'] ?? 'new', 
                $data['next_follow_up'] ?? date('Y-m-d', strtotime('+1 day')), 
                empty($data['owner_id']) ? null : $data['owner_id'], 
                $id, $companyId
            ];

            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $_SESSION['flash_msg'] = $isAr ? "تم تحديث بيانات الفرصة بنجاح!" : "Lead updated successfully!";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = $e->getMessage();
            return new RedirectResponse("/ERP/crm/leads/{$id}/edit");
        }
        return new RedirectResponse('/ERP/crm/leads');
    }

    public function delete(Request $request, Response $response, $id = null): Response
    {
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $sql = "DELETE FROM crm_leads WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $_SESSION['flash_msg'] = "تم الحذف بنجاح.";
        } catch (Exception $e) {
            $_SESSION['flash_err'] = "لا يمكن الحذف.";
        }
        return new RedirectResponse('/ERP/crm/leads');
    }

    public function convert(Request $request, Response $response, $id = null): Response
    {
        $isAr = isRtl();
        $companyId = current_company_id();
        $branchId  = current_branch();

        try {
            $this->db->beginTransaction();

            $sql = "SELECT * FROM crm_leads WHERE id = ? AND company_id = ?";
            $params = [$id, $companyId];
            if ($branchId) {
                $sql .= " AND branch_id = ?";
                $params[] = $branchId;
            }
            $sql .= " FOR UPDATE";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $lead = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$lead || $lead->status === 'converted') throw new Exception("الفرصة غير موجودة أو تم تحويلها مسبقاً.");

            $custCode = 'CUST-' . time();
            $custStmt = $this->db->prepare("
                INSERT INTO customers (company_id, branch_id, code, name_en, name_ar, email, phone, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $custStmt->execute([$companyId, $branchId, $custCode, $lead->company_name, $lead->company_name, $lead->email, $lead->phone]);

            $updStmt = $this->db->prepare("UPDATE crm_leads SET status = 'converted' WHERE id = ? AND company_id = ?");
            $updStmt->execute([$id, $companyId]);

            $this->db->commit();
            $_SESSION['flash_msg'] = $isAr ? "تم تحويل الفرصة لعميل فعلي." : "Converted to Customer successfully.";
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['flash_err'] = $e->getMessage();
        }
        return new RedirectResponse('/ERP/crm/leads');
    }
}