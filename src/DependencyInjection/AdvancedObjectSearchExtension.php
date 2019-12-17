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
    }

}
