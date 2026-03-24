<?php
if (!defined('GRINDS_APP')) exit;
$post = $pageData['post'];
$contentData = $post['content_decoded'] ?? json_decode($post['content'] ?? '{}', true);
$headers = get_post_toc($contentData); // Extract H2 and H3 for right TOC
?>
<div class="flex lg:gap-10 xl:gap-16">

    <!-- Main article content -->
    <div class="flex-1 min-w-0">
        <header class="mb-10">
            <p class="text-sm font-semibold text-indigo-600 mb-2">
                <?= h($post['category_name'] ?? 'Documentation') ?>
            </p>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-zinc-900 tracking-tight mb-4">
                <?= h($post['title']) ?>
            </h1>
            <?php if (!empty($post['description'])): ?>
                <p class="text-lg text-zinc-600 leading-relaxed">
                    <?= h($post['description']) ?>
                </p>
            <?php endif; ?>
        </header>

        <div class="prose prose-zinc prose-indigo max-w-none">
            <?= render_content($contentData) ?>
        </div>

        <footer class="mt-16 pt-8 border-t border-zinc-200 flex justify-between items-center text-sm text-zinc-500">
            <div>
                Last updated on <?= date('F j, Y', strtotime($post['updated_at'] ?? $post['created_at'])) ?>
            </div>
            <?php if (isset($_SESSION['admin_logged_in'])): ?>
                <a href="<?= h(resolve_url('admin/posts.php?action=edit&id=' . $post['id'])) ?>" class="font-semibold text-indigo-600 hover:underline">Edit this page</a>
            <?php endif; ?>
        </footer>
    </div>

    <!-- Right TOC pane -->
    <?php if (!empty($headers)): ?>
        <div class="hidden xl:block w-64 shrink-0">
            <nav class="sticky top-24 max-h-[calc(100vh-6rem)] overflow-y-auto custom-scrollbar">
                <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">On this page</h4>
                <ul class="space-y-2.5 text-sm text-zinc-500 border-l border-zinc-200 pl-4">
                    <?php foreach ($headers as $h):
                        $indentClass = ($h['level'] === 3) ? 'ml-3' : '';
                    ?>
                        <li class="<?= $indentClass ?>">
                            <a href="#<?= $h['id'] ?>" class="block hover:text-indigo-600 transition-colors line-clamp-2 leading-snug">
                                <?= h($h['text']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>

</div>
