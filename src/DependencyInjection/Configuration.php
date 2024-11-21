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
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('pimcore_advanced_object_search');
        $rootNode = $treeBuilder->getRootNode();

        /* @phpstan-ignore-next-line */
        $rootNode
            ->children()
                ->scalarNode('index_name_prefix')
                    ->defaultValue('advanced_object_search')
                    ->info('Prefix for index names')
                ->end()
                ->scalarNode('client_name')
                    ->info('Name of search client configuration to be used.')
                    ->defaultValue('default')
                ->end()
                ->enumNode('client_type')
                    ->info('Type of search client to be used.')
                    ->values([ClientType::OPEN_SEARCH->value, ClientType::ELASTIC_SEARCH->value])
                    ->defaultValue(ClientType::OPEN_SEARCH->value)
                ->end()
                ->arrayNode('index_configuration')
                    ->info('Add mapping between data object type and service implementation for field definition adapter')
                    ->children()
                        ->integerNode('elements_per_loop')
                            ->defaultValue(100)
                        ->end()
                        ->integerNode('nested_fields_limit')
                            ->defaultValue(200)
                        ->end()
                        ->integerNode('total_fields_limit')
                            ->defaultValue(100000)
                        ->end()
                        ->arrayNode('exclude_classes')
                            ->prototype('scalar')->end()
                            ->info('List of excluded class names from OpenSearch index')
                        ->end()
                        ->arrayNode('exclude_fields')
                            ->useAttributeAsKey('class')
                                ->prototype('scalar')
                            ->end()
                            ->arrayPrototype('fields')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('messenger_queue_processing')
                    ->addDefaultsIfNotSet()
                    ->info('Configure index queue processing via symfony messenger')
                    ->children()
                        ->booleanNode('activated')
                            ->info('Activate dispatching messages, will deactivate processing during Pimcore maintenance.')
                            ->defaultFalse()
                        ->end()
                        ->integerNode('worker_count_lifetime')
                            ->defaultValue(60 * 60) //1 hour
                            ->info('Lifetime of tmp store entry for current worker count entry. Default to 1 hour.')
                        ->end()
                        ->integerNode('worker_item_count')
                            ->defaultValue(400)
                            ->info('Count of items processed per worker message.')
                        ->end()
                        ->integerNode('worker_count')
                            ->defaultValue(3)
                            ->info('Count of maximum parallel worker messages.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('field_definition_adapters')
                    ->info('Add mapping between data object type and service implementation for field definition adapter')
                    ->useAttributeAsKey('name')
                        ->prototype('scalar')
                    ->end()
                ->end()
                ->arrayNode('core_fields_configuration')
                    ->useAttributeAsKey('field')
                        ->prototype('scalar')
                    ->end()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('type')
                                ->isRequired()
                            ->end()
                            ->scalarNode('title')
                                ->isRequired()
                            ->end()
                            ->scalarNode('fieldDefinition')->end()
                            ->arrayNode('values')
                                ->children()
                                    ->arrayNode('options')
                                        ->arrayPrototype()
                                            ->children()
                                                ->scalarNode('key')
                                                    ->isRequired()
                                                ->end()
                                                ->scalarNode('value')
                                                    ->isRequired()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
