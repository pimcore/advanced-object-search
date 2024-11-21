<?php
declare(strict_types=1);

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

namespace AdvancedObjectSearchBundle\Factory\OpenSearch;

use AdvancedObjectSearchBundle\Service;
use AdvancedObjectSearchBundle\Tools\IndexConfigService;
use OpenSearch\ClientBuilder;
use Pimcore\Bundle\ElasticsearchClientBundle\SearchClient\ElasticsearchClientInterface;
use Pimcore\Bundle\OpenSearchClientBundle\SearchClient\OpenSearchClientInterface;
use Pimcore\SearchClient\SearchClientInterface;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Translation\Translator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 * @deprecated will be removed in version 7.0
 */
final class LegacyServiceFactory
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TokenStorageUserResolver $userResolver,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Translator $translator,
        private readonly IndexConfigService $indexConfigService,
        private readonly SearchClientInterface $client
    )
    {

    }

    public function create(
        ContainerInterface $filterLocator
    ): Service
    {
        $openSearchClient = match (true) {
            $this->client instanceof OpenSearchClientInterface => $this->client->getOriginalClient(),
            $this->client instanceof ElasticsearchClientInterface => ClientBuilder::create()->build(),
            default => null,
        };

        if ($openSearchClient === null) {
            throw new RuntimeException('No client found for OpenSearch');
        }

        $service = new Service(
            $this->logger,
            $this->userResolver,
            $filterLocator,
            $this->eventDispatcher,
            $this->translator,
            $this->indexConfigService,
            $openSearchClient
        );

        $service->setSearchClientInterface($this->client);

        return $service;
    }
}
