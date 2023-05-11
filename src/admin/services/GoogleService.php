<?php

namespace sitis\seo\google\indexer\admin\services;

use Google\Exception;
use Google\Service\Indexing;
use Google_Client;
use Google_Service_Indexing;
use Google_Service_Indexing_UrlNotification;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use sitis\seo\google\indexer\admin\std\GoogleIndexerMethodEnum;
use sitis\seo\google\indexer\models\ngrest\SeoGoogleIndexerLog;
use sitis\shop\core\base\SaveModelErrorException;
use yii\helpers\FileHelper;

class GoogleService
{
    private string $url = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    private string $meta = 'https://indexing.googleapis.com/v3/urlNotifications/metadata';

    private ?string $oauth = null; // json из модуля

    private ?int $limit = 0; // json из модуля

    private string $pathToServiceAccountFile = ''; // json файл по OAuth

    private array $items = [];

    public function __construct(array $configs = [])
    {
        foreach ($configs as $key => $config) {
            if (property_exists($this, $key)) {
                $this->{$key} = $config;
            }
        }
        
        $this->oauth = \Yii::$app->modules['seogoogleindexeradmin']->seoGoogleOauth;
        $this->limit = \Yii::$app->modules['seogoogleindexeradmin']->seoGoogleIndexerLimit;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function request(): void
    {
        $client = $this->setAuth()->authorize();

        if(is_null($this->items[0]['iteration']) ){
            return;
        }

        if($this->limit <= SeoGoogleIndexerLog::find()->where(['iteration' => $this->items[0]['iteration']])->count()){
            return;
        }

        if($this->limit > count($this->items)){
            $this->items = array_slice($this->items, $this->limit)[0];
        }

        foreach ($this->items as $item) {

            $content = '{
              "url": "' . str_replace('{baseUrl}', 'https://sa.sitis.dev', $item['url']) . '",
              "type": "' . $item['type'] . '"
            }';

            $response = $client->post($this->url, ['body' => $content]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody()->getContents(), true);
                
                if($log = SeoGoogleIndexerLog::find()->where(['class' => $item['class']])->andWhere(['class_id' => $item['id']])->one()){
                    $log->type = $item['type'];
                    $log->create_model = $item['create_model'];
                    $log->update_model = $item['update_model'];
                    $log->iteration = $item['update_model'];

                    if(!$log->save()){
                        throw new SaveModelErrorException($log);
                    }

                } else {
                    SeoGoogleIndexerLog::create([
                        'type' => $data['urlNotificationMetadata']['latestUpdate']['type'],
                        'link' => $data['urlNotificationMetadata']['latestUpdate']['url'],
                        'action_date' => time(),
                        'status' => SeoGoogleIndexerLog::STATUS_SEND,
                        'item_type' => $item['item_type'],
                        'class' => $item['class'],
                        'class_id' => $item['id'],
                        'create_model' => $item['create_model'],
                        'update_model' => $item['update_model'],
                        'iteration' => $item['iteration'],
                    ]);
                }

            }
        }
        // удаляем вреиенный файл
        unlink($this->pathToServiceAccountFile);
    }

    /**
     * @return void
     * @throws Exception
     */
    private function setAuth(): Google_Client
    {
        $this->generateTmpOathFile();

        if (!file_exists($this->pathToServiceAccountFile)) {
            throw new \Exception('undefined key file');
        }

        if (array_pop(explode(".", $this->pathToServiceAccountFile)) != 'json') {
            throw new \Exception('authorization file extension must be json');
        }

        $client = new Google_Client();
        $client->setAuthConfig($this->pathToServiceAccountFile);
        $client->addScope('https://www.googleapis.com/auth/indexing');
        $client->setAccessType("offline");

        return $client;
    }

    /**
     * @return void
     * @throws Exception
     */
    private function generateTmpOathFile(): void {
        $tmpFile = \Yii::getAlias('@runtime/oauth/oauth.json');

        if (!is_dir($tmpFile)) {
            FileHelper::createDirectory(dirname($tmpFile));
        }

        $file = file_put_contents($tmpFile, $this->oauth);

        $this->pathToServiceAccountFile = $tmpFile;
    }
}