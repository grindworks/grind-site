<?php

declare(strict_types=1);

/**
 * Generate dynamic robots.txt for crawlers.
 */
ini_set('display_errors', '0');
error_reporting(0);

// Load bootstrap.
if (!require __DIR__ . '/lib/bootstrap_public.php') {
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo "User-agent: *\n";
    echo "Disallow: /\n";
    if (defined('GRINDS_IS_SSG')) {
        return;
    }
    exit;
}

if (!class_exists('RobotsGenerator')) {
    class RobotsGenerator
    {
        private const AI_BOTS_BLOCKLIST = [
            'GPTBot',
            'ChatGPT-User',
            'Google-Extended',
            'Claude-Bot',
            'PerplexityBot',
            'OmgiliBot',
            'FacebookBot',
            'Applebot-Extended',
            'OAI-SearchBot',
            'Amazonbot',
            'Bytespider',
            'CCBot',
            'Diffbot',
            'meta-externalagent',
            'anthropic-ai',
            'cohere-ai',
            'magpie-crawler',
            'Meltwater',
            'YouBot',
            'ImagesiftBot',
            'ISearch',
            'DataForSeoBot',
            'TurnitinBot',
            'Webz'
        ];

        private bool $isSsgMode;
        private bool $isBlockAi;
        private bool $isNoIndex;
        private string $baseUrl;
        private string $path;

        public function __construct(string $baseUrl, bool $isSsgMode = false)
        {
            $this->isSsgMode = $isSsgMode;
            $this->isNoIndex = function_exists('get_option') ? (bool)get_option('site_noindex') : false;
            $this->isBlockAi = function_exists('get_option') ? (bool)get_option('site_block_ai') : false;
            $this->baseUrl = $this->normalizeBaseUrl($baseUrl);
            $this->path = $this->extractPath($this->baseUrl);
        }

        public function run(): void
        {
            if (!$this->isSsgMode && file_exists(__DIR__ . '/.maintenance')) {
                $this->sendHeaders(503);
                return;
            }

            $content = $this->generateContent();

            if (!$this->isSsgMode) {
                while (ob_get_level()) {
                    ob_end_clean();
                }
            }
            $this->sendHeaders(200);
            echo $content;
        }

        private function normalizeBaseUrl(string $url): string
        {
            if ($this->isSsgMode && empty($url)) {
                return '';
            }
            if (empty($url) || !preg_match('/^https?:\/\//i', $url)) {
                $url = defined('BASE_URL') ? (string)BASE_URL : 'https://example.com';
            }
            return rtrim($url, '/');
        }

        private function extractPath(string $url): string
        {
            $parsed = parse_url($url);
            return (is_array($parsed) && !empty($parsed['path'])) ? rtrim($parsed['path'], '/') : '';
        }

        private function sendHeaders(int $statusCode): void
        {
            if ($this->isSsgMode || headers_sent()) {
                return;
            }

            http_response_code($statusCode);
            header('Content-Type: text/plain; charset=UTF-8');
            header("X-Content-Type-Options: nosniff");

            // Cache for 24 hours to reduce database load.
            if ($statusCode === 200) {
                header("Cache-Control: public, max-age=86400");
            }

            if ($statusCode === 503) {
                header('Retry-After: 3600');
                header("Cache-Control: no-cache, must-revalidate");
            }
        }

        private function generateContent(): string
        {
            $lines = [];
            $lines[] = "# GrindSite";

            if ($this->path !== '' && $this->path !== '/') {
                $lines[] = "# Note: If installed in a subdirectory, add 'Sitemap: {$this->baseUrl}/sitemap.xml' to your root robots.txt";
            }

            // Output sitemap directive at the top.
            if ($this->baseUrl !== '' && !$this->isNoIndex) {
                $lines[] = "Sitemap: {$this->baseUrl}/sitemap.xml";
            }

            $lines[] = "User-agent: *";
            $lines[] = "Crawl-delay: 1";

            // Block base directories while allowing crawl to read noindex tags.
            $lines[] = $this->getBaseRules();

            // Output AI crawler rules.
            $lines[] = $this->getAiCrawlerRules();

            return implode("\n", array_filter($lines)) . "\n";
        }

        private function getBaseRules(): string
        {
            $p = $this->path;
            $rules = [
                "Disallow: {$p}/admin/",
                "Disallow: {$p}/lib/",
                "Disallow: {$p}/data/",
                "Disallow: {$p}/plugins/",
                "Disallow: {$p}/*?q=*",
                "Disallow: {$p}/*?sort=*",
                "Disallow: {$p}/*?order=*",
                "Disallow: {$p}/*?limit=*"
            ];
            return implode("\n", $rules);
        }

        private function getAiCrawlerRules(): string
        {
            $lines = [];

            if ($this->isBlockAi) {
                $lines[] = "";
                $lines[] = "# AI Crawlers Policy (Opt-Out)";
                $targetPath = ($this->path !== '') ? "{$this->path}/" : "/";
                foreach (self::AI_BOTS_BLOCKLIST as $bot) {
                    $lines[] = "User-agent: {$bot}";
                    $lines[] = "Disallow: {$targetPath}";
                    $lines[] = "";
                }
            } else {
                $lines[] = "";
                $lines[] = "# AI Crawlers Policy (Transparency & Opt-In)";
                $lines[] = "# We embrace AI technologies and allow ethical crawling.";
                $llmsUrl = $this->baseUrl !== '' ? "{$this->baseUrl}/llms.txt" : "/llms.txt";
                $lines[] = "# See {$llmsUrl} for detailed crawler instructions.";
                // Prevent specification conflict: Defining explicit User-agents for allowed bots
                // causes them to ignore the global 'User-agent: *' rules above.
                // To safely enforce base directory restrictions (/admin/, etc.) on AI crawlers,
                // we omit explicit Allow/Disallow rules here and let them follow 'User-agent: *'.
            }
            return implode("\n", $lines);
        }
    }
}

// Execute generator.
$isSsgMode = defined('GRINDS_IS_SSG') && GRINDS_IS_SSG;
$baseUrl = defined('BASE_URL') ? (string)BASE_URL : '';

if ($isSsgMode && isset($ssgBaseUrl) && $ssgBaseUrl !== '') {
    $baseUrl = (string)$ssgBaseUrl;
}

(new RobotsGenerator($baseUrl, $isSsgMode))->run();
