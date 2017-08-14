<?php

namespace AdvancedObjectSearchBundle\Command;

use AdvancedObjectSearchBundle\Service;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Object\ClassDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReindexCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('es-backend-search:re-index')
            ->setDescription("Reindex all objects of given class. Does not delete index first or resets update queue.")
            ->addOption('classes', 'c', InputOption::VALUE_OPTIONAL, 'just update specific classes, use "," (comma) to execute more than one class')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = new Service();

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
        $elementsPerLoop = 100;

        foreach ($classes as $class) {
            $listClassName = "\\Pimcore\\Model\\Object\\" . ucfirst($class->getName()) . "\\Listing";
            $list = new $listClassName();
            $list->setUnpublished(true);

            $elementsTotal = $list->getTotalCount();

            for ($i=0; $i<(ceil($elementsTotal/$elementsPerLoop)); $i++) {
                $list->setLimit($elementsPerLoop);
                $list->setOffset($i*$elementsPerLoop);

                $this->output->writeln("Processing " . $class->getName() . ": " . ($list->getOffset()+$elementsPerLoop) . "/" . $elementsTotal);

                $objects = $list->load();
                foreach ($objects as $object) {
                    try {
                        $service->doUpdateIndexData($object, true);
                    } catch (\Exception $e) {
                        \Logger::err($e);
                    }
                }
                \Pimcore::collectGarbage();
            }
        }
    }
}
