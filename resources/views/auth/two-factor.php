<?php
// Path: resources/views/auth/two-factor.php

if (session_status() === PHP_SESSION_NONE) session_start();
$currentLocale = $locale ?? $_SESSION['locale'] ?? 'ar';
$isAr = ($currentLocale === 'ar');
?>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
    .nt-erp-fixed-wrap { position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; display: flex; background: #ffffff; z-index: 99999; direction: <?= $isAr ? 'rtl' : 'ltr' ?>; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Plus Jakarta Sans', sans-serif" ?>; box-sizing: border-box; overflow: hidden; }
    .nt-erp-fixed-wrap * { box-sizing: border-box; }
    
    .nt-visual-side { flex: 1; position: relative; display: flex; flex-direction: column; justify-content: flex-end; padding: 4rem; background-image: url('https://images.unsplash.com/photo-1563986768494-4dee2763ff0f?q=80&w=2000&auto=format&fit=crop'); background-size: cover; background-position: center; background-repeat: no-repeat; }
    .nt-visual-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(10, 17, 40, 0.9) 0%, rgba(16, 185, 129, 0.6) 100%); z-index: 1; }
    .nt-visual-content { position: relative; z-index: 2; color: white; max-width: 600px; animation: slideUp 1s ease forwards; }
    .nt-visual-title { font-size: 3.2rem; font-weight: 900; line-height: 1.2; margin: 0 0 1rem 0; letter-spacing: -0.02em; }
    .nt-visual-desc { font-size: 1.15rem; font-weight: 500; line-height: 1.6; color: rgba(255,255,255,0.85); margin: 0; }
    
    .nt-form-side { width: 500px; flex-shrink: 0; background: #ffffff; display: flex; flex-direction: column; justify-content: center; padding: 3rem 4rem; position: relative; z-index: 10; box-shadow: <?= $isAr ? '-20px' : '20px' ?> 0 40px rgba(0,0,0,0.05); overflow-y: auto; text-align: start; }
    
    .nt-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 3rem; text-decoration: none; }
    .nt-logo img { height: 45px; object-fit: contain; }
    .nt-logo-text { font-size: 1.4rem; font-weight: 900; color: #0A1128; letter-spacing: -0.02em; line-height: 1; margin:0;}
    .nt-logo-sub { font-size: 0.7rem; font-weight: 800; color: #10B981; letter-spacing: 0.1em; text-transform: uppercase; margin: 4px 0 0 0; }

    .nt-header { margin-bottom: 2.5rem; }
    .nt-header h2 { font-size: 1.8rem; font-weight: 800; color: #0F172A; margin: 0 0 8px 0; }
    .nt-header p { font-size: 0.95rem; color: #64748B; font-weight: 500; margin: 0; line-height: 1.6; }

    .nt-input-code { width: 100%; height: 4.5rem; border: 2px solid #E2E8F0; border-radius: 12px; font-family: monospace; font-size: 2rem; text-align: center; letter-spacing: 12px; background: #F8FAFC; color: #0F172A; transition: all 0.3s ease; outline: none; font-weight: 900; box-sizing: border-box; }
    .nt-input-code:focus { border-color: #10B981; background: #ffffff; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.15); transform: translateY(-2px); }
    .nt-input-rec { letter-spacing: 4px; font-size: 1.2rem; }
    
    .nt-btn { width: 100%; padding: 14px; background: #10B981; color: white; border: none; border-radius: 12px; font-size: 1.05rem; font-weight: 800; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.3s; margin-top: 1rem; }
    .nt-btn:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2); }
    .nt-btn-alt { background: #0A1128; }
    .nt-btn-alt:hover { background: #1D4ED8; box-shadow: 0 10px 20px rgba(29, 78, 216, 0.2); }
    
    .nt-toggle-btn { background: none; border: none; color: #1D4ED8; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: 0.3s; padding: 10px; width: 100%; border-radius: 8px; margin-top: 10px;}
    .nt-toggle-btn:hover { background: #F1F5F9; color: #0A1128; }

    @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 992px) { .nt-visual-side { display: none; } .nt-form-side { width: 100%; padding: 2rem; box-shadow: none; justify-content: flex-start; padding-top: 4rem; } }
</style>

<div class="nt-erp-fixed-wrap">
    
    <div class="nt-visual-side">
        <div class="nt-visual-overlay"></div>
        <div class="nt-visual-content">
            <h1 class="nt-visual-title"><?= $isAr ? 'طبقة حماية إضافية' : 'Extra Layer of Security' ?></h1>
            <p class="nt-visual-desc">
                <?= $isAr ? 'المصادقة الثنائية تضمن بقاء نظام شركتك محصناً ضد محاولات الاختراق، لتنعم براحة البال الكاملة.' : 'Two-factor authentication ensures your enterprise system remains fortified against unauthorized access, giving you complete peace of mind.' ?>
            </p>
        </div>
    </div>

    <div class="nt-form-side">
        <a href="https://nourtrust.com" class="nt-logo">
            <img src="https://nourtrust.com/images/logo.png" alt="Nour Trust Logo" onerror="this.src='/images/logo.png'">
            <div>
                <div class="nt-logo-text">Nour Trust</div>
                <div class="nt-logo-sub">Enterprise System</div>
            </div>
        </a>

        <div class="nt-header">
            <h2><?= __('auth.verify_2fa', [], $currentLocale) ?></h2>
            <p id="instruction-text">
                <?= $isAr ? 'لقد أرسلنا كود التحقق الآمن إلى جهازك. يرجى إدخاله للمتابعة.' : 'We have sent a secure verification code to your device. Please enter it to continue.' ?>
            </p>
        </div>

        <!-- OTP Form -->
        <form id="otp-form" action="/ERP/2fa/verify" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="type" value="otp">
            
            <div style="margin-bottom: 2rem; direction: ltr;">
                <input type="text" name="code" class="nt-input-code" required placeholder="------" maxlength="6" autocomplete="off" autofocus>
            </div>

            <button type="submit" class="nt-btn">
                <i class="ph-bold ph-fingerprint" style="font-size: 1.4rem;"></i>
                <?= __('auth.verify', [], $currentLocale) ?>
            </button>
        </form>

        <!-- Recovery Code Form -->
        <form id="recovery-form" action="/ERP/2fa/verify" method="POST" style="display: none;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="type" value="recovery_code">
            
            <div style="margin-bottom: 2rem; direction: ltr;">
                <input type="text" name="code" class="nt-input-code nt-input-rec" required placeholder="RC-XXXXX-XXXXX" autocomplete="off">
            </div>

            <button type="submit" class="nt-btn nt-btn-alt">
                <i class="ph-bold ph-lifebuoy" style="font-size: 1.4rem;"></i>
                <?= $isAr ? 'تحقق باستخدام كود الاسترداد' : 'Verify with Recovery Code' ?>
            </button>
        </form>
        
        <div style="margin-top: 2rem; text-align: center; border-top: 1px solid #E2E8F0; padding-top: 1.5rem;">
            <form action="/ERP/2fa/resend" method="POST" id="resend-form" style="display:block; margin-bottom: 8px;">
                <button type="submit" id="resend-btn" disabled style="background: none; border: none; color: #94A3B8; font-weight: 800; cursor: not-allowed; font-size: 0.95rem; transition: 0.3s; width: 100%;">
                    <i class="ph-bold ph-clock-counter-clockwise"></i> 
                    <?= $isAr ? 'إعادة إرسال الكود بعد' : 'Resend code in' ?> <span id="timer" style="color: #dc2626;">02:00</span>
                </button>
            </form>

            <button type="button" class="nt-toggle-btn" onclick="toggleForms()">
                <?= $isAr ? 'فقدت الوصول لهاتفك؟ استخدم كود الطوارئ' : 'Lost access to your phone? Use emergency code' ?>
            </button>
        </div>
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
            instructionText.innerText = '<?= $isAr ? "لقد أرسلنا كود التحقق الآمن إلى جهازك. يرجى إدخاله للمتابعة." : "We have sent a secure verification code to your device. Please enter it to continue." ?>';
        } else {
            otpForm.style.display = 'none';
            resendForm.style.display = 'none';
            recoveryForm.style.display = 'block';
            instructionText.innerText = '<?= $isAr ? "أدخل أحد أكواد الاسترداد الآمنة التي قمت بحفظها مسبقاً لاسترجاع حسابك." : "Enter one of your secure emergency recovery codes to regain access." ?>';
        }
    }

    let timeLeft = 120;
    const timerElement = document.getElementById('timer');
    const resendBtn = document.getElementById('resend-btn');

    const countdown = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(countdown);
            resendBtn.disabled = false;
            resendBtn.style.color = '#1D4ED8';
            resendBtn.style.cursor = 'pointer';
            resendBtn.innerHTML = '<i class="ph-bold ph-paper-plane-right"></i> <?= $isAr ? "إعادة إرسال الكود" : "Resend Code" ?>';
        } else {
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            timerElement.innerHTML = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        timeLeft -= 1;
    }, 1000);
</script>