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


namespace AdvancedObjectSearchBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateMappingCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('advanced-object-search:update-mapping')
            ->setDescription("Deletes and recreates mapping of given classes. Resets update queue for given class.")
            ->addOption('classes', 'c', InputOption::VALUE_OPTIONAL, 'just update specific classes, use "," (comma) to execute more than one class')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $this->getContainer()->get("bundle.advanced_object_search.service");

        $classes = [];

        if ($input->getOption("classes")) {
            $classNames = explode(",", $input->getOption("classes"));
            foreach($classNames as $name) {
                $classes[] = ClassDefinition::getByName($name);
            }
        } else {
            $classes = new ClassDefinition\Listing();
            $classes->load();
            $classes = $classes->getClasses();
        }

        $classes = array_filter($classes);

        foreach ($classes as $class) {

            $indexName = $service->getIndexName($class->getName());

            $this->output->writeln("Processing " . $class->getName() . " -> index $indexName");

            $service->updateMapping($class);

        }
    }
}
