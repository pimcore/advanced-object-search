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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'advanced-object-search:process-update-queue',
    description: 'processes whole update queue of es search index'
)]
class ProcessUpdateQueueCommand extends ServiceAwareCommand
{
    protected function configure(): void
    {
        // Configuration moved to AsCommand attribute
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
