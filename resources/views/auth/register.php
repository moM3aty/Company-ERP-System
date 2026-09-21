<?php
// Path: resources/views/auth/register.php

if (session_status() === PHP_SESSION_NONE) session_start();
$currentLocale = $locale ?? $_SESSION['locale'] ?? 'ar';
$isAr = ($currentLocale === 'ar');
?>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
    .nt-erp-fixed-wrap { position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; display: flex; background: #ffffff; z-index: 99999; direction: <?= $isAr ? 'rtl' : 'ltr' ?>; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Plus Jakarta Sans', sans-serif" ?>; box-sizing: border-box; overflow: hidden; }
    .nt-erp-fixed-wrap * { box-sizing: border-box; }
    
    .nt-visual-side { flex: 1; position: relative; display: flex; flex-direction: column; justify-content: flex-end; padding: 4rem; background-image: url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=2000&auto=format&fit=crop'); background-size: cover; background-position: center; background-repeat: no-repeat; }
    .nt-visual-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(10, 17, 40, 0.9) 0%, rgba(29, 78, 216, 0.7) 100%); z-index: 1; }
    .nt-visual-content { position: relative; z-index: 2; color: white; max-width: 600px; animation: slideUp 1s ease forwards; }
    .nt-visual-title { font-size: 3.2rem; font-weight: 900; line-height: 1.2; margin: 0 0 1rem 0; letter-spacing: -0.02em; }
    .nt-visual-desc { font-size: 1.15rem; font-weight: 500; line-height: 1.6; color: rgba(255,255,255,0.85); margin: 0; }
    
    .nt-form-side { width: 500px; flex-shrink: 0; background: #ffffff; display: flex; flex-direction: column; justify-content: center; padding: 2rem 4rem; position: relative; z-index: 10; box-shadow: <?= $isAr ? '-20px' : '20px' ?> 0 40px rgba(0,0,0,0.05); overflow-y: auto; text-align: start; }
    
    .nt-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 2.5rem; text-decoration: none; }
    .nt-logo img { height: 45px; object-fit: contain; }
    .nt-logo-text { font-size: 1.4rem; font-weight: 900; color: #0A1128; letter-spacing: -0.02em; line-height: 1; margin:0;}
    .nt-logo-sub { font-size: 0.7rem; font-weight: 800; color: #1D4ED8; letter-spacing: 0.1em; text-transform: uppercase; margin: 4px 0 0 0; }

    .nt-header { margin-bottom: 2rem; }
    .nt-header h2 { font-size: 1.8rem; font-weight: 800; color: #0F172A; margin: 0 0 8px 0; }
    .nt-header p { font-size: 0.95rem; color: #64748B; font-weight: 500; margin: 0; }

    .nt-form-group { margin-bottom: 1.2rem; }
    .nt-label { display: block; font-size: 0.9rem; font-weight: 700; color: #334155; margin-bottom: 8px; }
    .nt-input { width: 100%; padding: 14px 16px; font-size: 0.95rem; font-family: inherit; font-weight: 600; color: #0F172A; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; transition: all 0.3s ease; outline: none; }
    .nt-input:focus { border-color: #1D4ED8; background: #ffffff; box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.1); }
    
    .nt-btn { width: 100%; padding: 14px; font-size: 1.05rem; font-weight: 800; color: white; background: #1D4ED8; border: none; border-radius: 12px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 1rem; }
    .nt-btn:hover { background: #0A1128; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(10, 17, 40, 0.15); }
    
    .nt-link { color: #1D4ED8; text-decoration: none; font-weight: 700; font-size: 0.9rem; transition: 0.3s; }
    .nt-link:hover { color: #0A1128; text-decoration: underline; }

    @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 992px) { .nt-visual-side { display: none; } .nt-form-side { width: 100%; padding: 2rem; box-shadow: none; justify-content: flex-start; padding-top: 4rem; } }
</style>

<div class="nt-erp-fixed-wrap">
    
    <div class="nt-visual-side">
        <div class="nt-visual-overlay"></div>
        <div class="nt-visual-content">
            <h1 class="nt-visual-title"><?= $isAr ? 'ابدأ رحلة التحول الرقمي' : 'Start Your Digital Transformation' ?></h1>
            <p class="nt-visual-desc">
                <?= $isAr ? 'انضم إلى منصة نور ترست. بيئة عمل متكاملة مصممة خصيصاً للشركات لرفع الكفاءة التشغيلية وتحقيق أقصى معدلات النمو.' : 'Join Nour Trust platform. A fully integrated workspace tailored for enterprises to maximize operational efficiency and growth.' ?>
            </p>
        </div>
    </div>

    <div class="nt-form-side">
        <a href="https://nourtrust.com" class="nt-logo">
            <img src="https://nourtrust.com/images/logo.png" alt="Nour Trust Logo" onerror="this.src='/images/logo.png'">
            <div>
                <div class="nt-logo-text">Nour Trust</div>
                <div class="nt-logo-sub">ERP System</div>
            </div>
        </a>

        <div class="nt-header">
            <h2><?= $isAr ? 'إنشاء حساب مؤسسي' : 'Enterprise Registration' ?></h2>
            <p><?= $isAr ? 'أدخل بياناتك لإعداد مساحة العمل الخاصة بك' : 'Enter your details to set up your workspace' ?></p>
        </div>

        <form action="/ERP/register" method="POST">
            <div class="nt-form-group">
                <label class="nt-label"><?= $isAr ? 'الاسم بالكامل' : 'Full Name' ?> <span style="color:#dc2626">*</span></label>
                <input type="text" name="name" class="nt-input" required placeholder="<?= $isAr ? 'مثال: أحمد إبراهيم' : 'e.g. Ahmed Ibrahim' ?>">
            </div>

            <div class="nt-form-group">
                <label class="nt-label"><?= $isAr ? 'البريد الإلكتروني للعمل' : 'Work Email' ?> <span style="color:#dc2626">*</span></label>
                <input type="email" name="email" class="nt-input" required placeholder="name@company.com">
            </div>

            <div class="nt-form-group">
                <label class="nt-label"><?= $isAr ? 'كلمة المرور' : 'Password' ?> <span style="color:#dc2626">*</span></label>
                <input type="password" name="password" class="nt-input" required placeholder="••••••••">
            </div>

            <div class="nt-form-group">
                <label class="nt-label"><?= $isAr ? 'تأكيد كلمة المرور' : 'Confirm Password' ?> <span style="color:#dc2626">*</span></label>
                <input type="password" name="password_confirmation" class="nt-input" required placeholder="••••••••">
            </div>

            <div style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: #475569;">
                    <input type="checkbox" name="terms" required style="accent-color: #1D4ED8; width: 16px; height: 16px; border-radius: 4px; margin: 2px 0 0 0;">
                    <span style="padding-top:1px; line-height: 1.5;">
                        <?= $isAr ? 'أوافق على' : 'I agree to the' ?> 
                        <a href="#" class="nt-link"><?= $isAr ? 'شروط الخدمة' : 'Terms of Service' ?></a> 
                        <?= $isAr ? 'و' : 'and' ?> 
                        <a href="#" class="nt-link"><?= $isAr ? 'سياسة الخصوصية' : 'Privacy Policy' ?></a>.
                    </span>
                </label>
            </div>

            <button type="submit" class="nt-btn">
                <i class="ph-bold ph-shield-check" style="font-size: 1.3rem;"></i>
                <?= $isAr ? 'تسجيل الحساب الموثق' : 'Register Secure Account' ?>
            </button>
        </form>
        
        <div style="margin-top: 2rem; text-align: center;">
            <p style="color: #64748B; font-size: 0.95rem; font-weight: 600; margin: 0;">
                <?= $isAr ? 'لديك حساب بالفعل؟' : 'Already have an account?' ?> 
                <a href="/ERP/login" class="nt-link"><?= $isAr ? 'تسجيل الدخول' : 'Sign In' ?></a>
            </p>
        </div>
    </div>
</div>