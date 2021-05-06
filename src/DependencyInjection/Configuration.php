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

        $rootNode
            ->children()
                ->scalarNode('index_name_prefix')
                    ->defaultValue('advanced_object_search')
                    ->info('Prefix for index names')
                ->end()
                ->arrayNode('es_hosts')
                    ->prototype('scalar')->end()
                    ->defaultValue(['localhost'])
                    ->info('List of elasticsearch hosts')
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
