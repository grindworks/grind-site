<?php

if (!defined('GRINDS_APP')) exit;

/**
 * toc.php
 * Render Table of Contents.
 */

$contentData = $pageData['post']['content_decoded'] ?? [];
$headers = get_post_toc($contentData);
?>
<?php if (!empty($headers)): ?>
    <details id="toc" open class="mb-10 p-5 border border-gray-200 rounded-lg bg-gray-50 shadow-sm">
        <summary class="font-bold cursor-pointer text-gray-800 mb-3 text-lg outline-none">
            <?= h($pageData['post']['toc_title'] ?: theme_t('Contents')) ?>
        </summary>
        <nav role="navigation" aria-label="<?= theme_t('Contents') ?>">
            <ul class="space-y-2 list-none m-0 p-0">
                <?php foreach ($headers as $h): ?>
                    <?php
                    $indentClass = 'ml-0 font-bold';
                    if ($h['level'] === 3) $indentClass = 'ml-4 font-normal';
                    elseif ($h['level'] === 4) $indentClass = 'ml-8 font-normal text-sm';
                    elseif ($h['level'] >= 5) $indentClass = 'ml-12 font-normal text-xs';
                    ?>
                    <li class="<?= $indentClass ?>">
                        <a href="#<?= $h['id'] ?>" class="hover:underline hover:text-grinds-red text-gray-700 block py-0.5 transition-colors">
                            <?= h($h['text']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </details>
<?php endif; ?>
