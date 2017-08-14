<?php

namespace AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter;


class Language extends Select implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "language";

}
