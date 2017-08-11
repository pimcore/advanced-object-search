<?php

namespace ESBackendSearch\FieldDefinitionAdapter;


class Language extends Select implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "language";

}