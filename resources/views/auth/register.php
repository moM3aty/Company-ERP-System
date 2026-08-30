<?php
// Path: resources/views/auth/register.php

if (session_status() === PHP_SESSION_NONE) session_start();
$currentLocale = $locale ?? $_SESSION['locale'] ?? 'ar';
?>
<div style="text-align: start;">
    <div style="text-align: center; margin-bottom: var(--spacing-xl);">
        <h2 style="color: var(--color-primary-900); font-weight: 700; margin-bottom: 8px; font-size: 1.5rem;">Create an Account</h2>
        <p style="color: var(--color-text-muted); font-size: 0.875rem;">Join NOUR TRUST ERP Enterprise System.</p>
    </div>

    <form action="/ERP/register" method="POST">
        
        <?php 
        $name = 'name'; $label = 'Full Name'; $type = 'text'; $placeholder = 'e.g. Ahmed Ibrahim'; $required = true;
        include __DIR__ . '/../components/input.php';

        $name = 'email'; $label = 'Email Address'; $type = 'email'; $placeholder = 'name@company.com'; $required = true;
        include __DIR__ . '/../components/input.php';

        $name = 'password'; $label = 'Password'; $type = 'password'; $placeholder = '••••••••'; $required = true;
        include __DIR__ . '/../components/input.php';

        $name = 'password_confirmation'; $label = 'Confirm Password'; $type = 'password'; $placeholder = '••••••••'; $required = true;
        include __DIR__ . '/../components/input.php';
        ?>

        <div class="erp-form-group" style="margin-bottom: var(--spacing-lg);">
            <label class="d-flex align-start gap-sm" style="cursor: pointer;">
                <input type="checkbox" name="terms" required style="accent-color: var(--color-primary-500); margin-top: 4px;">
                <span style="color: var(--color-text-main); font-size: 0.875rem;">I agree to the <a href="#" style="color: var(--color-primary-500);">Terms of Service</a> and <a href="#" style="color: var(--color-primary-500);">Privacy Policy</a>.</span>
            </label>
        </div>

        <div style="margin-top: var(--spacing-lg);">
            <?php 
            $text = 'Register'; $type = 'submit'; $class = 'w-100';
            include __DIR__ . '/../components/button.php';
            ?>
        </div>
    </form>
    
    <div style="margin-top: var(--spacing-xl); text-align: center; border-top: 1px solid var(--color-border); padding-top: var(--spacing-md);">
        <p style="color: var(--color-text-muted); font-size: 0.875rem;">
            Already have an account? <a href="/ERP/login" style="color: var(--color-primary-500); font-weight: 600; text-decoration: none;">Log In</a>
        </p>
    </div>
</div>