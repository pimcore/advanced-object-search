<?php

namespace ESBackendSearch\FieldDefinitionAdapter;

use ESBackendSearch\FieldSelectionInformation;
use ESBackendSearch\Service;
use ONGR\ElasticsearchDSL\BuilderInterface;
use Pimcore\Model\Object\ClassDefinition\Data;
use Pimcore\Model\Object\Concrete;

interface IFieldDefinitionAdapter {

    /**
     * IFieldDefinitionAdapter constructor.
     * @param Data $fieldDefinition
     * @param Service $service
     */
    public function __construct(Data $fieldDefinition, Service $service);

    /**
     * @return array
     */
    public function getESMapping();

    /**
     * @param Concrete $object
     * @return array
     */
    public function getIndexData($object);

    /**
     * @param $fieldFilter - see concrete implementations for format
     * @param string $path - sub path for nested objects (only needed internally)
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $path = "");

    
    public function getExistsFilter($fieldFilter, $path = "");

    /**
     * returns selectable fields with their type information for search frontend
     *
     * @return FieldSelectionInformation[]
     */
    public function getFieldSelectionInformation();
}