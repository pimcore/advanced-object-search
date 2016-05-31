<?php

namespace ESBackendSearch\Console\Command;

use ESBackendSearch\Service;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessUpdateQueueCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('es-backend-search:process-update-queue')
            ->setDescription("processes whole update queue of es search index")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = new Service();
        $count = 1;

        while($count) {
            $count = $service->processUpdateQueue();
        }

    } 
}
