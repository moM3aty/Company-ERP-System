<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$isRtl = ($_SESSION['locale'] ?? 'ar') === 'ar';
$flashMsg = $_SESSION['flash_msg'] ?? null;
$flashErr = $_SESSION['flash_err'] ?? null;
unset($_SESSION['flash_msg'], $_SESSION['flash_err']);
$s = $settings ?? [];
?>

<style>
    :root {
        --st-primary: #ea580c;
        --st-dark: #c2410c;
        --st-light: #fff7ed;
        --st-border: #ffedd5;
        --st-text: #1e293b;
        --st-muted: #64748b;
    }

    .st-wrapper { max-width: 1100px; margin: 0 auto; padding-bottom: 90px; font-family: <?= $isRtl ? "'Cairo', sans-serif" : "'Inter', sans-serif" ?>; }
    
    .st-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; flex-wrap: wrap; gap: 16px; }
    .st-title-box { display: flex; align-items: center; gap: 16px; }
    .st-icon { width: 50px; height: 50px; background: var(--st-light); color: var(--st-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; box-shadow: 0 4px 10px rgba(234, 88, 12, 0.15); border: 1px solid var(--st-border); }
    .st-title { margin: 0; color: var(--st-text); font-size: 1.6rem; font-weight: 900; }

    /* Tabs Navigation */
    .settings-tabs { display: flex; gap: 4px; border-bottom: 2px solid #e2e8f0; margin-bottom: 24px; flex-wrap: wrap; }
    .tab-btn { padding: 12px 18px; border: none; background: transparent; font-family: inherit; font-size: 0.9rem; font-weight: 800; color: var(--st-muted); cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; border-radius: 8px 8px 0 0;}
    .tab-btn:hover { color: var(--st-primary); background: #f8fafc; }
    .tab-btn.active { color: var(--st-primary); border-bottom-color: var(--st-primary); background: var(--st-light); }

    .tab-pane { display: none; animation: fadeIn 0.3s ease; }
    .tab-pane.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

    .form-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 24px; position: relative; overflow: hidden; }
    .form-card::before { content: ''; position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: var(--st-primary); }
    [dir="ltr"] .form-card::before { right: auto; left: 0; }

    .card-title { font-size: 1.15rem; font-weight: 800; color: var(--st-text); margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; }
    
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    @media(max-width: 900px){ .grid-3 { grid-template-columns: repeat(2, 1fr); } }
    @media(max-width: 600px){ .grid-3, .grid-2 { grid-template-columns: 1fr; } }

    .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
    .input-label { font-size: 0.85rem; font-weight: 800; color: var(--st-text); }
    .form-control { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.9rem; background: #f8fafc; font-weight: 600; outline: none; transition: 0.2s; }
    .form-control:focus { border-color: var(--st-primary); box-shadow: 0 0 0 3px var(--st-light); background: #ffffff; }

    .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(12px); border-top: 1px solid #e2e8f0; padding: 16px 32px; display: flex; justify-content: flex-end; gap: 16px; z-index: 100; }
    .btn-save { background: linear-gradient(135deg, var(--st-primary), var(--st-dark)); color: white; border: none; padding: 12px 32px; border-radius: 10px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 1rem; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25); transition: 0.2s; }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(234, 88, 12, 0.35); }
</style>

<div class="st-wrapper" dir="<?= $isRtl ? 'rtl' : 'ltr' ?>">
    <div class="st-header">
        <div class="st-title-box">
            <div class="st-icon"><i class="ph-duotone ph-sliders-horizontal"></i></div>
            <div>
                <h2 class="st-title">الإعدادات العامة للنظام (System Settings)</h2>
                <p style="margin:4px 0 0 0; color:var(--st-muted);">لوحة التحكم المركزية للشركة، المالية، السيرفرات، والأمان.</p>
            </div>
        </div>
    </div>

    <?php if($flashMsg): ?><div style="background: #ecfdf5; color: #059669; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #a7f3d0;"><i class="ph-fill ph-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div><?php endif; ?>
    <?php if($flashErr): ?><div style="background: #fef2f2; color: #dc2626; padding: 14px; border-radius: 10px; margin-bottom: 20px; font-weight: 700; border: 1px solid #fecaca;"><i class="ph-fill ph-warning-circle"></i> <?= htmlspecialchars($flashErr) ?></div><?php endif; ?>

    <form action="/ERP/admin/settings/update" method="POST" enctype="multipart/form-data">
        
        <!-- التبويبات الرئيسية السبعة -->
        <div class="settings-tabs">
            <button type="button" class="tab-btn active" onclick="switchTab(event, 'tab-company')"><i class="ph-duotone ph-buildings"></i> الشركة والهوية</button>
            <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-tax')"><i class="ph-duotone ph-receipt"></i> الضرائب والقانونية</button>
            <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-financial')"><i class="ph-duotone ph-coins"></i> المالية والتشغيل</button>
            <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-hr')"><i class="ph-duotone ph-users-three"></i> ثوابت الـ HR</button>
            <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-smtp')"><i class="ph-duotone ph-envelope-simple"></i> البريد و SMTP</button>
            <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-security')"><i class="ph-duotone ph-shield-check"></i> الأمان والسياسات</button>
            <button type="button" class="tab-btn" onclick="switchTab(event, 'tab-system')"><i class="ph-duotone ph-gear"></i> تفضيلات النظام</button>
        </div>

        <!-- 1. الشركة والهوية -->
        <div id="tab-company" class="tab-pane active">
            <div class="form-card">
                <h3 class="card-title"><i class="ph-duotone ph-buildings" style="color:var(--st-primary);"></i> بيانات المنشأة والهوية الرسمية</h3>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="input-label">اسم المؤسسة / الشركة الرئيسي <span style="color:red">*</span></label>
                        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($s['company_name'] ?? 'NOUR TRUST') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="input-label">المدير العام / المسؤول المفوض</label>
                        <input type="text" name="manager_name" class="form-control" value="<?= htmlspecialchars($s['manager_name'] ?? '') ?>" placeholder="الاسم للظهور بالتوقيعات...">
                    </div>
                </div>

                <div class="grid-3">
                    <div class="form-group">
                        <label class="input-label">البريد الإلكتروني للشركة</label>
                        <input type="email" name="company_email" class="form-control" value="<?= htmlspecialchars($s['company_email'] ?? '') ?>" placeholder="info@company.com">
                    </div>
                    <div class="form-group">
                        <label class="input-label">رقم الهاتف / الجوال</label>
                        <input type="text" name="company_phone" class="form-control" value="<?= htmlspecialchars($s['company_phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="input-label">الموقع الإلكتروني (Website)</label>
                        <input type="text" name="company_website" class="form-control" value="<?= htmlspecialchars($s['company_website'] ?? '') ?>" placeholder="https://www...">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="input-label">طبيعة نشاط الشركة (Industry)</label>
                        <input type="text" name="industry_type" class="form-control" value="<?= htmlspecialchars($s['industry_type'] ?? '') ?>" placeholder="مقاولات، تجزئة، خدمات برمجية...">
                    </div>
                    <div class="form-group">
                        <label class="input-label">العنوان الرئيسي للمقر</label>
                        <input type="text" name="company_address" class="form-control" value="<?= htmlspecialchars($s['company_address'] ?? '') ?>" placeholder="المدينة، الشارع، المبنى...">
                    </div>
                </div>

                <div class="form-group" style="margin-top:12px;">
                    <label class="input-label">شعار الشركة (للفواتير والتقارير)</label>
                    <input type="file" name="company_logo" class="form-control" accept="image/*">
                    <?php if(!empty($s['company_logo'])): ?>
                        <div style="margin-top:10px;"><img src="/ERP<?= htmlspecialchars($s['company_logo']) ?>" alt="Logo" style="max-height:60px; border-radius:8px; border:1px solid #e2e8f0; padding:4px; background:#fff;"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2. الضرائب والسجل -->
        <div id="tab-tax" class="tab-pane">
            <div class="form-card">
                <h3 class="card-title"><i class="ph-duotone ph-receipt" style="color:var(--st-primary);"></i> البيانات القانونية والضريبية</h3>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="input-label">الرقم الضريبي (Tax VAT Number)</label>
                        <input type="text" name="tax_number" class="form-control" style="font-family:monospace;" value="<?= htmlspecialchars($s['tax_number'] ?? '') ?>" placeholder="3000XXXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label class="input-label">رقم السجل التجاري (CR Number)</label>
                        <input type="text" name="cr_number" class="form-control" style="font-family:monospace;" value="<?= htmlspecialchars($s['cr_number'] ?? '') ?>" placeholder="1010XXXXXX">
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="input-label">المأمورية الضريبية التابع لها</label>
                        <input type="text" name="tax_office" class="form-control" value="<?= htmlspecialchars($s['tax_office'] ?? '') ?>" placeholder="اسم المأمورية...">
                    </div>
                    <div class="form-group">
                        <label class="input-label">نسبة ضريبة القيمة المضافة الافتراضية (%)</label>
                        <input type="number" step="0.01" name="tax_rate" class="form-control" style="font-family:monospace;" value="<?= htmlspecialchars($s['tax_rate'] ?? '15') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="input-label">الشروط والأحكام الثابتة أسفل الفواتير</label>
                    <textarea name="invoice_terms" class="form-control" rows="3" placeholder="البضاعة المباعة لا ترد ولا تستبدل بعد 14 يوم..."><?= htmlspecialchars($s['invoice_terms'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- 3. المالية والتشغيل -->
        <div id="tab-financial" class="tab-pane">
            <div class="form-card">
                <h3 class="card-title"><i class="ph-duotone ph-coins" style="color:var(--st-primary);"></i> الإعدادات المالية والتشغيلية</h3>
                <div class="grid-3">
                    <div class="form-group">
                        <label class="input-label">العملة الأساسية للنظام</label>
                        <select name="default_currency" class="form-control">
                            <option value="EGP" <?= ($s['default_currency'] ?? 'EGP') === 'EGP' ? 'selected' : '' ?>>جنيه مصري (EGP)</option>
                            <option value="SAR" <?= ($s['default_currency'] ?? '') === 'SAR' ? 'selected' : '' ?>>ريال سعودي (SAR)</option>
                            <option value="USD" <?= ($s['default_currency'] ?? '') === 'USD' ? 'selected' : '' ?>>دولار أمريكي (USD)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="input-label">بداية السنة المالية</label>
                        <input type="text" name="fiscal_year_start" class="form-control" value="<?= htmlspecialchars($s['fiscal_year_start'] ?? '01-01') ?>" placeholder="01-01">
                    </div>
                    <div class="form-group">
                        <label class="input-label">شروط الدفع الافتراضية</label>
                        <select name="payment_terms" class="form-control">
                            <option value="Cash" <?= ($s['payment_terms'] ?? '') === 'Cash' ? 'selected' : '' ?>>نقدي (Cash)</option>
                            <option value="Net 15" <?= ($s['payment_terms'] ?? '') === 'Net 15' ? 'selected' : '' ?>>آجل 15 يوم (Net 15)</option>
                            <option value="Net 30" <?= ($s['payment_terms'] ?? 'Net 30') === 'Net 30' ? 'selected' : '' ?>>آجل 30 يوم (Net 30)</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="input-label">طريقة تقييم المخزون وتسعير المنصرف</label>
                        <select name="inventory_costing" class="form-control">
                            <option value="WAC" <?= ($s['inventory_costing'] ?? 'WAC') === 'WAC' ? 'selected' : '' ?>>المتوسط المرجح (WAC)</option>
                            <option value="FIFO" <?= ($s['inventory_costing'] ?? '') === 'FIFO' ? 'selected' : '' ?>>ما يرد أولاً يصرف أولاً (FIFO)</option>
                            <option value="LIFO" <?= ($s['inventory_costing'] ?? '') === 'LIFO' ? 'selected' : '' ?>>ما يرد أخيراً يصرف أولاً (LIFO)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="input-label">بادئة رقم الفاتورة (Prefix)</label>
                        <input type="text" name="invoice_prefix" class="form-control" value="<?= htmlspecialchars($s['invoice_prefix'] ?? 'INV-') ?>" placeholder="INV-">
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. الموارد البشرية HR -->
        <div id="tab-hr" class="tab-pane">
            <div class="form-card">
                <h3 class="card-title"><i class="ph-duotone ph-users-three" style="color:var(--st-primary);"></i> ثوابت الموارد البشرية والرواتب</h3>
                <div class="grid-3">
                    <div class="form-group">
                        <label class="input-label">ساعات العمل القياسية (يومياً)</label>
                        <input type="number" name="hr_working_hours" class="form-control" value="<?= htmlspecialchars($s['hr_working_hours'] ?? '8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="input-label">فترة السماح للتأخير (دقائق)</label>
                        <input type="number" name="hr_grace_period" class="form-control" value="<?= htmlspecialchars($s['hr_grace_period'] ?? '15') ?>">
                    </div>
                    <div class="form-group">
                        <label class="input-label">معامل احتساب الوقت الإضافي (Overtime)</label>
                        <input type="number" step="0.1" name="hr_overtime_rate" class="form-control" value="<?= htmlspecialchars($s['hr_overtime_rate'] ?? '1.5') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="input-label">أيام العطلة الأسبوعية (Weekend)</label>
                    <select name="hr_weekend_days" class="form-control">
                        <option value="Friday" <?= ($s['hr_weekend_days'] ?? '') === 'Friday' ? 'selected' : '' ?>>الجمعة فقط</option>
                        <option value="Friday_Saturday" <?= ($s['hr_weekend_days'] ?? 'Friday_Saturday') === 'Friday_Saturday' ? 'selected' : '' ?>>الجمعة والسبت</option>
                        <option value="Saturday_Sunday" <?= ($s['hr_weekend_days'] ?? '') === 'Saturday_Sunday' ? 'selected' : '' ?>>السبت والأحد</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- 5. البريد SMTP -->
        <div id="tab-smtp" class="tab-pane">
            <div class="form-card">
                <h3 class="card-title"><i class="ph-duotone ph-envelope-simple" style="color:var(--st-primary);"></i> إعدادات خادم البريد (SMTP Mailer)</h3>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="input-label">خادم البريد (SMTP Host)</label>
                        <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($s['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="form-group">
                        <label class="input-label">منفذ الاتصال (Port)</label>
                        <input type="number" name="smtp_port" class="form-control" value="<?= htmlspecialchars($s['smtp_port'] ?? '587') ?>">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="input-label">اسم المستخدم (Username / Email)</label>
                        <input type="text" name="smtp_username" class="form-control" value="<?= htmlspecialchars($s['smtp_username'] ?? '') ?>" placeholder="no-reply@company.com">
                    </div>
                    <div class="form-group">
                        <label class="input-label">كلمة المرور (Password / App Password)</label>
                        <input type="password" name="smtp_password" class="form-control" value="<?= htmlspecialchars($s['smtp_password'] ?? '') ?>" placeholder="••••••••">
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label class="input-label">التشفير (Encryption)</label>
                        <select name="smtp_encryption" class="form-control">
                            <option value="tls" <?= ($s['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= ($s['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="input-label">البريد المُرسل منه (From Address)</label>
                        <input type="email" name="mail_from_address" class="form-control" value="<?= htmlspecialchars($s['mail_from_address'] ?? '') ?>" placeholder="info@company.com">
                    </div>
                    <div class="form-group">
                        <label class="input-label">اسم المُرسل (From Name)</label>
                        <input type="text" name="mail_from_name" class="form-control" value="<?= htmlspecialchars($s['mail_from_name'] ?? 'ERP System') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. الأمان -->
        <div id="tab-security" class="tab-pane">
            <div class="form-card">
                <h3 class="card-title"><i class="ph-duotone ph-shield-check" style="color:var(--st-primary);"></i> سياسات الأمان والجلسات</h3>
                <div class="grid-3">
                    <div class="form-group">
                        <label class="input-label">مدة انتهاء الجلسة (Session Timeout)</label>
                        <select name="session_timeout" class="form-control">
                            <option value="15" <?= ($s['session_timeout'] ?? '') == '15' ? 'selected' : '' ?>>15 دقيقة</option>
                            <option value="30" <?= ($s['session_timeout'] ?? '') == '30' ? 'selected' : '' ?>>30 دقيقة</option>
                            <option value="60" <?= ($s['session_timeout'] ?? '60') == '60' ? 'selected' : '' ?>>ساعة واحدة</option>
                            <option value="120" <?= ($s['session_timeout'] ?? '') == '120' ? 'selected' : '' ?>>ساعتان</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="input-label">سياسة تعقيد كلمة المرور</label>
                        <select name="password_complexity" class="form-control">
                            <option value="low" <?= ($s['password_complexity'] ?? '') === 'low' ? 'selected' : '' ?>>بسيطة (أرقام وحروف)</option>
                            <option value="medium" <?= ($s['password_complexity'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>متوسطة (رموز + أرقام + حروف)</option>
                            <option value="high" <?= ($s['password_complexity'] ?? '') === 'high' ? 'selected' : '' ?>>معقدة (رموز + أرقام + حروف كبيرة وصغيرة)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="input-label">الحد الأقصى لمحاولات الدخول الخاطئة</label>
                        <input type="number" name="max_login_attempts" class="form-control" value="<?= htmlspecialchars($s['max_login_attempts'] ?? '5') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. تفضيلات النظام -->
        <div id="tab-system" class="tab-pane">
            <div class="form-card">
                <h3 class="card-title"><i class="ph-duotone ph-gear" style="color:var(--st-primary);"></i> الخيارات التشغيلية للواجهة</h3>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="input-label">النطاق الزمني للسيرفر (Timezone)</label>
                        <select name="timezone" class="form-control">
                            <option value="Africa/Cairo" <?= ($s['timezone'] ?? 'Africa/Cairo') === 'Africa/Cairo' ? 'selected' : '' ?>>القاهرة (GMT+2)</option>
                            <option value="Asia/Riyadh" <?= ($s['timezone'] ?? '') === 'Asia/Riyadh' ? 'selected' : '' ?>>الرياض (GMT+3)</option>
                            <option value="Asia/Dubai" <?= ($s['timezone'] ?? '') === 'Asia/Dubai' ? 'selected' : '' ?>>دبي (GMT+4)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="input-label">تنسيق عرض التاريخ الإقليمي</label>
                        <select name="date_format" class="form-control">
                            <option value="Y-m-d" <?= ($s['date_format'] ?? 'Y-m-d') === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD (2026-08-27)</option>
                            <option value="d/m/Y" <?= ($s['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY (27/08/2026)</option>
                        </select>
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="input-label">اللغة الافتراضية للواجهة</label>
                        <select name="system_language" class="form-control">
                            <option value="ar" <?= ($s['system_language'] ?? 'ar') === 'ar' ? 'selected' : '' ?>>العربية (Arabic)</option>
                            <option value="en" <?= ($s['system_language'] ?? '') === 'en' ? 'selected' : '' ?>>English</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="input-label">عدد العناصر بالجدول لكل صفحة (Pagination)</label>
                        <select name="items_per_page" class="form-control">
                            <option value="15" <?= ($s['items_per_page'] ?? '15') == '15' ? 'selected' : '' ?>>15 عنصر</option>
                            <option value="25" <?= ($s['items_per_page'] ?? '') == '25' ? 'selected' : '' ?>>25 عنصر</option>
                            <option value="50" <?= ($s['items_per_page'] ?? '') == '50' ? 'selected' : '' ?>>50 عنصر</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- الشريط السفلي للحفظ -->
        <div class="sticky-footer">
            <button type="submit" class="btn-save"><i class="ph-bold ph-floppy-disk"></i> حفظ وتحديث النظام بالكامل</button>
        </div>

    </form>
</div>

<script>
function switchTab(evt, tabId) {
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    evt.currentTarget.classList.add('active');
}
</script>