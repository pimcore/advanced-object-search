<?php

namespace AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter;

use ESBackendSearch\FieldSelectionInformation;
use ESBackendSearch\Service;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\ExistsQuery;
use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Model\Object\ClassDefinition\Data;
use Pimcore\Model\Object\Concrete;

interface IFieldDefinitionAdapter {

    const ES_MAPPING_PROPERTY_STANDARD = "standard";
    const ES_MAPPING_PROPERTY_NOT_INHERITED = "notInherited";

    /**
     * IFieldDefinitionAdapter constructor.
     * @param Data $fieldDefinition
     * @param Service $service
     * @param bool $considerInheritance
     */
    public function __construct(Data $fieldDefinition, Service $service, bool $considerInheritance);

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
     * @param bool $ignoreInheritance - if true inheritance is not considered during query
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $ignoreInheritance = false, $path = "");

    /**
     * @param $fieldFilter - see concrete implementations for format
     * @param bool $ignoreInheritance - if true inheritance is not considered during query
     * @param string $path - sub path for nested objects (only needed internally)
     * @return ExistsQuery
     */
    public function getExistsFilter($fieldFilter, $ignoreInheritance = false, $path = "");

    /**
     * returns selectable fields with their type information for search frontend
     *
     * @return FieldSelectionInformation[]
     */
    public function getFieldSelectionInformation();
}
