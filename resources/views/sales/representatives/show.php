<?php
// Path: resources/views/sales/representatives/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isAr = isRtl();
$currency = current_currency();
$dir = $isAr ? 'rtl' : 'ltr';

$companyName = current_company_name();
$branchName  = $_SESSION['active_branch_name'] ?? ($isAr ? 'الفرع الرئيسي' : 'Main Branch');

$repName = $isAr ? ($representative->name_ar ?: $representative->name_en) : ($representative->name_en ?: $representative->name_ar);
if (empty($repName)) $repName = $isAr ? 'مندوب مبيعات' : 'Sales Representative';

$initial = mb_substr($repName, 0, 1, 'UTF-8');
$targetAmount     = (float)($representative->target_amount ?? 0);
$totalSales       = (float)($stats->total_sales ?? 0);
$commissionRate   = (float)($representative->commission_rate ?? 0);
$earnedCommission = (float)($stats->earned_commission ?? 0);
$targetAchieved   = ($targetAmount > 0) ? round(($totalSales / $targetAmount) * 100, 1) : 0;

$t = [
    'ar' => [
        'title' => 'تقرير أداء المندوب',
        'print_btn' => 'طباعة تقرير المندوب',
        'edit_btn' => 'تعديل البيانات',
        'total_sales' => 'إجمالي المبيعات',
        'earned_commission' => 'العمولة المستحقة',
        'monthly_target' => 'المستهدف الشهري (Target)',
        'target_achieved' => 'نسبة تحقيق التارجت',
        'contact_info' => 'معلومات التواصل',
        'phone' => 'رقم الهاتف',
        'email' => 'البريد الإلكتروني',
        'status' => 'حالة الحساب',
        'active' => 'نشط (Active)',
        'inactive' => 'موقوف (Inactive)',
        'commission_policy' => 'سياسة العمولات والأداء',
        'commission_rate' => 'نسبة العمولة الثابتة',
        'invoices_count' => 'عدد الفواتير الصادرة',
        'notes' => 'ملاحظات',
        'branch' => 'الفرع التابع له'
    ],
    'en' => [
        'title' => 'Representative Performance Report',
        'print_btn' => 'Print Report',
        'edit_btn' => 'Edit Info',
        'total_sales' => 'Total Sales',
        'earned_commission' => 'Earned Commission',
        'monthly_target' => 'Monthly Target',
        'target_achieved' => 'Target Achieved %',
        'contact_info' => 'Contact Information',
        'phone' => 'Phone',
        'email' => 'Email',
        'status' => 'Status',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'commission_policy' => 'Commission Policy & Performance',
        'commission_rate' => 'Fixed Commission Rate',
        'invoices_count' => 'Invoices Count',
        'notes' => 'Notes',
        'branch' => 'Assigned Branch'
    ]
][$isAr ? 'ar' : 'en'];
?>

