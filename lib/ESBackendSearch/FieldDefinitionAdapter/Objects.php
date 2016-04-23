<?php

namespace ESBackendSearch\FieldDefinitionAdapter;

use Pimcore\Model\Object\ClassDefinition\Data;

class Objects extends Href implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "objects";

}