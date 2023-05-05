<?php

namespace sitis\seo\google\indexer\admin\std;

/**
 * Методы отправки запросов в google indexer
 */
class GoogleIndexerMethodEnum
{
    const URL_DELETED = 'URL_DELETED';
    const URL_UPDATED = 'URL_UPDATED';

    public static function methods() : array
    {
        return [
            self::URL_DELETED,
            self::URL_UPDATED
        ];
    }
}