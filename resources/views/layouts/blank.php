<?php
// Path: resources/views/layouts/blank.php

/**
 * @var string $locale
 * @var string $pageTitle
 * @var string $content
 */

$dir = ($locale === 'ar') ? 'rtl' : 'ltr';
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>" dir="<?= $dir ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | NOUR TRUST</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/variables.css">
    <style>
        body { font-family: var(--font-family-en); background: var(--color-background); color: var(--color-text-main); margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .error-container { text-align: center; max-width: 500px; padding: 40px; }
        .error-code { font-size: 6rem; font-weight: 800; color: var(--color-primary-900); line-height: 1; margin-bottom: 16px; }
        .error-title { font-size: 1.5rem; font-weight: 600; margin-bottom: 8px; }
        .error-desc { color: var(--color-text-muted); margin-bottom: 32px; }
        .btn-home { display: inline-flex; align-items: center; gap: 8px; background: var(--color-primary-500); color: white; padding: 12px 24px; text-decoration: none; border-radius: var(--radius-sm); font-weight: 600; transition: opacity 0.2s; }
        .btn-home:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <?= $content ?>
</body>
</html>