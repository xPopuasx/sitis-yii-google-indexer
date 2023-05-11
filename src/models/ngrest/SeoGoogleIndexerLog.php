<?php

namespace sitis\seo\google\indexer\models\ngrest;


use luya\admin\aws\DetailViewActiveWindow;
use luya\admin\ngrest\base\NgRestModel;
use sitis\shop\core\base\SaveModelErrorException;

/**
 * @property integer $id
 * @property string $link
 * @property string $type
 * @property string $status
 * @property integer $action_date
 * @property string $error_description
 * @property string $item_type
 * @property string $class
 * @property integer $class_id
 * @property integer $create_model
 * @property integer $update_model
 * @property integer $iteration
 */
class SeoGoogleIndexerLog extends NgRestModel
{
    const STATUS_SEND = 'Отправлено';
    const STATUS_ERROR = 'Ошибка';

    public static function tableName(): string
    {
        return '{{%seo_google_indexer_log}}';
    }

    public static function ngRestApiEndpoint(): string
    {
        return 'api-seo-google-indexer-log';
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'link' => 'Ссылка',
            'type' => 'Тип запроса',
            'status' => 'Статус запроса',
            'action_date' => 'Дата составления запроса',
            'error_description' => 'Ошибка',
            'item_type' => 'Тип сущности',
            'class' => 'класс сущности',
            'class_id' => 'id сущности',
            'create_model' => 'Дата создания сущности',
            'update_model' => 'Дата обновления сущности',
            'iteration' => 'Итерация'
        ];
    }

    public function rules(): array
    {
        return [
            [['action_date', 'create_model', 'update_model', 'iteration'], 'integer'],
            [['link', 'type', 'status', 'error_description'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        return ['extraStatus'];
    }

    public function ngRestAttributeTypes(): array
    {
        return [
            'action_date' => ['datetime', 'format' => 'dd.MM.yy HH:mm:ss'],
            'create_model' => ['datetime', 'format' => 'dd.MM.yy HH:mm:ss'],
            'update_model' => ['datetime', 'format' => 'dd.MM.yy HH:mm:ss'],
            'link' => 'textarea',
            'type' => 'textarea',
            'status' => 'textarea',
            'item_type' => 'textarea',
            'calss' => 'textarea',
            'class_id' => 'number',
            'iteration' => 'number',
            'error_description' => 'textarea',
        ];
    }

    public function ngRestExtraAttributeTypes(): array
    {
        return [
            'extraStatus' => [
                'html',
                'sortField' => false,
            ],
        ];
    }

    public function ngRestScopes(): array
    {
        return [
            ['list', ['link', 'type', 'status', 'action_date', 'item_type', 'create_model', 'update_model']],
            ['delete', false],
        ];
    }

    public function ngRestActiveWindows(): array
    {
        return [
            [
                'class' => DetailViewActiveWindow::class,
            ],
        ];
    }

    public static function create($attributes = []): self
    {
        $model = new self();

        foreach ($attributes as $key => $value) {

            if (!in_array($key, array_keys($model->getAttributes()))) {
                throw new InvalidArgumentException();
            }
        }

        foreach ($attributes as $key => $value) {
            $model->{$key} = $value;
        }

        if (!$model->save()) {
            throw new SaveModelErrorException($model);
        }

        return $model;
    }
}