<?php

namespace AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter;

use Pimcore\Model\Object\ClassDefinition\Data;

class Multihref extends Href implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "multihref";

}
