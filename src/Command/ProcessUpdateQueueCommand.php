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

use AdvancedObjectSearchBundle\Service;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessUpdateQueueCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('advanced-object-search:process-update-queue')
            ->setDescription("processes whole update queue of es search index")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $this->getContainer()->get("bundle.advanced_object_search.service");
        $count = 1;

        while($count) {
            $count = $service->processUpdateQueue();
        }

    }
}
