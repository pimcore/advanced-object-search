<?php

namespace ESBackendSearch\FieldDefinitionAdapter;


class Multiselect extends Select implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "multiselect";

}