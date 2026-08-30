<?php
// Path: app/Modules/Accounting/Http/Controllers/ChartOfAccountsController.php

namespace App\Modules\Accounting\Http\Controllers;

use Core\Http\Controller;
use Core\Http\Request;
use Core\Http\Response;
use Core\Security\Auth;
use App\Modules\Accounting\Application\Services\AccountService;
use App\Modules\Accounting\Application\DTOs\CreateAccountDTO;
use App\Modules\Accounting\Http\Requests\CreateAccountRequest;

class ChartOfAccountsController extends Controller
{
    private AccountService $service;

    public function __construct()
    {
        // Service resolution via container
        $this->service = app(AccountService::class);
    }

    public function index(Request $request, Response $response): Response
    {
        // حماية الصلاحيات
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_accounts_view');

        // جلب الشجرة المحاسبية الخاصة بالشركة الحالية فقط (Multi-Tenant)
        $companyId = current_company() ?? 1;
        $tree = $this->service->getChartOfAccounts($companyId);
        
        return $this->json([
            'status' => 'success',
            'data' => $tree
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        // حماية الصلاحيات لعملية الإنشاء
        if (class_exists('\Core\Security\Auth')) Auth::enforce('accounting_accounts_create');

        $validatedData = CreateAccountRequest::validate($request->getParsedBody());
        
        // ربط الحساب بالشركة الحالية أوتوماتيكياً
        $validatedData['company_id'] = current_company() ?? 1;

        $dto = CreateAccountDTO::fromRequest($validatedData);
        $account = $this->service->createAccount($dto);

        return $this->json([
            'status' => 'success',
            'message' => __('تم إنشاء الحساب بنجاح.', 'Account created successfully.'),
            'data' => $account
        ], 201);
    }
}