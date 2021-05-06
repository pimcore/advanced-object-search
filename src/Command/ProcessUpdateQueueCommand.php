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

namespace AdvancedObjectSearchBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessUpdateQueueCommand extends ServiceAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('advanced-object-search:process-update-queue')
            ->setDescription('processes whole update queue of es search index')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = 1;

        while ($count) {
            $count = $this->service->processUpdateQueue();
        }

        return 0;
    }
}
