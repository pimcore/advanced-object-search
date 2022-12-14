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

use AdvancedObjectSearchBundle\Maintenance\UpdateQueueProcessor;
use AdvancedObjectSearchBundle\Messenger\QueueHandler;
use AdvancedObjectSearchBundle\Service;
use Pimcore\Bundle\ElasticsearchClientBundle\DependencyInjection\PimcoreElasticsearchClientExtension;
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

        $definition = $container->getDefinition(Service::class);
        $definition->setArgument('$esClient', new Reference(PimcoreElasticsearchClientExtension::CLIENT_SERVICE_PREFIX . $config['es_client_name']));
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
}
