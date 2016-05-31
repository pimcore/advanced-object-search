<?php

namespace ESBackendSearch\Console\Command;

use ESBackendSearch\Plugin;
use ESBackendSearch\Service;
use Pimcore\Console\AbstractCommand;
use Pimcore\Model\Object\ClassDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateMappingCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('es-backend-search:update-mapping')
            ->setDescription("Deletes and recreates mapping of given classes. Resets update queue for given class.")
            ->addOption('classes', 'c', InputOption::VALUE_OPTIONAL, 'just update specific classes, use "," (comma) to execute more than one class')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = new Service();
        $client = \ESBackendSearch\Plugin::getESClient();

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

            try {
                \Logger::info("Deleting index $indexName for class " . $class->getName());
                $response = $client->indices()->delete(["index" => $indexName]);
                \Logger::debug(json_encode($response));
            } catch (\Exception $e) {
                \Logger::debug($e);
            }

            try {
                \Logger::info("Creating index $indexName for class " . $class->getName());
                $response = $client->indices()->create(["index" => $indexName]);
                \Logger::debug(json_encode($response));
            } catch (\Exception $e) {
                \Logger::err($e);
            }

            \Logger::info("Putting mapping for class " . $class->getName());
            $mapping = $service->generateMapping(\Pimcore\Model\Object\ClassDefinition::getByName($class->getName()));
            $response = $client->indices()->putMapping($mapping);
            \Logger::debug(json_encode($response));

            $db = \Pimcore\Db::get();
            $db->query("UPDATE " . Plugin::QUEUE_TABLE_NAME . " SET in_queue = 1 WHERE classId = ?", $class->getId());

        }
    }
}
