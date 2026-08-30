<?php
// Path: resources/views/sales/customers/show.php

if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = isRtl();
$currency = current_currency();

$companyName = current_company_name();
$branchName  = $_SESSION['active_branch_name'] ?? ($isRtl ? 'الفرع الرئيسي' : 'Main Branch');

$custName = $isRtl ? ($customer->name_ar ?: $customer->name_en) : ($customer->name_en ?: $customer->name_ar);
if (empty($custName)) $custName = $isRtl ? 'عميل بدون اسم' : 'Unnamed Customer';
$initial = mb_substr($custName, 0, 1, 'UTF-8');

$t = [
    'ar' => [
        'title' => 'ملف العميل', 'edit' => 'تعديل البيانات', 'back' => 'العودة', 'print' => 'طباعة ملف العميل',
        'orders' => 'إجمالي الفواتير', 'spent' => 'إجمالي المشتريات', 'credit' => 'الحد الائتماني',
        'basic_info' => 'معلومات التواصل والعنوان', 'fin_info' => 'المعلومات المالية والحساب',
        'phone' => 'رقم الهاتف', 'email' => 'البريد الإلكتروني', 'address' => 'العنوان التفصيلي',
        'tax_no' => 'الرقم الضريبي (VAT)', 'status' => 'حالة الحساب', 'joined' => 'تاريخ التسجيل',
        'active' => 'نشط', 'inactive' => 'موقوف', 'branch' => 'الفرع التابع له',
        'sig_sales' => 'مسؤول مبيعات الحساب', 'sig_audit' => 'إدارة المراجعة المالية', 'sig_manager' => 'اعتماد مدير الفرع'
    ],
    'en' => [
        'title' => 'Customer Profile', 'edit' => 'Edit Profile', 'back' => 'Back', 'print' => 'Print Profile',
        'orders' => 'Total Invoices', 'spent' => 'Total Spent', 'credit' => 'Credit Limit',
        'basic_info' => 'Contact & Address Info', 'fin_info' => 'Financial & Account Info',
        'phone' => 'Phone Number', 'email' => 'Email Address', 'address' => 'Address',
        'tax_no' => 'Tax Number (VAT)', 'status' => 'Account Status', 'joined' => 'Joined Date',
        'active' => 'Active', 'inactive' => 'Inactive', 'branch' => 'Assigned Branch',
        'sig_sales' => 'Account Manager', 'sig_audit' => 'Financial Auditor', 'sig_manager' => 'Branch Manager'
    ]
][$isRtl ? 'ar' : 'en'];
?>

