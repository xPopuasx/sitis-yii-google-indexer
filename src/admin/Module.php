<?php

namespace sitis\seo\google\indexer\admin;

use luya\admin\components\AdminMenuBuilder;
use luya\admin\ngrest\ConfigBuilderInterface;
use sitis\config\base\ConfigurableModuleInterface;
use luya\admin\base\TypesInterface;
use yii\base\Application;

class Module extends \luya\admin\base\Module implements ConfigurableModuleInterface
{
    /**
     * @var string
     */
    public ?int $seoGoogleIndexerLimit = 0;

    /**
     * @var string
     */
    public ?string $seoGoogleOauth = null;

    public $apis = [
        'api-seo-google-indexer-log' => 'sitis\seo\google\indexer\admin\apis\SeoGoogleIndexerLogController',
    ];

    public function getMenu()
    {
        return (new AdminMenuBuilder($this))
            ->node('SEO Google индексация', 'extension')
            ->group('Запросы')
            ->itemApi('Информация о запросах', $this->id . '/seo-google-indexer-log/index', 'import_export', 'api-seo-google-indexer-log');
    }

    public function attributeTypes(): array
    {
        return [
            'seoGoogleIndexerLimit' => TypesInterface::TYPE_NUMBER,
            'seoGoogleOauth' => TypesInterface::TYPE_TEXTAREA,
        ];
    }

    public function attributeGroups(): array
    {
        return [];
    }

    public function attributeStorageTypes(): array
    {
        return [];
    }

    public function attributeLabels(): array
    {
        return [
            'seoGoogleIndexerLimit' => 'Лимит запросов в google-indexer в день (не более 200)',
            'seoGoogleOauth' => 'Содержимое файла ключа полученного из google-indexer',
        ];
    }

    public function attributeHints(): array
    {
        return [];
    }

    public function getModuleName(): string
    {
        return 'SEO Google';
    }

    public function getModuleIcon(): string
    {
        return 'data_saver_on';
    }

}
