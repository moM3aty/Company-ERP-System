<?php
// Path: resources/views/auth/reset-password.php

if (session_status() === PHP_SESSION_NONE) session_start();
$currentLocale = $locale ?? $_SESSION['locale'] ?? 'ar';
$isAr = ($currentLocale === 'ar');
?>

<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<style>
    .nt-erp-fixed-wrap { position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; display: flex; background: #ffffff; z-index: 99999; direction: <?= $isAr ? 'rtl' : 'ltr' ?>; font-family: <?= $isAr ? "'Cairo', sans-serif" : "'Plus Jakarta Sans', sans-serif" ?>; box-sizing: border-box; overflow: hidden; }
    .nt-erp-fixed-wrap * { box-sizing: border-box; }
    
    .nt-visual-side { flex: 1; position: relative; display: flex; flex-direction: column; justify-content: flex-end; padding: 4rem; background-image: url('https://images.unsplash.com/photo-1555949963-aa79dcee981c?q=80&w=2000&auto=format&fit=crop'); background-size: cover; background-position: center; background-repeat: no-repeat; }
    .nt-visual-overlay { position: absolute; inset: 0; background: linear-gradient(135deg, rgba(10, 17, 40, 0.9) 0%, rgba(29, 78, 216, 0.6) 100%); z-index: 1; }
    .nt-visual-content { position: relative; z-index: 2; color: white; max-width: 600px; animation: slideUp 1s ease forwards; }
    .nt-visual-title { font-size: 3.2rem; font-weight: 900; line-height: 1.2; margin: 0 0 1rem 0; letter-spacing: -0.02em; }
    .nt-visual-desc { font-size: 1.15rem; font-weight: 500; line-height: 1.6; color: rgba(255,255,255,0.85); margin: 0; }
    
    .nt-form-side { width: 500px; flex-shrink: 0; background: #ffffff; display: flex; flex-direction: column; justify-content: center; padding: 3rem 4rem; position: relative; z-index: 10; box-shadow: <?= $isAr ? '-20px' : '20px' ?> 0 40px rgba(0,0,0,0.05); overflow-y: auto; text-align: start; }
    
    .nt-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 3rem; text-decoration: none; }
    .nt-logo img { height: 45px; object-fit: contain; }
    .nt-logo-text { font-size: 1.4rem; font-weight: 900; color: #0A1128; letter-spacing: -0.02em; line-height: 1; margin:0;}
    .nt-logo-sub { font-size: 0.7rem; font-weight: 800; color: #1D4ED8; letter-spacing: 0.1em; text-transform: uppercase; margin: 4px 0 0 0; }

    .nt-header { margin-bottom: 2.5rem; }
    .nt-header h2 { font-size: 1.8rem; font-weight: 800; color: #0F172A; margin: 0 0 8px 0; }
    .nt-header p { font-size: 0.95rem; color: #64748B; font-weight: 500; margin: 0; line-height: 1.6; }

    .nt-form-group { margin-bottom: 1.5rem; }
    .nt-label { display: block; font-size: 0.9rem; font-weight: 700; color: #334155; margin-bottom: 8px; }
    .nt-input { width: 100%; padding: 14px 16px; font-size: 1rem; font-family: inherit; font-weight: 600; color: #0F172A; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; transition: all 0.3s ease; outline: none; }
    .nt-input:focus { border-color: #1D4ED8; background: #ffffff; box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.1); }
    
    .nt-btn { width: 100%; padding: 14px; font-size: 1.05rem; font-weight: 800; color: white; background: #1D4ED8; border: none; border-radius: 12px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 2rem; }
    .nt-btn:hover { background: #0A1128; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(10, 17, 40, 0.15); }

    @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 992px) { .nt-visual-side { display: none; } .nt-form-side { width: 100%; padding: 2rem; box-shadow: none; justify-content: flex-start; padding-top: 4rem; } }
</style>

<div class="nt-erp-fixed-wrap">
    <div class="nt-visual-side">
        <div class="nt-visual-overlay"></div>
        <div class="nt-visual-content">
            <h1 class="nt-visual-title"><?= $isAr ? 'أمان بلا تنازلات' : 'Security Without Compromise' ?></h1>
            <p class="nt-visual-desc">
                <?= $isAr ? 'نلتزم بأعلى معايير الأمان العالمية للحفاظ على سرية عملياتك وبياناتك الحساسة داخل نظام ERP.' : 'We adhere to the highest global security standards to maintain the confidentiality of your operations and sensitive data.' ?>
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
            <h2><?= __('auth.new_password', [], $currentLocale) ?></h2>
            <p><?= $isAr ? 'يرجى إدخال كلمة المرور الجديدة. تأكد من أنها قوية لحماية حسابك المؤسسي.' : 'Please enter your new password. Ensure it is strong to protect your enterprise account.' ?></p>
        </div>

        <form action="/ERP/reset-password" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
            
            <div class="nt-form-group">
                <label class="nt-label"><?= __('auth.new_password', [], $currentLocale) ?> <span style="color:#dc2626">*</span></label>
                <input type="password" id="new_password" name="password" class="nt-input" required placeholder="••••••••">
                
                <div style="margin-top: 12px; height: 6px; width: 100%; background: #E2E8F0; border-radius: 10px; overflow: hidden; display: flex;">
                    <div id="strength-bar" style="height: 100%; width: 0%; transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1), background 0.5s; background: #dc2626;"></div>
                </div>
                <div id="strength-text" style="font-size: 0.85rem; font-weight: 800; color: #64748B; margin-top: 8px;"></div>
            </div>

            <div class="nt-form-group" style="margin-bottom: 24px;">
                <label class="nt-label"><?= __('auth.confirm_password', [], $currentLocale) ?> <span style="color:#dc2626">*</span></label>
                <input type="password" name="password_confirmation" class="nt-input" required placeholder="••••••••">
            </div>

            <button type="submit" class="nt-btn">
                <i class="ph-bold ph-shield-check" style="font-size: 1.3rem;"></i> 
                <?= $isAr ? 'توثيق الدخول الجديد' : 'Secure Login' ?>
            </button>
        </form>
    </div>
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
            strengthBar.style.background = '#dc2626';
            strengthText.innerText = '<?= $isAr ? "ضعيفة - يرجى زيادة التعقيد" : "Weak - Increase complexity" ?>';
            strengthText.style.color = '#dc2626';
        } else if (score < 100) {
            strengthBar.style.background = '#F59E0B';
            strengthText.innerText = '<?= $isAr ? "متوسطة - جيدة للعمل" : "Medium - Good enough" ?>';
            strengthText.style.color = '#F59E0B';
        } else {
            strengthBar.style.background = '#10B981';
            strengthText.innerText = '<?= $isAr ? "قوية جداً ومحمية بالكامل" : "Strong & Fully Secured" ?>';
            strengthText.style.color = '#10B981';
        }
    });
</script>