<?php
if (!defined('GRINDS_APP')) exit;
$ctx = ['type' => $pageType ?? 'home', 'data' => $pageData ?? []];
$headerData = grinds_get_header_data($ctx);
extract($headerData);

$sidebarTree = nexus_get_sidebar_tree();
$currentSlug = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

// Generate search index for autocomplete
$searchIndex = [];
foreach ($sidebarTree as $group) {
    foreach ($group['posts'] as $p) {
        $searchIndex[] = [
            'title' => $p['title'],
            'url' => resolve_url($p['slug']),
            'category' => $group['category']['name']
        ];
    }
}
$searchIndexJson = json_encode($searchIndex, JSON_UNESCAPED_UNICODE);
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

    <link rel="stylesheet" href="<?= grinds_asset_url('theme/nexus-docs/css/style.css') ?>">
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <?php grinds_head(); ?>
</head>

<body class="bg-zinc-50 text-zinc-900 flex flex-col min-h-screen" x-data="{
    mobileMenuOpen: false,
    searchOpen: false,
    searchQuery: '',
    searchIndex: <?= h($searchIndexJson) ?>,
    get searchResults() {
        if (this.searchQuery.trim() === '') return [];
        let q = this.searchQuery.toLowerCase();
        return this.searchIndex.filter(item => item.title.toLowerCase().includes(q)).slice(0, 8);
    }
}">

    <!-- Global header -->
    <header class="sticky top-0 z-40 w-full backdrop-blur flex-none transition-colors duration-500 lg:z-50 lg:border-b lg:border-zinc-900/10 bg-white/95">
        <div class="max-w-8xl mx-auto">
            <div class="py-4 border-b border-zinc-900/10 lg:px-8 lg:border-0 px-4 sm:px-6 flex items-center justify-between">
                <a href="<?= h(resolve_url('/')) ?>" class="flex items-center gap-2 font-bold text-xl tracking-tight text-zinc-900">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
                        </svg>
                    </div>
                    <?= h(get_option('site_name', CMS_NAME)) ?> <span class="text-xs text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-100">Docs</span>
                </a>

                <div class="flex items-center gap-4">
                    <!-- Search button -->
                    <button @click="searchOpen = true; $nextTick(() => $refs.searchInput.focus())" class="hidden sm:flex items-center gap-2 w-64 px-3 py-1.5 bg-zinc-100 hover:bg-zinc-200 text-zinc-500 rounded-lg text-sm transition-colors ring-1 ring-zinc-900/5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
                        </svg>
                        Search docs...
                        <span class="ml-auto text-xs font-semibold">⌘K</span>
                    </button>
                    <!-- Mobile search button -->
                    <button @click="searchOpen = true" class="sm:hidden p-2 text-zinc-500 hover:text-zinc-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
                        </svg>
                    </button>
                    <!-- Mobile menu toggle -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 text-zinc-500 hover:text-zinc-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-bars-3"></use>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-8xl mx-auto w-full flex-1 flex items-start">

        <!-- Left navigation sidebar -->
        <aside :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed inset-y-0 left-0 z-30 w-72 bg-white lg:bg-transparent lg:sticky lg:block transition-transform duration-300 lg:w-72 lg:shrink-0 lg:border-r border-zinc-200 overflow-y-auto custom-scrollbar pt-20 lg:pt-8 pb-10 px-6 shadow-2xl lg:shadow-none h-screen lg:h-[calc(100vh-4rem)] lg:top-16">
            <button @click="mobileMenuOpen = false" class="lg:hidden absolute top-4 right-4 p-2 text-zinc-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
                </svg>
            </button>
            <nav>
                <ul class="space-y-6 text-sm">
                    <?php foreach ($sidebarTree as $group): ?>
                        <li>
                            <h2 class="font-semibold text-zinc-900 mb-3"><?= h($group['category']['name']) ?></h2>
                            <ul class="space-y-2 border-l border-zinc-200 ml-2 pl-4">
                                <?php foreach ($group['posts'] as $p):
                                    $pUrl = resolve_url($p['slug']);
                                    $isActive = str_ends_with($currentSlug, $p['slug']);
                                ?>
                                    <li>
                                        <a href="<?= h($pUrl) ?>" class="block transition-colors <?= $isActive ? 'text-indigo-600 font-semibold' : 'text-zinc-500 hover:text-zinc-900' ?>">
                                            <?= h($p['title']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>

        <!-- Mobile overlay -->
        <div x-show="mobileMenuOpen" @click="mobileMenuOpen = false" class="fixed inset-0 z-20 bg-zinc-900/50 backdrop-blur-sm lg:hidden" x-cloak></div>

        <!-- Main content area -->
        <main class="flex-1 min-w-0 px-4 sm:px-6 lg:px-8 py-10 lg:py-12">
            <?= $content ?>
        </main>

    </div>

    <!-- Search modal -->
    <div x-show="searchOpen" class="relative z-50" role="dialog" aria-modal="true" x-cloak @keydown.window.prevent.cmd.k="searchOpen = true; $nextTick(() => $refs.searchInput.focus())" @keydown.window.prevent.ctrl.k="searchOpen = true; $nextTick(() => $refs.searchInput.focus())" @keydown.window.escape="searchOpen = false; searchQuery = ''">
        <div class="fixed inset-0 bg-zinc-900/50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto p-4 sm:p-6 md:p-20" @click.self="searchOpen = false; searchQuery = ''">
            <div class="mx-auto max-w-2xl transform divide-y divide-zinc-100 overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black ring-opacity-5 transition-all">
                <form action="<?= h(resolve_url('/search')) ?>" method="get" class="relative border-b border-zinc-100">
                    <svg class="pointer-events-none absolute top-3.5 left-4 h-5 w-5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
                    </svg>
                    <input type="text" name="q" x-ref="searchInput" x-model="searchQuery" class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-zinc-900 placeholder:text-zinc-400 focus:ring-0 sm:text-sm outline-none" placeholder="Search documentation..." autocomplete="off">
                </form>

                <!-- Search results -->
                <ul x-show="searchResults.length > 0" class="max-h-72 scroll-py-2 overflow-y-auto py-2 text-sm text-zinc-800">
                    <template x-for="result in searchResults" :key="result.url">
                        <li>
                            <a :href="result.url" class="group flex items-center cursor-pointer select-none rounded-md px-4 py-2 hover:bg-indigo-600 hover:text-white transition-colors mx-2">
                                <svg class="h-4 w-4 flex-none opacity-50 mr-3 text-zinc-400 group-hover:text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
                                </svg>
                                <span class="flex-auto truncate" x-text="result.title"></span>
                                <span class="ml-3 flex-none text-xs text-zinc-400 group-hover:text-indigo-200" x-text="result.category"></span>
                            </a>
                        </li>
                    </template>
                </ul>
                <div x-show="searchQuery !== '' && searchResults.length === 0" class="p-6 text-center text-sm text-zinc-500">
                    No results found for "<span x-text="searchQuery" class="text-zinc-900 font-semibold"></span>".
                </div>
            </div>
        </div>
    </div>

    <?php grinds_footer(); ?>
</body>

</html>
