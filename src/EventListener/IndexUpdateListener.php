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

namespace AdvancedObjectSearchBundle\EventListener;

use AdvancedObjectSearchBundle\Service;
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Logger;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;

class IndexUpdateListener
{
    /**
     * @var Service
     */
    protected $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function updateObject(DataObjectEvent $event)
    {
        //do not update index when auto save or only saving version
        if (
            ($event->hasArgument('isAutoSave') && $event->getArgument('isAutoSave')) ||
            ($event->hasArgument('saveVersionOnly') && $event->getArgument('saveVersionOnly'))
        ) {
            return;
        }

        $inheritanceBackup = AbstractObject::getGetInheritedValues();
        AbstractObject::setGetInheritedValues(true);

        $object = $event->getObject();
        if ($object instanceof Concrete) {
            $this->service->doUpdateIndexData($object);
        }

        AbstractObject::setGetInheritedValues($inheritanceBackup);
    }

    public function deleteObject(DataObjectEvent $event)
    {
        $object = $event->getObject();
        if ($object instanceof Concrete) {
            $this->service->doDeleteFromIndex($object);
        }
    }

    public function updateMapping(ClassDefinitionEvent $event)
    {
        $classDefinition = $event->getClassDefinition();
        $this->service->updateMapping($classDefinition);
    }

    public function deleteIndex(ClassDefinitionEvent $event)
    {
        $classDefinition = $event->getClassDefinition();

        try {
            $this->service->deleteIndex($classDefinition);
        } catch (\Exception $e) {
            Logger::err($e->getMessage());
        }
    }
}
