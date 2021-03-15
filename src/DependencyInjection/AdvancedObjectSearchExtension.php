<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace AdvancedObjectSearchBundle\DependencyInjection;


use AdvancedObjectSearchBundle\AdvancedObjectSearchBundle;
use Pimcore\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class AdvancedObjectSearchExtension extends ConfigurableExtension
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
        $serviceLocator = $container->getDefinition("bundle.advanced_object_search.filter_locator");
        $arguments = [];

        foreach ($config['field_definition_adapters'] as $key => $serviceId) {
            $arguments[$key] = new Reference($serviceId);
        }

        $serviceLocator->setArgument(0, $arguments);

        $container->setParameter('pimcore.advanced_object_search.index_name_prefix', $config['index_name_prefix']);
        if($config['index_name_prefix'] === Configuration::BC_DEFAULT_VALUE) {
            try {
                $container->setParameter('pimcore.advanced_object_search.index_name_prefix', AdvancedObjectSearchBundle::getConfig()['index-prefix']);
            } catch (\Exception $e) {
                Logger::error('Error loading advanced-object-search config: ' . $e->getMessage());
            }
        }

        $container->setParameter('pimcore.advanced_object_search.es_hosts', $config['es_hosts']);
        if(is_array($config['es_hosts']) && $config['es_hosts'][0] === Configuration::BC_DEFAULT_VALUE) {
            try {
                $container->setParameter('pimcore.advanced_object_search.es_hosts', AdvancedObjectSearchBundle::getConfig()['hosts']);
            } catch (\Exception $e) {
                Logger::error('Error loading advanced-object-search config: ' . $e->getMessage());
            }
        }

    }

}
