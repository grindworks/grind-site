<?php
if (!defined('GRINDS_APP')) exit;
$ctx = ['type' => $pageType ?? 'home', 'data' => $pageData ?? []];
$headerData = grinds_get_header_data($ctx);
extract($headerData);
?>
<!DOCTYPE html>
<html lang="<?= h($htmlLang) ?>" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($finalTitle) ?></title>
    <meta name="description" content="<?= h($finalDesc) ?>">
    <?php if ($showCanonical && $pageType !== 'search'): ?>
        <link rel="canonical" href="<?= h($canonicalUrl) ?>"><?php endif; ?>
    <link rel="icon" href="<?= h(get_favicon_url()) ?>">

    <link rel="stylesheet" href="<?= grinds_theme_asset_url('css/style.css') ?>">
    <?php grinds_head(); ?>
</head>

<body class="selection:bg-indigo-500 selection:text-white flex flex-col items-center py-12 px-4 sm:px-6 bg-slate-50 relative min-h-screen">

    <div class="fixed top-0 left-0 w-full h-full overflow-hidden -z-10 pointer-events-none">
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-indigo-200/40 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-blue-200/40 rounded-full blur-[120px]"></div>
    </div>

    <div class="w-full max-w-3xl mx-auto flex-1 z-10 relative">
        <?= $content ?>
    </div>

    <?php grinds_footer(); ?>
</body>

</html>
