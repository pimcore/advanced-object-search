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

namespace AdvancedObjectSearchBundle\Maintenance;

use AdvancedObjectSearchBundle\Messenger\QueueHandler;
use AdvancedObjectSearchBundle\Service;
use Pimcore\Maintenance\TaskInterface;

class UpdateQueueProcessor implements TaskInterface
{
    /**
     * @var Service
     */
    protected Service $service;

    /**
     * @var bool
     */
    protected bool $messengerQueueActivated;

    /**
     * @var QueueHandler
     */
    protected QueueHandler $queueHandler;

    /**
     * @param Service $service
     * @param bool $messengerQueueActivated
     * @param QueueHandler $queueHandler
     */
    public function __construct(Service $service, bool $messengerQueueActivated, QueueHandler $queueHandler)
    {
        $this->service = $service;
        $this->messengerQueueActivated = $messengerQueueActivated;
        $this->queueHandler = $queueHandler;
    }

    public function execute()
    {
        if ($this->messengerQueueActivated) {
            $this->queueHandler->dispatchMessages();
        } else {
            $this->service->processUpdateQueue(500);
        }
    }
}
