<?php
// Path: resources/views/auth/login.php

if (session_status() === PHP_SESSION_NONE) session_start();

$currentLocale = $_SESSION['locale'] ?? 'ar';
$isAr = ($currentLocale === 'ar');
$oldUsername = $_POST['username'] ?? $_POST['email'] ?? '';
$flashErr = $_SESSION['flash_err'] ?? null;
$flashMsg = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_err'], $_SESSION['flash_msg']);
?>
<div style="text-align: start;">
    <div style="text-align: center; margin-bottom: var(--spacing-xl, 24px);">
        <h2 style="color: var(--color-primary-900, #0f172a); font-weight: 700; margin-bottom: 8px; font-size: 1.5rem;"><?= $isAr ? 'مرحباً بعودتك' : 'Welcome Back' ?></h2>
        <p style="color: var(--color-text-muted, #94a3b8); font-size: 0.875rem;">NOUR TRUST IT DEVELOPMENT & WEB SOLUTIONS</p>
    </div>

    <!-- تنبيهات الأخطاء والرسائل -->
    <?php if ($flashErr): ?>
        <div style="background: #fef2f2; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.875rem; display: flex; align-items: center; gap: 8px; border: 1px solid #fecaca; font-weight: 600;">
            <i class="ph ph-warning-circle" style="font-size: 1.2rem;"></i>
            <?= htmlspecialchars($flashErr) ?>
        </div>
    <?php endif; ?>

    <?php if ($flashMsg): ?>
        <div style="background: #ecfdf5; color: #059669; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.875rem; display: flex; align-items: center; gap: 8px; border: 1px solid #a7f3d0; font-weight: 600;">
            <i class="ph ph-check-circle" style="font-size: 1.2rem;"></i>
            <?= htmlspecialchars($flashMsg) ?>
        </div>
    <?php endif; ?>

    <form action="/ERP/login" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        
        <!-- اسم المستخدم أو البريد الإلكتروني -->
        <div class="erp-form-group" style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 0.875rem;">
                <?= $isAr ? 'اسم المستخدم أو البريد الإلكتروني' : 'Username or Email' ?> <span style="color: red;">*</span>
            </label>
            <input type="text" name="username" value="<?= htmlspecialchars($oldUsername) ?>" required placeholder="superadmin / name@nourtrust.com" 
                   style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.875rem; background: #ffffff; outline: none;">
        </div>

        <!-- كلمة المرور -->
        <div class="erp-form-group" style="margin-bottom: 16px;">
            <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 0.875rem;">
                <?= $isAr ? 'كلمة المرور' : 'Password' ?> <span style="color: red;">*</span>
            </label>
            <div style="position: relative; display: flex; align-items: center;">
                <input type="password" name="password" id="login-password" required placeholder="••••••••" 
                       style="width: 100%; padding: 10px 12px; padding-inline-end: 40px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 0.875rem; background: #ffffff; outline: none;">
                <button type="button" onclick="togglePassword('login-password', 'eye-icon')" style="position: absolute; <?= $isAr ? 'left: 10px;' : 'right: 10px;' ?> background: none; border: none; cursor: pointer; color: #94a3b8; display: flex; align-items: center; justify-content: center;">
                    <i id="eye-icon" class="ph ph-eye" style="font-size: 1.2rem;"></i>
                </button>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-size: 0.875rem;">
            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                <input type="checkbox" name="remember" style="accent-color: #2563eb; width: 16px; height: 16px;">
                <span style="color: #475569; padding-top: 2px;"><?= $isAr ? 'تذكرني' : 'Remember Me' ?></span>
            </label>
            <a href="/ERP/forgot-password" style="color: #2563eb; text-decoration: none; font-weight: 600;"><?= $isAr ? 'نسيت كلمة المرور؟' : 'Forgot Password?' ?></a>
        </div>

        <button type="submit" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);">
            <i class="ph ph-sign-in" style="font-size: 1.2rem;"></i>
            <?= $isAr ? 'تسجيل الدخول' : 'Sign In' ?>
        </button>
    </form>
    
    <div style="margin-top: 24px; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 16px;">
        <p style="color: #94a3b8; font-size: 0.75rem;">
            &copy; <?= date('Y') ?> NOUR TRUST ERP. <?= $isAr ? 'جميع الحقوق محفوظة.' : 'All Rights Reserved.' ?>
        </p>
    </div>
</div>

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('ph-eye');
        icon.classList.add('ph-eye-closed');
    } else {
        input.type = 'password';
        icon.classList.remove('ph-eye-closed');
        icon.classList.add('ph-eye');
    }
}
</script>