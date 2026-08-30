<?php
// Path: resources/views/auth/reset-password.php

if (session_status() === PHP_SESSION_NONE) session_start();
$currentLocale = $locale ?? $_SESSION['locale'] ?? 'ar';
?>
<div style="text-align: start;">
    <div style="text-align: center; margin-bottom: var(--spacing-xl);">
        <h2 style="color: var(--color-primary-900); font-weight: 700; margin-bottom: 8px; font-size: 1.5rem;"><?= __('auth.new_password', [], $currentLocale) ?></h2>
        <p style="color: var(--color-text-muted); font-size: 0.875rem;">
            <?= $currentLocale === 'ar' ? 'يرجى إدخال كلمة المرور الجديدة. تأكد من أنها قوية وتحتوي على أرقام وحروف.' : 'Please enter your new password. Ensure it is strong and contains alphanumeric characters.' ?>
        </p>
    </div>

    <form action="/ERP/reset-password" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
        
        <div class="erp-form-group" style="margin-bottom: var(--spacing-md);">
            <label style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 0.875rem;">
                <?= __('auth.new_password', [], $currentLocale) ?> <span style="color: red;">*</span>
            </label>
            <input type="password" id="new_password" name="password" required 
                   style="width: 100%; padding: 10px 12px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); outline: none;">
            
            <!-- Password Strength Meter -->
            <div style="margin-top: 8px; height: 4px; width: 100%; background: var(--color-border); border-radius: 2px; overflow: hidden; display: flex;">
                <div id="strength-bar" style="height: 100%; width: 0%; transition: 0.3s; background: var(--color-danger-text);"></div>
            </div>
            <div id="strength-text" style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 4px;"></div>
        </div>

        <div class="erp-form-group" style="margin-bottom: var(--spacing-xl);">
            <label style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 0.875rem;">
                <?= __('auth.confirm_password', [], $currentLocale) ?> <span style="color: red;">*</span>
            </label>
            <input type="password" name="password_confirmation" required 
                   style="width: 100%; padding: 10px 12px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); outline: none;">
        </div>

        <button type="submit" style="width: 100%; padding: 12px; background: var(--color-primary-500); color: white; border: none; border-radius: var(--radius-sm); font-size: 1rem; font-weight: 600; cursor: pointer;">
            <i class="ph ph-floppy-disk" style="font-size: 1.2rem;"></i> <?= $currentLocale === 'ar' ? 'حفظ الدخول' : 'Save & Login' ?>
        </button>
    </form>
</div>

<script>
    const passInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');

    passInput.addEventListener('input', function() {
        let val = passInput.value;
        let score = 0;
        
        if (val.length > 5) score += 25;
        if (val.match(/[a-z]/) && val.match(/[A-Z]/)) score += 25;
        if (val.match(/\d/)) score += 25;
        if (val.match(/[^a-zA-Z\d]/)) score += 25;

        strengthBar.style.width = score + '%';
        
        if (score < 50) {
            strengthBar.style.background = 'var(--color-danger-text)';
            strengthText.innerText = '<?= $currentLocale === "ar" ? "ضعيفة" : "Weak" ?>';
        } else if (score < 100) {
            strengthBar.style.background = '#F59E0B';
            strengthText.innerText = '<?= $currentLocale === "ar" ? "متوسطة" : "Medium" ?>';
        } else {
            strengthBar.style.background = 'var(--color-success-text)';
            strengthText.innerText = '<?= $currentLocale === "ar" ? "قوية" : "Strong" ?>';
        }
    });
</script>