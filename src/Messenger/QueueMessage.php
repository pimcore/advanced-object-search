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

namespace AdvancedObjectSearchBundle\Messenger;

class QueueMessage
{
    protected string $workerId;
    protected array $entries;

    /**
     * @param string $workerId
     * @param array $entries
     */
    public function __construct(string $workerId, array $entries)
    {
        $this->workerId = $workerId;
        $this->entries = $entries;
    }

    /**
     * @return string
     */
    public function getWorkerId(): string
    {
        return $this->workerId;
    }

    /**
     * @return array
     */
    public function getEntries(): array
    {
        return $this->entries;
    }
}
