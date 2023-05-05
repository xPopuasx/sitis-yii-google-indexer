<?php

namespace sitis\seo\google\indexer\admin\services;

use Google\Exception;
use Google_Client;
use Google_Service_Indexing;
use Google_Service_Indexing_UrlNotification;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class GoogleService
{
    private string $url = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    private string $pathToServiceAccountFile = ''; // json файл по OAuth

    private array $items = [];

    public function __construct(array $configs = []){
        foreach($configs as $key => $config){
            if(property_exists($this, $key)){
                $this->{$key} = $config;
            }
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function request(): void
    {
        //$this->setAuth();
        $service = new Google_Service_Indexing();
        $batch = $service->createBatch();
        $postBody = new Google_Service_Indexing_UrlNotification();

        foreach ($this->items as $item){
            $postBody->setType($item['type']);
            $postBody->setUrl(str_replace('{baseUrl}', 'тет какая-то ссылка', $item['url']));
            $batch->add($service->urlNotifications->publish($postBody));
        }

        $batch->execute();
    }

    /**
     * @return void
     * @throws Exception
     */
    private function setAuth()
    {
        $client = new Google_Client();
        $client->setAuthConfig($this->pathToServiceAccountFile);
        $client->addScope('https://www.googleapis.com/auth/indexing');
        $client->setUseBatch(true);
    }


}