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

namespace AdvancedObjectSearchBundle\DependencyInjection;

use AdvancedObjectSearchBundle\Enum\ClientType;
use AdvancedObjectSearchBundle\Maintenance\UpdateQueueProcessor;
use AdvancedObjectSearchBundle\Messenger\QueueHandler;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class AdvancedObjectSearchExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    public function loadInternal(array $config, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');

        $container->setParameter(
            'advanced_object_search.core_fields_configuration',
            $config['core_fields_configuration']
        );

        // load mappings for field definition adapters
        $serviceLocator = $container->getDefinition('bundle.advanced_object_search.filter_locator');
        $arguments = [];

        foreach ($config['field_definition_adapters'] as $key => $serviceId) {
            $arguments[$key] = new Reference($serviceId);
        }

        $serviceLocator->setArgument(0, $arguments);

        $container->setParameter('pimcore.advanced_object_search.index_name_prefix', $config['index_name_prefix']);

        $container->setParameter(
            'pimcore.advanced_object_search.index_configuration',
            $config['index_configuration']
        );

        $definition = $container->getDefinition(QueueHandler::class);
        $definition->setArgument('$workerCountLifeTime', $config['messenger_queue_processing']['worker_count_lifetime']);
        $definition->setArgument('$workerItemCount', $config['messenger_queue_processing']['worker_item_count']);
        $definition->setArgument('$workerCount', $config['messenger_queue_processing']['worker_count']);

        $definition = $container->getDefinition(UpdateQueueProcessor::class);
        $definition->setArgument('$messengerQueueActivated', $config['messenger_queue_processing']['activated']);
        if ($config['client_type'] === ClientType::OPEN_SEARCH->value) {
            $openSearchClientId = 'pimcore.open_search_client.' . $config['client_name'];
            $container->setAlias('pimcore.advanced_object_search.opensearch-client', $openSearchClientId)
                ->setDeprecated(
                    'pimcore/advanced-object-search',
                    '6.1',
                    'The "%alias_id%" service alias is deprecated and will be removed in version 7.0. ' .
                    'Please use "pimcore.advanced_object_search.search-client" instead.'
                );
        }

        $clientId = $this->getDefaultSearchClientId($config);
        $container->setAlias('pimcore.advanced_object_search.search-client', $clientId);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('doctrine_migrations')) {
            $loader = new YamlFileLoader(
                $container,
                new FileLocator(__DIR__ . '/../Resources/config')
            );

            $loader->load('doctrine_migrations.yml');
        }
    }

    /**
     * @throws RuntimeException
     */
    private function getDefaultSearchClientId(array $indexSettings): string
    {
        $clientType = $indexSettings['client_type'];
        $clientName = $indexSettings['client_name'];

        return match ($clientType) {
            ClientType::OPEN_SEARCH->value => 'pimcore.openSearch.custom_client.' . $clientName,
            ClientType::ELASTIC_SEARCH->value => 'pimcore.elasticsearch.custom_client.' . $clientName,
            default => throw new RuntimeException(
                sprintf('Invalid client type: %s', $clientType)
            )
        };
    }
}
