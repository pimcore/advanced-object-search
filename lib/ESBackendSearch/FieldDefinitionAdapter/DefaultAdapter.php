<?php

namespace ESBackendSearch\FieldDefinitionAdapter;

use ESBackendSearch\FieldSelectionInformation;
use ESBackendSearch\FilterEntry;
use ESBackendSearch\Service;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\BoolQuery;
use ONGR\ElasticsearchDSL\Query\ExistsQuery;
use ONGR\ElasticsearchDSL\Query\QueryStringQuery;
use Pimcore\Model\Object\ClassDefinition\Data;
use Pimcore\Model\Object\Concrete;

class DefaultAdapter implements IFieldDefinitionAdapter {

    /**
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "default";

    /**
     * @var Data
     */
    protected $fieldDefinition;

    /**
     * @var Service
     */
    protected $service;

    /**
     * DefaultAdapter constructor.
     * @param Data $fieldDefinition
     */
    public function __construct(Data $fieldDefinition, Service $service) {
        $this->fieldDefinition = $fieldDefinition;
        $this->service = $service;
    }

    /**
     * @return array
     */
    public function getESMapping() {
        return [
            $this->fieldDefinition->getName(),
            [
                'type' => 'string',
                'fields' => [
                    "raw" =>  [ "type" => "string", "index" => "not_analyzed" ]
                ]
                //'index' => 'not_analyzed'
            ]
        ];
    }

    /**
     * @param Concrete $object
     * @return mixed
     */
    public function getIndexData($object) {
        $value = $this->fieldDefinition->getForWebserviceExport($object);
        if($value) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param $fieldFilter
     *
     * filter field format as follows:
     *   - simple string like
     *       "filter for value"  --> creates QueryStringQuery
     *
     * @param string $path
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $path = "") {

        return new QueryStringQuery($fieldFilter, ["fields" => [$path . $this->fieldDefinition->getName()]]);

    }

    public function getExistsFilter($fieldFilter, $path = "")
    {
        return new ExistsQuery($path . $this->fieldDefinition->getName());
    }

    /**
     * returns selectable fields with their type information for search frontend
     *
     * @return FieldSelectionInformation[]
     */
    public function getFieldSelectionInformation()
    {
        return [new FieldSelectionInformation(
            $this->fieldDefinition->getName(),
            $this->fieldDefinition->getTitle(),
            $this->fieldType, ['operators' => [BoolQuery::MUST, BoolQuery::SHOULD, BoolQuery::MUST_NOT, FilterEntry::EXISTS, FilterEntry::NOT_EXISTS]
        ])];
    }
}