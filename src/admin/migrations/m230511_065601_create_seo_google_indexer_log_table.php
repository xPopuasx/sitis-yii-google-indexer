<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%seo_google_indexer_log}}`.
 */
class m230511_065601_create_seo_google_indexer_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%seo_google_indexer_log}}', [
            'id' => $this->primaryKey(),
            'link' => $this->string()->null(),
            'type' => $this->string()->null(),
            'status' => $this->string()->null(),
            'action_date' => $this->integer()->null(),
            'error_description' => $this->text()->null(),
            'item_type' => $this->text()->null(),
            'class' => $this->text()->null(),
            'class_id' => $this->integer()->null(),
            'create_model' => $this->integer()->null(),
            'update_model' => $this->integer()->null(),
            'iteration' => $this->integer()->defaultValue(0),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%seo_google_indexer_log}}');
    }
}
