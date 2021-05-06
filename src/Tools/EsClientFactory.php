<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace AdvancedObjectSearchBundle\Tools;

use Elasticsearch\Client;

class EsClientFactory
{
    /**
     * @var Client
     */
    protected static $esClient = null;

    /**
     * @param ElasticSearchConfigService $esConfigService
     *
     * @return Client
     *
     * @throws \Exception
     */
    public static function getESClient(ElasticSearchConfigService $esConfigService)
    {
        if (empty(self::$esClient)) {
            self::$esClient = \Elasticsearch\ClientBuilder::create()
                ->setHosts($esConfigService->getHosts())
                ->setLogger($esConfigService->getLogger())
                ->setConnectionParams(['client' => ['ignore' => [404]]])
                ->build();
        }

        return self::$esClient;
    }
}
