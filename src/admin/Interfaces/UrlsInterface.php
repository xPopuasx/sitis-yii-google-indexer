<?php

namespace sitis\seo\google\indexer\admin\Interfaces;

interface UrlsInterface
{
    /** Получение разности ссылок между новым sitemap и старым sitemap */
    public function getUpdatedUrls(string $key, $query, callable $callback): array;

    /** Получение разности ссылок между новым sitemap и старым sitemap */
    public function getDeletedUrls(string $key, $query, callable $callback): array;
}