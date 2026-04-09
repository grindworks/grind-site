<?php

declare(strict_types=1);

/**
 * Trait GeneratorCacheTrait
 * Shared caching and utility methods for LLM, RSS, and Sitemap generators.
 */
trait GeneratorCacheTrait
{
    // Force classes using this trait to implement their own sendHeaders
    abstract protected function sendHeaders(): void;

    protected function serveFromCache(): bool
    {
        if (!file_exists($this->cacheFile) || empty($this->pdo)) {
            return false;
        }

        try {
            $cacheMtime = filemtime($this->cacheFile);
            if ($cacheMtime === false) {
                return false;
            }

            $lastContentUpdate = $this->getLastContentUpdateTime();
            if ($lastContentUpdate !== null && $cacheMtime >= $lastContentUpdate) {
                $this->sendHeaders();
                readfile($this->cacheFile);
                return true;
            }
        } catch (\Throwable $e) {
            if (class_exists('GrindsLogger')) {
                GrindsLogger::log(static::class . ' Cache Error: ' . $e->getMessage(), 'WARNING');
            }
        }

        return false;
    }

    protected function getLastContentUpdateTime(): ?int
    {
        if (empty($this->pdo)) return null;

        if (!class_exists('PostRepository')) {
            require_once __DIR__ . '/functions/posts.php';
        }
        $repo = new PostRepository($this->pdo);

        // Allow each class to define target types (default: post, page)
        $types = property_exists($this, 'cachePostTypes') ? $this->cachePostTypes : ['post', 'page'];

        $latest = $repo->getLatestPostTimestamp([
            'status' => 'any',
            'type' => $types
        ]);
        return $latest ? strtotime((string)$latest) : null;
    }

    protected function sendError(int $code): void
    {
        if (empty($this->isSsgMode)) {
            http_response_code($code);
            exit;
        }
    }
}
