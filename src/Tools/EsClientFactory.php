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
     * @return Client
     */
    public static function getESClient() {

        if(empty(self::$esClient)) {
            $config = AdvancedObjectSearchBundle::getConfig();
            self::$esClient = \Elasticsearch\ClientBuilder::create()->setHosts($config['hosts'])->build();
        }

        return self::$esClient;
    }
}
