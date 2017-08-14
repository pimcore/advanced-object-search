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


namespace AdvancedObjectSearchBundle\EventListener;


use AdvancedObjectSearchBundle\Service;
use Pimcore\Event\Model\Object\ClassDefinitionEvent;
use Pimcore\Event\Model\ObjectEvent;
use Pimcore\Event\System\MaintenanceEvent;
use Pimcore\Logger;
use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\Schedule\Maintenance\Job;

class IndexUpdateListener
{
    public function updateObject(ObjectEvent $event)
    {
        $inheritanceBackup = AbstractObject::getGetInheritedValues();
        AbstractObject::setGetInheritedValues(true);

        $object = $event->getObject();
        if($object instanceof Concrete) {
            $service = new Service();
            $service->doUpdateIndexData($object);
        }

        AbstractObject::setGetInheritedValues($inheritanceBackup);
    }

    public function deleteObject(ObjectEvent $event)
    {
        $object = $event->getObject();
        if($object instanceof Concrete) {
            $service = new Service();
            $service->doDeleteFromIndex($object);
        }
    }

    public function updateMapping(ClassDefinitionEvent $event) {
        $classDefinition = $event->getClassDefinition();
        $service = new Service();
        $service->updateMapping($classDefinition);
    }

    public function deleteIndex(ClassDefinitionEvent $event) {
        $classDefinition = $event->getClassDefinition();
        $service = new Service();
        try {
            $service->deleteIndex($classDefinition);
        } catch (\Exception $e) {
            Logger::err($e);
        }
    }

    public function registerMaintenanceJob(MaintenanceEvent $maintenanceEvent) {
        $maintenanceEvent->getManager()->registerJob(new Job(get_class($this), $this, "maintenance"));
    }

    public function maintenance() {
        $service = new Service();
        $service->processUpdateQueue(500);
    }

}