<style>
    .show-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 60px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .profile-header { background: #ffffff; border-radius: 20px; padding: 32px; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; border: 1px solid #f1f5f9; flex-wrap: wrap; gap: 24px; }
    .profile-info { display: flex; align-items: center; gap: 24px; }
    .avatar { width: 85px; height: 85px; border-radius: 24px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4); text-transform: uppercase; }
    .cust-name { margin: 0 0 10px 0; font-size: 1.8rem; font-weight: 900; color: #0f172a; letter-spacing: -0.5px; }
    .cust-code { background: #eff6ff; color: #2563eb; padding: 6px 16px; border-radius: 99px; font-weight: 800; font-family: monospace; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 6px; border: 1px solid #bfdbfe; }
    
    .actions-group { display: flex; gap: 12px; }
    .btn { padding: 12px 24px; border-radius: 12px; font-weight: 700; font-size: 0.95rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: none; cursor: pointer; }
    .btn-light { background: #ffffff; border: 1px solid #cbd5e1; color: #475569; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .btn-light:hover { background: #f8fafc; color: #0f172a; border-color: #94a3b8; }
    .btn-primary { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3); }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4); }

    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px; }
    @media(max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } }
    .stat-card { background: #ffffff; border-radius: 20px; padding: 24px; border: 1px solid #f1f5f9; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); transition: 0.3s; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.05); border-color: #e2e8f0; }
    .stat-icon { width: 64px; height: 64px; border-radius: 18px; display: flex; align-items: center; justify-content: center; font-size: 2rem; flex-shrink: 0; }
    .icon-blue { background: #eff6ff; color: #2563eb; }
    .icon-green { background: #ecfdf5; color: #10b981; }
    .icon-purple { background: #f3e8ff; color: #a855f7; }
    .stat-details h5 { margin: 0 0 6px 0; color: #64748b; font-size: 0.9rem; font-weight: 700; }
    .stat-details .value { margin: 0; color: #0f172a; font-size: 1.6rem; font-weight: 900; letter-spacing: -0.5px; }

    .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    @media(max-width: 768px) { .details-grid { grid-template-columns: 1fr; } }
    .details-card { background: #ffffff; border-radius: 20px; padding: 32px; border: 1px solid #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
    .details-card h4 { margin: 0 0 24px 0; color: #0f172a; font-size: 1.2rem; font-weight: 800; display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #f8fafc; padding-bottom: 16px; }
    
    .info-list { list-style: none; padding: 0; margin: 0; }
    .info-list li { display: flex; justify-content: space-between; align-items: flex-start; padding: 16px 0; border-bottom: 1px dashed #e2e8f0; }
    .info-list li:last-child { border-bottom: none; padding-bottom: 0; }
    .info-label { color: #64748b; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; width: 40%; flex-shrink: 0; }
    .info-value { color: #1e293b; font-weight: 800; font-size: 1rem; text-align: end; max-width: 60%; word-break: break-word; }
    
    .status-badge { padding: 6px 16px; border-radius: 999px; font-size: 0.85rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
    .status-active { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .status-inactive { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    .print-only-header { display: none; }
    .print-signatures { display: none; }

    /* =========================================
       Master A4 Print CSS (إعدادات طباعة مسطرة صارمة)
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
        
        .show-wrapper {
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

        .print-only-header { 
            display: flex !important; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 2px solid #0f172a; 
            padding-bottom: 15px; 
            margin-bottom: 20px; 
        }
        .print-only-header h1 { font-size: 20pt !important; margin:0 !important; color:#000 !important; font-weight:900 !important;}
        .print-only-header h2 { font-size: 14pt !important; margin:0 !important; color:#000 !important;}
        .print-only-header p { font-size: 10pt !important; margin:4px 0 0 0 !important; color:#000 !important;}

        .profile-header { border: 2px solid #0f172a !important; box-shadow: none !important; padding: 16px !important; border-radius: 8px !important; margin-bottom: 20px !important; background: #ffffff !important; }
        .avatar { width: 50px !important; height: 50px !important; font-size: 1.5rem !important; border: 1px solid #0f172a !important; border-radius: 8px !important; }
        .cust-name { font-size: 1.4rem !important; margin-bottom: 4px !important; color: #000000 !important; }
        .cust-code { border: 1px solid #0f172a !important; background: #ffffff !important; color: #000000 !important; font-size: 8.5pt !important; padding: 2px 8px !important; }

        .stats-grid { display: table !important; width: 100% !important; table-layout: fixed !important; border-collapse: collapse !important; margin-bottom: 20px !important; page-break-inside: avoid !important; }
        .stat-card { display: table-cell !important; width: 33.33% !important; border: 1px solid #0f172a !important; padding: 12px !important; background: #ffffff !important; box-shadow: none !important; border-radius: 0 !important; vertical-align: middle !important; min-height: auto !important; }
        .stat-icon { display: none !important; }
        .stat-details h5 { font-size: 8pt !important; color: #000000 !important; font-weight: 800 !important; }
        .stat-details .value { font-size: 12pt !important; font-weight: 900 !important; color: #000000 !important; font-family: monospace !important; }

        .details-grid { display: flex !important; justify-content: space-between !important; gap: 12px !important; page-break-inside: avoid !important; }
        .details-card { width: 48% !important; border: 1px solid #0f172a !important; border-radius: 8px !important; padding: 16px !important; box-shadow: none !important; background: #ffffff !important; }
        .details-card h4 { font-size: 10pt !important; margin-bottom: 12px !important; padding-bottom: 6px !important; border-bottom: 1px solid #0f172a !important; }
        .info-list li { padding: 8px 0 !important; border-bottom: 1px dashed #cbd5e1 !important; font-size: 8.5pt !important; }

        .print-signatures { display: flex !important; justify-content: space-between !important; margin-top: 45px !important; padding-top: 15px !important; border-top: 2px dashed #0f172a !important; page-break-inside: avoid !important; }
        .sig-box { text-align: center !important; flex: 1 !important; font-size: 8.5pt !important; font-weight: 800 !important; color: #000 !important; }
        .sig-line { border-top: 1px dashed #0f172a !important; width: 70% !important; margin: 30px auto 0 auto !important; }
    }
</style>

<div class="show-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    
    <!-- Header for Print -->
    <div class="print-only-header">
        <div>
            <h1><?= $t['title'] ?></h1>
            <p><?= $isRtl ? 'تاريخ الطباعة:' : 'Print Date:' ?> <?= date('Y-m-d H:i') ?></p>
        </div>
        <div style="text-align: <?= $isRtl ? 'left' : 'right' ?>;">
            <h2><?= htmlspecialchars($companyName) ?></h2>
            <p><?= htmlspecialchars($branchName) ?></p>
        </div>
    </div>

    <!-- Main Profile Bar -->
    <div class="profile-header">
        <div class="profile-info">
            <div class="avatar"><?= htmlspecialchars($initial) ?></div>
            <div>
                <h1 class="cust-name"><?= htmlspecialchars($custName) ?></h1>
                <div class="cust-code"><i class="ph-bold ph-hash"></i> <?= htmlspecialchars($customer->code) ?></div>
            </div>
        </div>
        <div class="actions-group no-print">
            <button onclick="window.print()" class="btn btn-light"><i class="ph-bold ph-printer"></i> <?= $t['print'] ?></button>
            <a href="/ERP/sales/customers" class="btn btn-light"><i class="ph-bold <?= $isRtl ? 'ph-arrow-right' : 'ph-arrow-left' ?>"></i> <?= $t['back'] ?></a>
            <a href="/ERP/sales/customers/<?= $customer->id ?>/edit" class="btn btn-primary"><i class="ph-bold ph-pencil-simple"></i> <?= $t['edit'] ?></a>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-blue"><i class="ph-duotone ph-receipt"></i></div>
            <div class="stat-details">
                <h5><?= $t['orders'] ?></h5>
                <p class="value"><?= number_format($stats->orders_count ?? 0) ?></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-green"><i class="ph-duotone ph-coins"></i></div>
            <div class="stat-details">
                <h5><?= $t['spent'] ?></h5>
                <p class="value"><?= number_format(convert_amount($stats->total_spent ?? 0), 2) ?> <span style="font-size:0.85rem; font-weight:700; color:#64748b;"><?= htmlspecialchars($currency) ?></span></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-purple"><i class="ph-duotone ph-shield-check"></i></div>
            <div class="stat-details">
                <h5><?= $t['credit'] ?></h5>
                <p class="value"><?= number_format(convert_amount($customer->credit_limit ?? 0), 2) ?> <span style="font-size:0.85rem; font-weight:700; color:#64748b;"><?= htmlspecialchars($currency) ?></span></p>
            </div>
        </div>
    </div>

    <!-- Detailed Panels -->
    <div class="details-grid">
        <div class="details-card">
            <h4><i class="ph-duotone ph-identification-card text-blue-500 no-print" style="font-size: 1.5rem;"></i> <?= $t['basic_info'] ?></h4>
            <ul class="info-list">
                <li>
                    <span class="info-label"><i class="ph-bold ph-buildings text-slate-400 no-print"></i> <?= $t['branch'] ?></span>
                    <span class="info-value"><?= htmlspecialchars($branchName) ?></span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-phone text-slate-400 no-print"></i> <?= $t['phone'] ?></span>
                    <span class="info-value" dir="ltr" style="text-align: <?= $isRtl ? 'right' : 'left' ?>; color: #2563eb; font-family: monospace;"><?= htmlspecialchars($customer->phone ?? '---') ?></span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-envelope text-slate-400 no-print"></i> <?= $t['email'] ?></span>
                    <span class="info-value"><?= htmlspecialchars($customer->email ?? '---') ?></span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-map-pin text-slate-400 no-print"></i> <?= $t['address'] ?></span>
                    <span class="info-value"><?= htmlspecialchars($customer->address ?? '---') ?></span>
                </li>
            </ul>
        </div>

        <div class="details-card">
            <h4><i class="ph-duotone ph-bank text-blue-500 no-print" style="font-size: 1.5rem;"></i> <?= $t['fin_info'] ?></h4>
            <ul class="info-list">
                <li>
                    <span class="info-label"><i class="ph-bold ph-receipt text-slate-400 no-print"></i> <?= $t['tax_no'] ?></span>
                    <span class="info-value" style="font-family: monospace; letter-spacing: 0.5px;"><?= htmlspecialchars($customer->tax_number ?? '---') ?></span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-toggle-left text-slate-400 no-print"></i> <?= $t['status'] ?></span>
                    <span class="info-value">
                        <?php if($customer->is_active): ?>
                            <span class="status-badge status-active"><?= $t['active'] ?></span>
                        <?php else: ?>
                            <span class="status-badge status-inactive"><?= $t['inactive'] ?></span>
                        <?php endif; ?>
                    </span>
                </li>
                <li>
                    <span class="info-label"><i class="ph-bold ph-calendar-blank text-slate-400 no-print"></i> <?= $t['joined'] ?></span>
                    <span class="info-value"><?= date('M d, Y', strtotime($customer->created_at ?? time())) ?></span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Official Signatures Section (Visible on Print Only) -->
    <div class="print-signatures">
        <div class="sig-box">
            <div><?= $t['sig_sales'] ?></div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-box">
            <div><?= $t['sig_audit'] ?></div>
            <div class="sig-line"></div>
        </div>
        <div class="sig-box">
            <div><?= $t['sig_manager'] ?></div>
            <div class="sig-line"></div>
        </div>
    </div>

</div>