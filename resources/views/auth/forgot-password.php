<?php
// Path: resources/views/auth/forgot-password.php

if (session_status() === PHP_SESSION_NONE) session_start();
$currentLocale = $locale ?? $_SESSION['locale'] ?? 'ar';
$successMessage = $successMessage ?? false;
?>
<div style="text-align: start;">
    <div style="text-align: center; margin-bottom: var(--spacing-xl);">
        <div style="width: 56px; height: 56px; background: var(--color-primary-100); color: var(--color-primary-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <i class="ph ph-key" style="font-size: 1.8rem;"></i>
        </div>
        <h2 style="color: var(--color-primary-900); font-weight: 700; margin-bottom: 8px; font-size: 1.5rem;"><?= __('auth.reset_password', [], $currentLocale) ?></h2>
        <p style="color: var(--color-text-muted); font-size: 0.875rem;">
            <?= $currentLocale === 'ar' ? 'أدخل بريدك الإلكتروني المؤسسي وسنرسل لك رابطاً مشفراً لاستعادة حسابك.' : 'Enter your enterprise email and we will send you a secure link to reset your password.' ?>
        </p>
    </div>

    <?php if ($successMessage): ?>
        <div style="background: var(--color-success-bg); color: var(--color-success-text); padding: 16px; border-radius: var(--radius-sm); margin-bottom: 24px; font-size: 0.875rem; text-align: center; line-height: 1.5;">
            <i class="ph ph-check-circle" style="font-size: 2rem; margin-bottom: 8px;"></i><br>
            <?= $currentLocale === 'ar' ? 'إذا كان البريد الإلكتروني مسجلاً لدينا، فستتلقى رابط الاستعادة خلال دقائق.' : 'If the email exists in our system, you will receive a reset link shortly.' ?>
        </div>
    <?php else: ?>
        <form action="/ERP/forgot-password" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <div class="erp-form-group" style="margin-bottom: var(--spacing-lg);">
                <label style="display: block; margin-bottom: 4px; font-weight: 500; color: var(--color-text-main); font-size: 0.875rem;">
                    <?= __('auth.email_address', [], $currentLocale) ?> <span style="color: red;">*</span>
                </label>
                <input type="email" name="email" required placeholder="name@nourtrust.com" 
                       style="width: 100%; padding: 10px 12px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-family: inherit; font-size: 0.875rem; background: var(--color-surface); outline: none;">
            </div>

            <button type="submit" style="width: 100%; padding: 12px; background: var(--color-primary-500); color: white; border: none; border-radius: var(--radius-sm); font-size: 1rem; font-weight: 600; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.2s;">
                <i class="ph ph-paper-plane-tilt" style="font-size: 1.2rem;"></i>
                <?= __('auth.send_link', [], $currentLocale) ?>
            </button>
        </form>
    <?php endif; ?>
    
    <div style="margin-top: var(--spacing-xl); text-align: center;">
        <a href="/ERP/login" style="color: var(--color-text-muted); text-decoration: none; font-size: 0.875rem; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
            <i class="ph ph-arrow-left" style="font-size: 1rem;"></i> <?= __('auth.back_to_login', [], $currentLocale) ?>
        </a>
    </div>
</div>