<?php

namespace sitis\seo\google\indexer\admin\services;

use sitis\seo\google\indexer\admin\Interfaces\UrlsInterface;
use sitis\seo\google\indexer\admin\std\GoogleIndexerMethodEnum;

class UrlsService implements UrlsInterface
{
    public function getUpdatedUrls(string $key, $query, callable $callback): array
    {
        /** TODO: тут надо запоминать отправленные items миграция google_index_items (morph_type, morph_id, google_type)*/

        $urls = [];

        foreach ($this->getChunksRange($query->count(), 1000) as $chunk){
            $modificationQuery = array_map($callback, $query->limit(1000)->offset($chunk * 1000)->all());

            foreach ($modificationQuery as $item){
                $urls[$chunk][] = [
                    'url' => $item->location,
                    'type' => GoogleIndexerMethodEnum::URL_UPDATED
                ];
            }
        }

        $output = [];

        foreach ($urls as $urlsChunk){
            foreach ($urlsChunk as $item){
                $output[] = $item;
            }
        }

        return $output;
    }

    public function getDeletedUrls(string $key, $query, callable $callback): array
    {
        /** TODO: тут надо запоминать отправленные items миграция google_index_items (morph_type, morph_id, google_type)*/
        /** TODO: уточнить, как сверять удлёные, чекать из sitemap или испольовать другой механизм */
    }

    private function getChunksRange(int $count, int $limit = 0): array
    {
        return range(0, (int)($count / $limit));
    }
}