<style>
    .rep-show-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .profile-header { background: #ffffff; border-radius: 20px; padding: 28px 32px; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; border: 1px solid #f1f5f9; flex-wrap: wrap; gap: 20px; }
    .profile-info { display: flex; align-items: center; gap: 20px; }
    .avatar { width: 75px; height: 75px; border-radius: 20px; background: linear-gradient(135deg, #0284c7, #0369a1); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; font-weight: 800; box-shadow: 0 8px 16px -4px rgba(2, 132, 199, 0.35); text-transform: uppercase; flex-shrink: 0; }
    .rep-name { margin: 0 0 8px 0; font-size: 1.6rem; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
    .rep-code { background: #e0f2fe; color: #0284c7; padding: 4px 14px; border-radius: 99px; font-weight: 800; font-family: monospace; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #bae6fd; }

    .actions-group { display: flex; gap: 12px; }
    .btn { padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: none; cursor: pointer; }
    .btn-light { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .btn-light:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; }
    .btn-primary { background: linear-gradient(135deg, #0284c7, #0369a1); color: white; box-shadow: 0 6px 16px rgba(2, 132, 199, 0.3); }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(2, 132, 199, 0.4); }

    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    @media(max-width: 900px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    
    .stat-card { background: #ffffff; border-radius: 16px; padding: 18px 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between; min-height: 100px; }
    .stat-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .stat-title { color: #64748b; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; line-height: 1.2; }
    .stat-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
    
    .icon-blue { background: #e0f2fe; color: #0284c7; }
    .icon-green { background: #ecfdf5; color: #10b981; }
    .icon-purple { background: #f3e8ff; color: #9333ea; }
    .icon-amber { background: #fef3c7; color: #d97706; }
    
    .stat-value { color: #0f172a; font-size: 1.35rem; font-weight: 900; font-family: monospace; line-height: 1; word-break: break-all; }

    .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    @media(max-width: 768px) { .details-grid { grid-template-columns: 1fr; } }
    .details-card { background: #ffffff; border-radius: 20px; padding: 28px; border: 1px solid #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
    .details-card h4 { margin: 0 0 20px 0; color: #0f172a; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 10px; border-bottom: 2px solid #f8fafc; padding-bottom: 14px; }

    .info-list { list-style: none; padding: 0; margin: 0; }
    .info-list li { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px dashed #e2e8f0; }
    .info-list li:last-child { border-bottom: none; padding-bottom: 0; }
    .info-label { color: #64748b; font-weight: 700; font-size: 0.88rem; display: flex; align-items: center; gap: 8px; }
    .info-value { color: #1e293b; font-weight: 800; font-size: 0.92rem; text-align: end; }

    .print-only-header { display: none; }
    .print-signatures { display: none; }

    /* =========================================
       Master A4 Print CSS (الطباعة الصارمة إخفاء العناصر والإضافات)
       ========================================= */
    @media print {
        @page { size: A4 portrait; margin: 0; }
        
        html, body { 
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #ffffff !important; 
            color: #000000 !important; 
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important; 
            font-size: 9.5pt !important;
            overflow: visible !important; 
        }
        
        .rep-show-wrapper {
            padding: 15mm !important;
            max-width: 100% !important; 
            width: 100% !important; 
            margin: 0 !important; 
            box-sizing: border-box !important;
        }

        .nt-navbar, .nt-sidebar, .nav-search-box, header, nav, footer, aside, .sidebar-overlay, .mobile-sidebar-toggle, .actions-group, .no-print {
            display: none !important;
            height: 0 !important;
            opacity: 0 !important;
            visibility: hidden !important;
        }

        .dataTables_filter, .dataTables_length, .dataTables_info, .dataTables_paginate,
        .dt-search, .dt-paging, .dt-info, .dt-length, .dt-buttons, .pagination,
        [id*="filter"], [id*="search"], [class*="paginate"], [class*="pagination"] {
            display: none !important;
            height: 0 !important;
            opacity: 0 !important;
            visibility: hidden !important;
        }

        .profile-header { border: 2px solid #0f172a !important; box-shadow: none !important; padding: 16px !important; border-radius: 8px !important; margin-bottom: 20px !important; background: #ffffff !important; }
        .avatar { width: 50px !important; height: 50px !important; font-size: 1.5rem !important; border: 1px solid #0f172a !important; border-radius: 8px !important; }
        .rep-name { font-size: 1.3rem !important; margin-bottom: 4px !important; color: #000000 !important; }
        .rep-code { border: 1px solid #0f172a !important; background: #ffffff !important; color: #000000 !important; font-size: 8.5pt !important; padding: 2px 8px !important; }

        .stats-grid { display: table !important; width: 100% !important; table-layout: fixed !important; border-collapse: collapse !important; margin-bottom: 20px !important; page-break-inside: avoid !important; }
        .stat-card { display: table-cell !important; width: 25% !important; border: 1px solid #0f172a !important; padding: 10px !important; background: #ffffff !important; box-shadow: none !important; border-radius: 0 !important; vertical-align: middle !important; min-height: auto !important; }
        .stat-header { margin-bottom: 4px !important; display: block !important; }
        .stat-title { font-size: 7.5pt !important; color: #000000 !important; font-weight: 800 !important; white-space: nowrap !important; }
        .stat-icon { display: none !important; }
        .stat-value { font-size: 11pt !important; font-weight: 900 !important; color: #000000 !important; font-family: monospace !important; }

        .details-grid { display: flex !important; justify-content: space-between !important; gap: 12px !important; page-break-inside: avoid !important; }
        .details-card { width: 48% !important; border: 1px solid #0f172a !important; border-radius: 8px !important; padding: 16px !important; box-shadow: none !important; background: #ffffff !important; }
        .details-card h4 { font-size: 9.5pt !important; margin-bottom: 12px !important; padding-bottom: 6px !important; border-bottom: 1px solid #0f172a !important; }
        .info-list li { padding: 8px 0 !important; border-bottom: 1px dashed #cbd5e1 !important; font-size: 8.5pt !important; }

        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 45px !important; padding-top: 15px !important; border-top: 2px dashed #0f172a !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-size: 8.5pt !important; font-weight: 800 !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #0f172a !important; width: 70% !important; margin: 30px auto 0 auto !important; }
    }
</style>

<div class="rep-show-wrapper" dir="<?= $dir ?>">
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-info">
            <div class="avatar"><?= htmlspecialchars($initial) ?></div>
            <div>
                <h1 class="rep-name"><?= htmlspecialchars($repName) ?></h1>
                <div class="rep-code"><i class="ph-bold ph-hash"></i> <?= htmlspecialchars($representative->code) ?></div>
            </div>
        </div>
        <div class="actions-group no-print">
            <button onclick="window.print()" class="btn btn-light"><i class="ph-bold ph-printer"></i> <?= $t['print_btn'] ?></button>
            <a href="/ERP/sales/representatives/<?= $representative->id ?>/edit" class="btn btn-primary"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit_btn'] ?></a>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title"><?= $t['total_sales'] ?></span>
                <div class="stat-icon icon-blue"><i class="ph-duotone ph-coins"></i></div>
            </div>
            <div class="stat-value"><?= number_format($totalSales, 2) ?> <span style="font-size: 0.75rem;"><?= htmlspecialchars($currency) ?></span></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title"><?= $t['earned_commission'] ?></span>
                <div class="stat-icon icon-green"><i class="ph-duotone ph-percent"></i></div>
            </div>
            <div class="stat-value" style="color:#059669;"><?= number_format($earnedCommission, 2) ?> <span style="font-size: 0.75rem;"><?= htmlspecialchars($currency) ?></span></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title"><?= $t['monthly_target'] ?></span>
                <div class="stat-icon icon-purple"><i class="ph-duotone ph-target"></i></div>
            </div>
            <div class="stat-value"><?= number_format($targetAmount, 2) ?> <span style="font-size: 0.75rem;"><?= htmlspecialchars($currency) ?></span></div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-title"><?= $t['target_achieved'] ?></span>
                <div class="stat-icon icon-amber"><i class="ph-duotone ph-trend-up"></i></div>
            </div>
            <div class="stat-value" style="color:#d97706;"><?= $targetAchieved ?>%</div>
        </div>
    </div>

    <!-- Detailed Info Cards -->
    <div class="details-grid">
        <div class="details-card">
            <h4><i class="ph-duotone ph-identification-card text-sky-500 no-print" style="font-size: 1.3rem;"></i> <?= $t['contact_info'] ?></h4>
            <ul class="info-list">
                <li>
                    <span class="info-label"><i class="ph-bold ph-buildings text-slate-400 no-print"></i> <?= $t['branch'] ?></span>
                    <span class="info-value"><?= htmlspecialchars($branchName) ?></span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-phone text-slate-400 no-print"></i> <?= $t['phone'] ?></span>
                    <span class="info-value" dir="ltr" style="color: #0284c7; font-family: monospace;"><?= htmlspecialchars($representative->phone ?? '---') ?></span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-envelope text-slate-400 no-print"></i> <?= $t['email'] ?></span>
                    <span class="info-value"><?= htmlspecialchars($representative->email ?? '---') ?></span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-toggle-left text-slate-400 no-print"></i> <?= $t['status'] ?></span>
                    <span class="info-value">
                        <?php if($representative->is_active): ?>
                            <span style="background:#ecfdf5; color:#059669; padding:3px 12px; border-radius:99px; font-size:0.75rem; font-weight:800; border:1px solid #a7f3d0;" class="no-print-bg"><?= $t['active'] ?></span>
                        <?php else: ?>
                            <span style="background:#fef2f2; color:#dc2626; padding:3px 12px; border-radius:99px; font-size:0.75rem; font-weight:800; border:1px solid #fecaca;" class="no-print-bg"><?= $t['inactive'] ?></span>
                        <?php endif; ?>
                    </span>
                </li>
            </ul>
        </div>

        <div class="details-card">
            <h4><i class="ph-duotone ph-percent text-sky-500 no-print" style="font-size: 1.3rem;"></i> <?= $t['commission_policy'] ?></h4>
            <ul class="info-list">
                <li>
                    <span class="info-label"><i class="ph-bold ph-percent text-slate-400 no-print"></i> <?= $t['commission_rate'] ?></span>
                    <span class="info-value" style="font-family: monospace; color:#059669; font-size:1.05rem;"><?= number_format($commissionRate, 2) ?> %</span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-receipt text-slate-400 no-print"></i> <?= $t['invoices_count'] ?></span>
                    <span class="info-value" style="font-family: monospace;"><?= number_format($stats->invoices_count ?? 0) ?></span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-info text-slate-400 no-print"></i> <?= $t['notes'] ?></span>
                    <span class="info-value"><?= htmlspecialchars($representative->notes ?? '---') ?></span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Official Signatures Section (Visible on Print Only) -->
    <div class="print-signatures">
        <div class="sig-box">
            <div><?= $isAr ? 'إعداد المشرف' : 'Prepared By' ?></div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-box">
            <div><?= $isAr ? 'اعتماد مدير المبيعات' : 'Sales Manager' ?></div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-box">
            <div><?= $isAr ? 'توقيع المندوب' : 'Rep Signature' ?></div>
            <div class="sig-line"></div>
        </div>
    </div>

</div>