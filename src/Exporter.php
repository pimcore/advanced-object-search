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
declare(strict_types=1);

namespace AdvancedObjectSearchBundle;

use AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter\IFieldDefinitionAdapter;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick;
use Psr\Container\ContainerInterface;

class Exporter
{
    /**
     * @var ContainerInterface
     */
    protected $filterLocator;

    public function __construct(ContainerInterface $filterLocator) {
        $this->filterLocator = $filterLocator;
    }

    /**
     * @param Concrete $object
     * @param array $result
     * @param Objectbrick $container
     * @param Data\Objectbricks $brickFieldDef
     */
    public function doExportBrick(Concrete $object, array &$result, Objectbrick $container, Data\Objectbricks $brickFieldDef)
    {
        $allowedBrickTypes = $container->getAllowedBrickTypes();
        $resultContainer = [];

        $considerInheritance = $object->getClass()->getAllowInherit();

        $prefixes = $considerInheritance ? [IFieldDefinitionAdapter::ES_MAPPING_PROPERTY_STANDARD, IFieldDefinitionAdapter::ES_MAPPING_PROPERTY_NOT_INHERITED]
            : [IFieldDefinitionAdapter::ES_MAPPING_PROPERTY_NOT_INHERITED];

        foreach ($prefixes as $prefix) {

            $inheritanceBackup = AbstractObject::getGetInheritedValues();
            AbstractObject::setGetInheritedValues($prefix === "standard");

            foreach ($allowedBrickTypes as $brickType) {
                $brickDef = Objectbrick\Definition::getByKey($brickType);
                $brickGetter = "get" . ucfirst($brickType);
                $brickValue = $container->$brickGetter();

                if ($brickValue instanceof Objectbrick\Data\AbstractData) {
                    $fDefs = $brickDef->getFieldDefinitions();
                    foreach ($fDefs as $fd) {
                        $getter = "get" . ucfirst($fd->getName());
                        $value = $brickValue->$getter();
                        $marshalledValue = $fd->marshal($value, $object, ['blockmode' => true]);
                        $packedData = $this->postMarshalData($marshalledValue, $fd);

                        if ($packedData) {
                            $resultContainer[$brickType][$prefix] = $resultContainer[$brickType][$prefix] ?? [];

                            if (count($prefixes) < 2) {
                                $resultContainer[$brickType][$prefix][$fd->getName()] = $packedData;
                            } else {

                                $resultContainer[$brickType][$prefix][$fd->getName()] = $packedData;
                            }
                        }
                    }
                }
            }

            $result[$container->getFieldname()] = $resultContainer;

            AbstractObject::setGetInheritedValues($inheritanceBackup);
        }
    }

    public function postMarshalData($data, Data $fieldDefinition) {
        $adapter = null;

        if ($this->filterLocator->has($fieldDefinition->fieldtype)) {
            $adapter = $this->filterLocator->get($fieldDefinition->fieldtype);
        } else {
            $adapter = $this->filterLocator->get('default');
        }

        $data = $adapter->postMarshalData($data, $fieldDefinition);
        return $data;
    }


