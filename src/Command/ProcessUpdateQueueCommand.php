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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessUpdateQueueCommand extends ServiceAwareCommand
{
    protected function configure(): void
    {
        $this
            ->setName('advanced-object-search:process-update-queue')
            ->setDescription('processes whole update queue of es search index')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = 1;

        while ($count) {
            $count = $this->service->processUpdateQueue();
        }

        return 0;
    }
}
