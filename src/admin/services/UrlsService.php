<?php

namespace sitis\seo\google\indexer\admin\services;

use sitis\seo\google\indexer\admin\Interfaces\UrlsInterface;
use sitis\seo\google\indexer\admin\std\GoogleIndexerMethodEnum;
use sitis\seo\google\indexer\models\ngrest\SeoGoogleIndexerLog;

class UrlsService
{
    public function getUpdatedUrls(string $key, $query, callable $callback): array
    {
        /** TODO: тут надо запоминать отправленные items миграция google_index_items (morph_type, morph_id, google_type)*/

        $urls = [];

        foreach ($this->getChunksRange($query->count(), 1000) as $chunk){
            $modificationQuery = array_map($callback, $query->limit(1000)->offset($chunk * 1000)->all());

            foreach ($modificationQuery as $item){
                // пропускаем то что уже присутствует в логах
                if($this->getLogByItem($item->class, $item->id, $item->type)){
                    continue;
                }

                // если отправляем статус на удаление и при этому у нас нету в логах обновления этого товара, просто пропускаем (так как не можем удалять то что не обновлено)
                if(!$item->type == GoogleIndexerMethodEnum::URL_DELETED && $this->getLogByItem($item->class, $item->id, GoogleIndexerMethodEnum::URL_UPDATED)) {
                    continue;
                }

                if($item->type == GoogleIndexerMethodEnum::URL_DELETED && !$this->getLogByItem($item->class, $item->id, GoogleIndexerMethodEnum::URL_UPDATED)) {
                    continue;
                }

                $urls[$chunk][] = [
                    'url' => $item->location,
                    'class' => $item->class,
                    'item_type' => $item->itemType,
                    'id' => $item->id,
                    'create_model' => $item->createDate,
                    'update_model' => $item->updateDate,
                    'type' => $item->type,
                    'iteration' => $item->iteration
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

    private function getLogByItem(string $class, int $id, string $type){
        return SeoGoogleIndexerLog::find()->where(['class' => $class])->andWhere(['class_id' => $id])->andWhere(['type' => $type])->one();
    }

    private function getChunksRange(int $count, int $limit = 0): array
    {
        return range(0, (int)($count / $limit));
    }
}