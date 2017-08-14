<?php

namespace AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter;

class Multihref extends Href implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "multihref";

}
