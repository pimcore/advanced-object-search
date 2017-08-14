<?php

namespace AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter;


class User extends Select implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "user";

}
