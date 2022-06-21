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

        $this->removeMessage($message->getWorkerId());
        $this->dispatchMessages();
    }

    public function dispatchMessages()
    {
        $dispatchedMessageCount = $this->getMessageCount();

        $addWorkers = true;
        while ($addWorkers && $dispatchedMessageCount < $this->workerCount) {
            $workerId = uniqid();
            $entries = $this->queueService->initUpdateQueue($workerId, $this->workerItemCount);
            if (!empty($entries)) {
                $this->addMessage($workerId);
                $this->messageBus->dispatch(new QueueMessage($workerId, $entries));
                $dispatchedMessageCount = $this->getMessageCount();
            } else {
                $addWorkers = false;
            }
        }
    }

    private function addMessage(string $messageId)
    {
        TmpStore::set(self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY . $messageId, true, self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY, $this->workerCountLifeTime);
    }

    private function removeMessage(string $messageId)
    {
        TmpStore::delete(self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY . $messageId);
    }

    private function getMessageCount(): int
    {
        $ids = TmpStore::getIdsByTag(self::IMPORTER_WORKER_COUNT_TMP_STORE_KEY);
        $runningWorkers = [];
        foreach ($ids as $id) {
            $runningWorkers[] = TmpStore::get($id);
        }

        return count(array_filter($runningWorkers));
    }
}
