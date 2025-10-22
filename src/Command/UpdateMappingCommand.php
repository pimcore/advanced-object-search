<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace AdvancedObjectSearchBundle\Command;

use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'advanced-object-search:update-mapping',
    description: 'Deletes and recreates mapping of given classes. Resets update queue for given class.'
)]
class UpdateMappingCommand extends ServiceAwareCommand
{
    protected function configure(): void
    {
        $this->addOption(
            'classes',
            'c',
            InputOption::VALUE_OPTIONAL,
            'just update specific classes, use "," (comma) to execute more than one class'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $classes = [];

        if ($input->getOption('classes')) {
            $classNames = explode(',', $input->getOption('classes'));
            foreach ($classNames as $name) {
                $classes[] = ClassDefinition::getByName($name);
            }
        } else {
            $classes = new ClassDefinition\Listing();
            $classes->load();
            $classes = $classes->getClasses();
        }

        $classes = array_filter($classes);

        foreach ($classes as $class) {
            $indexName = $this->service->getIndexName($class->getName());

            $this->output->writeln('Processing ' . $class->getName() . " -> index $indexName");

            $this->service->updateMapping($class);
        }

        return 0;
    }
}
