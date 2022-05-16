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
