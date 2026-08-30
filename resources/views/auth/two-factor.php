<?php
// Path: resources/views/auth/two-factor.php

if (session_status() === PHP_SESSION_NONE) session_start();
$currentLocale = $locale ?? $_SESSION['locale'] ?? 'ar';
?>
<div style="text-align: start;">
    <div style="text-align: center; margin-bottom: var(--spacing-xl);">
        <div style="width: 56px; height: 56px; background: var(--color-primary-100); color: var(--color-primary-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <i class="ph ph-shield-check" style="font-size: 1.8rem;"></i>
        </div>
        <h2 style="color: var(--color-primary-900); font-weight: 700; margin-bottom: 8px; font-size: 1.5rem;"><?= __('auth.verify_2fa', [], $currentLocale) ?></h2>
        <p id="instruction-text" style="color: var(--color-text-muted); font-size: 0.875rem;">
            <?= $currentLocale === 'ar' ? 'لقد أرسلنا كود التحقق إلى جهازك. يرجى إدخاله للمتابعة.' : 'We have sent a verification code to your device. Please enter it to continue.' ?>
        </p>
    </div>

    <!-- OTP Form -->
    <form id="otp-form" action="/ERP/2fa/verify" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="type" value="otp">
        
        <div class="erp-form-group" style="margin-bottom: var(--spacing-lg);">
            <input type="text" name="code" required placeholder="123456" maxlength="6" autocomplete="off"
                   style="width: 100%; padding: 12px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-family: monospace; font-size: 1.5rem; text-align: center; letter-spacing: 10px; background: var(--color-surface); outline: none;">
        </div>

        <button type="submit" style="width: 100%; padding: 12px; background: var(--color-primary-500); color: white; border: none; border-radius: var(--radius-sm); font-size: 1rem; font-weight: 600; cursor: pointer;">
            <?= __('auth.verify', [], $currentLocale) ?>
        </button>
    </form>

    <!-- Recovery Code Form -->
    <form id="recovery-form" action="/ERP/2fa/verify" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="type" value="recovery_code">
        
        <div class="erp-form-group" style="margin-bottom: var(--spacing-lg);">
            <input type="text" name="code" required placeholder="RC-XXXXX-XXXXX" autocomplete="off"
                   style="width: 100%; padding: 12px; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-family: monospace; font-size: 1.1rem; text-align: center; letter-spacing: 2px; background: var(--color-surface); outline: none;">
        </div>

        <button type="submit" style="width: 100%; padding: 12px; background: var(--color-primary-900); color: white; border: none; border-radius: var(--radius-sm); font-size: 1rem; font-weight: 600; cursor: pointer;">
            <?= $currentLocale === 'ar' ? 'تحقق باستخدام كود الاسترداد' : 'Verify with Recovery Code' ?>
        </button>
    </form>
    
    <div style="margin-top: var(--spacing-lg); text-align: center; display: flex; flex-direction: column; gap: 8px;">
        <form action="/ERP/2fa/resend" method="POST" id="resend-form" style="display:inline;">
            <button type="submit" id="resend-btn" disabled style="background: none; border: none; color: var(--color-text-muted); font-weight: 500; cursor: not-allowed; font-size: 0.875rem; text-decoration: underline;">
                <?= $currentLocale === 'ar' ? 'إعادة إرسال الكود بعد' : 'Resend code in' ?> <span id="timer">02:00</span>
            </button>
        </form>

        <button type="button" onclick="toggleForms()" style="background: none; border: none; color: var(--color-primary-500); font-weight: 500; cursor: pointer; font-size: 0.875rem;">
            <?= $currentLocale === 'ar' ? 'فقدت الوصول لهاتفك؟ استخدم كود استرداد' : 'Lost access to your phone? Use a recovery code' ?>
        </button>
    </div>
</div>

<script>
    function toggleForms() {
        const otpForm = document.getElementById('otp-form');
        const recoveryForm = document.getElementById('recovery-form');
        const resendForm = document.getElementById('resend-form');
        const instructionText = document.getElementById('instruction-text');

        if (otpForm.style.display === 'none') {
            otpForm.style.display = 'block';
            resendForm.style.display = 'block';
            recoveryForm.style.display = 'none';
            instructionText.innerText = '<?= $currentLocale === "ar" ? "لقد أرسلنا كود التحقق إلى جهازك. يرجى إدخاله للمتابعة." : "We have sent a verification code to your device. Please enter it to continue." ?>';
        } else {
            otpForm.style.display = 'none';
            resendForm.style.display = 'none';
            recoveryForm.style.display = 'block';
            instructionText.innerText = '<?= $currentLocale === "ar" ? "أدخل أحد أكواد الاسترداد الآمنة التي قمت بحفظها مسبقاً." : "Enter one of your emergency recovery codes." ?>';
        }
    }

    let timeLeft = 120;
    const timerElement = document.getElementById('timer');
    const resendBtn = document.getElementById('resend-btn');

    const countdown = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(countdown);
            resendBtn.disabled = false;
            resendBtn.style.color = 'var(--color-primary-500)';
            resendBtn.style.cursor = 'pointer';
            timerElement.innerHTML = '';
            resendBtn.innerHTML = '<?= $currentLocale === "ar" ? "إعادة إرسال الكود" : "Resend Code" ?>';
        } else {
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            timerElement.innerHTML = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        timeLeft -= 1;
    }, 1000);
</script>