    /**
     * @param Concrete $object
     * @param array $result
     * @param Fieldcollection $container
     * @param Data\Fieldcollections $containerDef
     * @throws \Exception
     */
    public function doExportFieldcollection(Concrete $object, array &$result, Fieldcollection $container, Data\Fieldcollections $containerDef)
    {
        $resultContainer = [];

        $considerInheritance = $object->getClass()->getAllowInherit();

        $prefixes = $considerInheritance ? [IFieldDefinitionAdapter::ES_MAPPING_PROPERTY_STANDARD, IFieldDefinitionAdapter::ES_MAPPING_PROPERTY_NOT_INHERITED]
            : [IFieldDefinitionAdapter::ES_MAPPING_PROPERTY_NOT_INHERITED];

        foreach ($prefixes as $prefix) {
            $inheritanceBackup = AbstractObject::getGetInheritedValues();
            AbstractObject::setGetInheritedValues($prefix === "standard");

            $items = $container->getItems();
            foreach ($items as $item) {
                $type = $item->getType();

                $itemValues = [];

                $itemContainerDefinition = Fieldcollection\Definition::getByKey($type);
                $fDefs = $itemContainerDefinition->getFieldDefinitions();

                foreach ($fDefs as $fd) {
                    $getter = "get" . ucfirst($fd->getName());
                    $value = $item->$getter();
                    $marshalledValue = $fd->marshal($value, $object, ['blockmode' => true]);
                    $packedData = $this->postMarshalData($marshalledValue, $fd);

                    if ($packedData) {
                        if (count($prefixes) < 2) {
                            $itemValues[$fd->getName()] = $packedData;
                        } else {
                            $itemValues[$fd->getName()] = $itemValues[$fd->getName()] ?? [];
                            $itemValues[$fd->getName()][$prefix] = $packedData;
                        }
                    }
                }

                $resultContainer[] = [
                    "type" => $type,
                    "value" => $itemValues
                ];
            }

            AbstractObject::setGetInheritedValues($inheritanceBackup);
        }

        $result[$container->getFieldname()] = $resultContainer;
    }

    /**
     * @param Concrete $object
     * @param array $result
     * @throws \Exception
     */
    public function doExportObject(Concrete $object, &$result = [])
    {
        $considerInheritance = $object->getClass()->getAllowInherit();

        $prefixes = $considerInheritance ? [IFieldDefinitionAdapter::ES_MAPPING_PROPERTY_STANDARD, IFieldDefinitionAdapter::ES_MAPPING_PROPERTY_NOT_INHERITED]
            : [IFieldDefinitionAdapter::ES_MAPPING_PROPERTY_NOT_INHERITED];

        $fDefs = $object->getClass()->getFieldDefinitions();

        foreach ($prefixes as $prefix) {
            $inheritanceBackup = AbstractObject::getGetInheritedValues();
            AbstractObject::setGetInheritedValues($prefix === "standard");

            /** @var Data $fd */
            foreach ($fDefs as $fd) {
                $getter = "get" . ucfirst($fd->getName());

                $value = $object->$getter();

                if ($fd instanceof Data\Fieldcollections) {
                    $this->doExportFieldcollection($object, $result, $value, $fd);
                } else if ($fd instanceof Data\Objectbricks) {
                    $this->doExportBrick($object, $result, $value, $fd);
                } else {
                    $marshalledValue = $fd->marshal($value, $object, ['blockmode' => true]);
                    $packedData = $this->postMarshalData($marshalledValue, $fd);

                    if ($packedData) {
                        if (count($prefixes) < 2) {
                            $result[$fd->getName()] = $packedData;
                        } else {
                            if (!isset($result[$fd->getName()])) {
                                $result[$fd->getName()] = [];
                            }
                            $result[$fd->getName()][$prefix] = $packedData;
                        }
                    }
                }
            }

            AbstractObject::setGetInheritedValues($inheritanceBackup);
        }
    }

    public function array_filter_recursive($input)
    {
        foreach ($input as &$value)
        {
            if (is_array($value))
            {
                if (isset($value['value']) && isset($value['value2'])) {
                    $data = array_values($value);
                }

                $value = $this->array_filter_recursive($value);
            }
        }

        return array_filter($input);
    }

    /**
     * @param Concrete $object
     * @return array
     */
    public function exportObject(AbstractObject $object)
    {

        $considerInheritance = $object->getClass()->getAllowInherit();

        $webObject = [];
        $webObject["id"] = $object->getId();
        $webObject["fullpath"] = $object->getFullPath();

        $properties = $object->getProperties();
        $finalProperties = [];

        foreach ($properties as $property) {
            $finalProperties[] = $property->serialize();
        }

        $webObject["properties"] = $finalProperties;

        if ($object instanceof Concrete) {
            $this->doExportObject($object, $webObject);
        }

        $resultItem = json_decode(json_encode($webObject), true);
        unset($resultItem['data']);

        return $resultItem;
    }
}
