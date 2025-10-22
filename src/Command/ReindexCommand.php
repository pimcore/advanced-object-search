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

use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'advanced-object-search:re-index',
    description: 'Reindex all objects of given class. Does not delete index first or resets update queue.'
)]
class ReindexCommand extends ServiceAwareCommand
{
    protected ?array $indexConfiguration = null;

    public function __construct(array $indexConfiguration)
    {
        $this->indexConfiguration = $indexConfiguration;
        parent::__construct();
    }

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
        $elementsPerLoop = $this->indexConfiguration['elements_per_loop'];

        foreach ($classes as $class) {
            $listClassName = '\\Pimcore\\Model\\DataObject\\' . ucfirst($class->getName()) . '\\Listing';
            $list = new $listClassName();
            $list->setObjectTypes([AbstractObject::OBJECT_TYPE_OBJECT, AbstractObject::OBJECT_TYPE_VARIANT]);
            $list->setUnpublished(true);

            $elementsTotal = $list->getTotalCount();

            for ($i = 0; $i < (ceil($elementsTotal / $elementsPerLoop)); $i++) {
                $list->setLimit($elementsPerLoop);
                $list->setOffset($i * $elementsPerLoop);

                $this->output->writeln('Processing ' . $class->getName() . ': ' . min($list->getOffset() + $elementsPerLoop, $elementsTotal) . '/' . $elementsTotal);

                $objects = $list->load();
                foreach ($objects as $object) {
                    try {
                        $this->service->doUpdateIndexData($object, true);
                    } catch (\Exception $e) {
                        $this->writeError($e->getMessage());
                    }
                }
                \Pimcore::collectGarbage();
            }
        }

        return 0;
    }
}
