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

    public function execute(): void
    {
        if ($this->messengerQueueActivated) {
            $this->queueHandler->dispatchMessages();
        } else {
            $this->service->processUpdateQueue(500);
        }
    }
}
