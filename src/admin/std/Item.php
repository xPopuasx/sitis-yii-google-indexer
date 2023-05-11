<?php

namespace sitis\seo\google\indexer\admin\std;

use sitis\shop\core\services\sitemap\AlternateItem;

/**
 * Методы отправки запросов в google indexer
 */
class Item
{
    public string $location;
    public string $type;
    public string $class;
    public int $lastModified;
    public int $id;
    public string $itemType;
    public int $createDate;
    public int $updateDate;
    public int $iteration;

    public function __construct(int $id, int $createDate, int $updateDate, string $location, int $lastModified, string $type = '', string $class = '', string $itemType = '', int $iteration = 1)
    {
        $this->id = $id;
        $this->location = $location;
        $this->lastModified = $lastModified;
        $this->type = $type;
        $this->class = $class;
        $this->itemType = $itemType;
        $this->createDate = $createDate;
        $this->updateDate = $updateDate;
        $this->iteration = $iteration;
    }
}