<?php

namespace ESBackendSearch\FieldDefinitionAdapter;


class Date extends Datetime implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "date";

}