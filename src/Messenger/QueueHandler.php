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

namespace AdvancedObjectSearchBundle\Messenger;

use AdvancedObjectSearchBundle\Service;
use Pimcore\Model\Tool\TmpStore;
use Symfony\Component\Messenger\MessageBusInterface;

class QueueHandler
{
    const IMPORTER_WORKER_COUNT_TMP_STORE_KEY = 'ADVANCED-OBJECT-SEARCH::worker-count';

    protected Service $queueService;
    protected MessageBusInterface $messageBus;
    protected int $workerCountLifeTime;
    protected int $workerItemCount;
    protected int $workerCount;

    public function __construct(Service $queueService, MessageBusInterface $messageBus, int $workerCountLifeTime, int $workerItemCount, int $workerCount)
    {
        $this->queueService = $queueService;
        $this->messageBus = $messageBus;
        $this->workerCountLifeTime = $workerCountLifeTime;
        $this->workerItemCount = $workerItemCount;
        $this->workerCount = $workerCount;
    }

    public function __invoke(QueueMessage $message)
    {
        $this->queueService->doProcessUpdateQueue($message->getWorkerId(), $message->getEntries());

        $workerCount = 0;
        $entry = TmpStore::get(self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY);
        if($entry instanceof TmpStore) {
            $workerCount = $entry->getData() ?? 0;
        }
        $workerCount--;
        TmpStore::set(self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY, $workerCount, null, $this->workerCountLifeTime);

        $this->dispatchMessages();
    }

    public function dispatchMessages()
    {
        $workerCount = TmpStore::get(self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY)?->getData() ?? 0;

        $addWorkers = true;
        while ($addWorkers && $workerCount < $this->workerCount) {
            $workerId = uniqid();
            $entries = $this->queueService->initUpdateQueue($workerId, $this->workerItemCount);
            if (!empty($entries)) {
                $this->messageBus->dispatch(new QueueMessage($workerId, $entries));
                $workerCount++;
                TmpStore::set(self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY, $workerCount, null, $this->workerCountLifeTime);
            } else {
                $addWorkers = false;
            }
        }
    }
}
