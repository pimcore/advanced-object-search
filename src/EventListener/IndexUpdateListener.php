<?php
/**
 * Created by PhpStorm.
 * User: cfasching
 * Date: 14.08.2017
 * Time: 12:17
 */

namespace AdvancedObjectSearchBundle\EventListener;


use AdvancedObjectSearchBundle\Service;
use Pimcore\Event\Model\Object\ClassDefinitionEvent;
use Pimcore\Event\Model\ObjectEvent;
use Pimcore\Event\System\MaintenanceEvent;
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


    public function registerMaintenanceJob(MaintenanceEvent $maintenanceEvent) {
        $maintenanceEvent->getManager()->registerJob(new Job(get_class($this), $this, "maintenance"));
    }

    public function maintenance() {
        $service = new Service();
        $service->processUpdateQueue(500);
    }

}
