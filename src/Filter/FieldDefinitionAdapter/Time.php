<?php

namespace AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter;


use Pimcore\Model\Object\AbstractObject;
use Pimcore\Model\Object\Concrete;

class Time extends Datetime implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "time";


    /**
     * @param Concrete $object
     * @param bool $ignoreInheritance
     */
    protected function doGetIndexDataValue($object, $ignoreInheritance = false) {
        $inheritanceBackup = null;
        if($ignoreInheritance) {
            $inheritanceBackup = AbstractObject::getGetInheritedValues();
            AbstractObject::setGetInheritedValues(false);
        }

        $value = null;

        $getter = "get" . $this->fieldDefinition->getName();
        $valueObject = $object->$getter();
        if($valueObject) {
            $valueObject = new \DateTime("0000-01-01T" . $valueObject);
            $value = $valueObject->format(\DateTime::ISO8601);
        }

        if($ignoreInheritance) {
            AbstractObject::setGetInheritedValues($inheritanceBackup);
        }

        return $value;
    }


}
