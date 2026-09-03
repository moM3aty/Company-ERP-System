<?php
// Path: resources/views/layouts/auth.php
$currentLocale = $locale ?? 'en';
$dir = ($currentLocale === 'ar') ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLocale) ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= ($currentLocale === 'ar') ? 'تسجيل الدخول' : 'Login' ?> | NOUR TRUST ERP</title>    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- STREAMING_CHUNK: Injecting Enterprise CSS Tokens... -->
    <style>
        :root {
            /* Design Tokens */
            --color-primary-900: #0B192C;
            --color-primary-800: #112A46;
            --color-primary-700: #163B61;
            --color-primary-500: #21609A;
            --color-primary-100: #E9F0F7;
            --color-accent: #00B4D8;
            --color-background: #F4F7F9;
            --color-surface: #FFFFFF;
            --color-border: #E2E8F0;
            --color-border-hover: #CBD5E1;
            --color-text-main: #1E293B;
            --color-text-muted: #64748B;
            --color-text-inverse: #FFFFFF;
            --color-success-bg: #DCFCE7;
            --color-success-text: #166534;
            --color-warning-bg: #FEF9C3;
            --color-warning-text: #854D0E;
            --color-danger-bg: #FEE2E2;
            --color-danger-text: #991B1B;
            
            --spacing-xs: 4px; --spacing-sm: 8px; --spacing-md: 16px; --spacing-lg: 24px; --spacing-xl: 32px;
            --radius-sm: 6px; --radius-md: 10px; --radius-lg: 16px;
            --shadow-sm: 0 1px 3px rgba(11, 25, 44, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(11, 25, 44, 0.08);
            --shadow-lg: 0 10px 15px -3px rgba(11, 25, 44, 0.08);
        }

        /* STREAMING_CHUNK: Setting up Base Styles... */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html[dir="ltr"] { font-family: 'Inter', sans-serif; }
        html[dir="rtl"] { font-family: 'Cairo', sans-serif; }
        body { background-color: var(--color-background); color: var(--color-text-main); font-size: 14px; line-height: 1.5; -webkit-font-smoothing: antialiased; }
        a { text-decoration: none; color: var(--color-primary-500); transition: 0.2s; }
        
        .auth-wrapper {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--color-primary-900) 0%, var(--color-primary-700) 100%);
        }

        .auth-side-image {
            flex: 1;
            display: none;
            position: relative;
            overflow: hidden;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            color: white;
        }

        @media (min-width: 1024px) {
            .auth-side-image { display: flex; }
        }

        .auth-side-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80') center/cover;
            opacity: 0.15;
            z-index: 1;
        }

        .auth-content {
            position: relative;
            z-index: 2;
            max-width: 600px;
        }

        .auth-form-container {
            width: 100%;
            max-width: 480px;
            margin: auto;
            background: var(--color-surface);
            padding: 3rem 2rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }

        @media (min-width: 1024px) {
            .auth-form-container {
                padding: 4rem 3rem;
            }
        }

        .d-flex { display: flex; }
        .align-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-sm { gap: var(--spacing-sm); }
    </style>
</head>
<body>

    <div class="auth-wrapper">
        <!-- Visual Brand Side -->
        <div class="auth-side-image">
            <div class="auth-content">
                <div style="font-weight: 800; font-size: 2.5rem; letter-spacing: 1px; margin-bottom: 1rem;">NOUR TRUST</div>
                <div style="font-weight: 600; font-size: 1.25rem; color: var(--color-accent); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 2rem;">Enterprise PRO</div>
                <p style="font-size: 1.1rem; line-height: 1.6; opacity: 0.9; max-width: 450px;">
                    Experience the next generation of unified business management. Secure, scalable, and intelligent ERP solutions designed for enterprise excellence.
                </p>
                
                <div style="margin-top: 3rem; display: flex; gap: 1rem; opacity: 0.7;">
                    <i class="ph ph-shield-check" style="font-size: 1.5rem;"></i>
                    <i class="ph ph-cloud-check" style="font-size: 1.5rem;"></i>
                    <i class="ph ph-lightning" style="font-size: 1.5rem;"></i>
                </div>
            </div>
        </div>

        <!-- Login Form Side -->
        <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 1rem;">
            <div class="auth-form-container">
                <?= $content ?? '' ?>
            </div>
        </div>
    </div>

</body>
</html>