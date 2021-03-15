<?php
/**
 * Created by PhpStorm.
 * User: cfasching
 * Date: 14.08.2017
 * Time: 16:09
 */

namespace AdvancedObjectSearchBundle\Tools;


use AdvancedObjectSearchBundle\AdvancedObjectSearchBundle;
use Elasticsearch\Client;

class EsClientFactory
{

    /**
     * @var Client
     */
    protected static $esClient = null;

    /**
     * @param ElasticSearchConfigService $esConfigService
     * @return Client
     * @throws \Exception
     */
    public static function getESClient(ElasticSearchConfigService $esConfigService) {

        if(empty(self::$esClient)) {
            self::$esClient = \Elasticsearch\ClientBuilder::create()
                ->setHosts($esConfigService->getHosts())
                ->setLogger($esConfigService->getLogger())
                ->build();
        }

        return self::$esClient;
    }
}
