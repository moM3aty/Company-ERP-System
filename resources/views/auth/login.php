<?php
// Path: resources/views/auth/login.php

if (session_status() === PHP_SESSION_NONE) session_start();

// التقاط وتحديث اللغة من الرابط
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'])) {
    $_SESSION['locale'] = $_GET['lang'];
}

$currentLocale = $_SESSION['locale'] ?? 'ar';
$isAr = ($currentLocale === 'ar');
$oldUsername = $_POST['username'] ?? $_POST['email'] ?? '';
$flashErr = $_SESSION['flash_err'] ?? null;
$flashMsg = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_err'], $_SESSION['flash_msg']);

// رابط التبديل الفعال
$toggleLang = '?lang=' . ($isAr ? 'en' : 'ar');
?>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
    body, html { margin: 0; padding: 0; width: 100%; height: 100%; background: #ffffff; }
    
    .nt-erp-wrap {
        position: fixed; inset: 0; display: flex; width: 100vw; height: 100vh;
        direction: <?= $isAr ? 'rtl' : 'ltr' ?>;
        font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Plus Jakarta Sans', sans-serif" ?>;
        background: #ffffff; z-index: 99999; overflow: hidden;
    }
    .nt-erp-wrap * { box-sizing: border-box; }

    .nt-form-side {
        width: 100%; max-width: 520px; background: #ffffff;
        display: flex; flex-direction: column; justify-content: center;
        padding: 4rem; position: relative; z-index: 10;
        box-shadow: <?= $isAr ? '-20px' : '20px' ?> 0 50px rgba(0,0,0,0.04);
        overflow-y: auto; text-align: start;
    }

    .nt-lang-btn {
        position: absolute; top: 2rem; <?= $isAr ? 'left: 2rem;' : 'right: 2rem;' ?>
        display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
        background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 100px;
        color: #0F172A; font-weight: 700; font-size: 0.85rem; text-decoration: none;
        transition: all 0.3s ease; z-index: 50;
    }
    .nt-lang-btn:hover { background: #E2E8F0; border-color: #CBD5E1; }

    .nt-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 3.5rem; text-decoration: none; }
    .nt-logo img { height: 45px; object-fit: contain; }
    .nt-logo-text { font-size: 1.5rem; font-weight: 900; color: #0F172A; letter-spacing: -0.02em; line-height: 1;}
    .nt-logo-sub { font-size: 0.7rem; font-weight: 800; color: #1D4ED8; letter-spacing: 0.1em; text-transform: uppercase; margin-top: 4px; }

    .nt-header { margin-bottom: 2.5rem; }
    .nt-header h2 { font-size: 2rem; font-weight: 800; color: #0F172A; margin: 0 0 10px 0; letter-spacing: -0.02em; }
    .nt-header p { font-size: 1rem; color: #64748B; font-weight: 500; margin: 0; }

    .nt-form-group { margin-bottom: 1.5rem; position: relative; }
    .nt-label { display: block; font-size: 0.9rem; font-weight: 700; color: #334155; margin-bottom: 8px; }
    
    .nt-input-wrap { position: relative; display: flex; align-items: center; }
    .nt-input-icon { position: absolute; <?= $isAr ? 'right: 16px;' : 'left: 16px;' ?> color: #94A3B8; font-size: 1.2rem; pointer-events: none; }
    
    .nt-input { 
        width: 100%; padding: 14px 16px; padding-inline-start: 45px;
        font-size: 0.95rem; font-family: inherit; font-weight: 600; color: #0F172A;
        background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: 12px;
        transition: all 0.3s ease; outline: none;
    }
    .nt-input:hover { border-color: #CBD5E1; background: #F1F5F9; }
    .nt-input:focus { border-color: #1D4ED8; background: #ffffff; box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.1); }
    
    .nt-eye-btn { position: absolute; <?= $isAr ? 'left: 16px;' : 'right: 16px;' ?> background: none; border: none; color: #94A3B8; cursor: pointer; padding: 0; display: flex; transition: 0.3s; }
    .nt-eye-btn:hover { color: #1D4ED8; }

    .nt-btn { 
        width: 100%; padding: 16px; font-size: 1.05rem; font-weight: 800; color: white; 
        background: linear-gradient(135deg, #1D4ED8, #0A1128); border: none; border-radius: 12px;
        cursor: pointer; transition: all 0.4s ease; display: flex; align-items: center; justify-content: center; gap: 10px;
        margin-top: 1.5rem; box-shadow: 0 10px 25px rgba(29, 78, 216, 0.2);
    }
    .nt-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(29, 78, 216, 0.3); }

    .nt-link { color: #1D4ED8; text-decoration: none; font-weight: 700; font-size: 0.9rem; transition: 0.3s; }
    .nt-link:hover { color: #0A1128; }

    .nt-alert { padding: 14px 16px; border-radius: 12px; margin-bottom: 24px; font-size: 0.95rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .nt-alert-err { background: #FEF2F2; color: #DC2626; border: 1.5px solid #FECACA; }
    .nt-alert-succ { background: #ECFDF5; color: #059669; border: 1.5px solid #A7F3D0; }

    .nt-visual-side { 
        flex: 1; position: relative; display: flex; flex-direction: column; justify-content: center; padding: 5rem;
        background-image: url('https://images.unsplash.com/photo-1460925895917-afdab827c52f?q=80&w=2000&auto=format&fit=crop'); 
        background-size: cover; background-position: center;
    }
    .nt-visual-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(10, 17, 40, 0.9) 0%, rgba(29, 78, 216, 0.7) 100%); z-index: 1; }
    .nt-visual-content { position: relative; z-index: 2; color: white; max-width: 650px; animation: slideUp 1s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .nt-visual-title { font-size: 3.5rem; font-weight: 900; line-height: 1.2; margin: 0 0 1.5rem 0; letter-spacing: -0.02em; }
    .nt-visual-desc { font-size: 1.2rem; font-weight: 500; line-height: 1.7; color: rgba(255,255,255,0.85); margin: 0; }
    
    @keyframes slideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 992px) { .nt-visual-side { display: none; } .nt-form-side { max-width: 100%; padding: 2rem; box-shadow: none; padding-top: 4rem; } }
</style>

<div class="nt-erp-wrap">
    
    <div class="nt-form-side">
        <a href="<?= $toggleLang ?>" class="nt-lang-btn">
            <i class="ph-bold ph-translate"></i> <?= $isAr ? 'English' : 'عربي' ?>
        </a>

        <a href="https://nourtrust.com" class="nt-logo">
            <img src="https://nourtrust.com/images/logo.png" alt="Nour Trust Logo" onerror="this.src='/images/logo.png'">
            <div>
                <div class="nt-logo-text">Nour Trust</div>
                <div class="nt-logo-sub">ERP System</div>
            </div>
        </a>

        <div class="nt-header">
            <h2><?= $isAr ? 'تسجيل الدخول' : 'Welcome Back' ?></h2>
            <p><?= $isAr ? 'الرجاء إدخال بياناتك للوصول إلى لوحة التحكم' : 'Please enter your details to access the dashboard' ?></p>
        </div>

        <?php if ($flashErr): ?>
            <div class="nt-alert nt-alert-err"><i class="ph-fill ph-warning-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashErr) ?></div>
        <?php endif; ?>
        <?php if ($flashMsg): ?>
            <div class="nt-alert nt-alert-succ"><i class="ph-fill ph-check-circle" style="font-size: 1.4rem;"></i> <?= htmlspecialchars($flashMsg) ?></div>
        <?php endif; ?>

        <form action="/ERP/login" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <div class="nt-form-group">
                <label class="nt-label"><?= $isAr ? 'البريد الإلكتروني للعمل' : 'Work Email' ?></label>
                <div class="nt-input-wrap">
                    <i class="ph-duotone ph-envelope-simple nt-input-icon"></i>
                    <input type="text" name="username" class="nt-input" value="<?= htmlspecialchars($oldUsername) ?>" required placeholder="name@company.com">
                </div>
            </div>

            <div class="nt-form-group">
                <label class="nt-label"><?= $isAr ? 'كلمة المرور' : 'Password' ?></label>
                <div class="nt-input-wrap">
                    <i class="ph-duotone ph-lock-key nt-input-icon"></i>
                    <input type="password" name="password" id="login-password" class="nt-input" required placeholder="••••••••" style="<?= $isAr ? 'padding-left: 45px;' : 'padding-right: 45px;' ?>">
                    <button type="button" class="nt-eye-btn" onclick="togglePassword('login-password', 'eye-icon')">
                        <i id="eye-icon" class="ph-bold ph-eye" style="font-size: 1.2rem;"></i>
                    </button>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.9rem; font-weight: 700; color: #475569;">
                    <input type="checkbox" name="remember" style="accent-color: #1D4ED8; width: 16px; height: 16px; border-radius: 4px; margin: 0;">
                    <span style="padding-top:2px;"><?= $isAr ? 'البقاء متصلاً' : 'Keep me signed in' ?></span>
                </label>
                <a href="/ERP/forgot-password" class="nt-link"><?= $isAr ? 'نسيت كلمة المرور؟' : 'Forgot Password?' ?></a>
            </div>

            <button type="submit" class="nt-btn">
                <?= $isAr ? 'الدخول للنظام' : 'Login to System' ?>
                <i class="ph-bold <?= $isAr ? 'ph-arrow-left' : 'ph-arrow-right' ?>" style="font-size: 1.2rem;"></i>
            </button>
        </form>
        
        <div style="margin-top: auto; padding-top: 3rem;">
            <p style="color: #94A3B8; font-size: 0.85rem; font-weight: 600; margin: 0;">
                &copy; <?= date('Y') ?> NOUR TRUST IT DEVELOPMENT.
            </p>
        </div>
    </div>

    <!-- Visual Corporate Side -->
    <div class="nt-visual-side">
        <div class="nt-visual-overlay"></div>
        <div class="nt-visual-content">
            <h1 class="nt-visual-title"><?= $isAr ? 'نظام إدارة الموارد المؤسسية' : 'Enterprise Resource Planning' ?></h1>
            <p class="nt-visual-desc">
                <?= $isAr ? 'تمكين أعمالك بذكاء. أدر مبيعاتك، عملياتك، ومواردك المالية من لوحة تحكم واحدة متطورة ومحمية بالكامل.' : 'Empower your business intelligently. Manage sales, operations, and finances from a single, fully secured advanced dashboard.' ?>
            </p>
        </div>
    </div>

</div>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('ph-eye', 'ph-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('ph-eye-slash', 'ph-eye');
    }
}
</script>