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


namespace AdvancedObjectSearchBundle\Maintenance;


use AdvancedObjectSearchBundle\Service;
use Pimcore\Event\System\MaintenanceEvent;
use Pimcore\Maintenance\TaskInterface;
use Pimcore\Model\Schedule\Maintenance\Job;

class UpdateQueueProcessor implements TaskInterface
{
    /**
     * @var Service
     */
    protected $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function execute()
    {
        $this->service->processUpdateQueue(500);
    }
}
