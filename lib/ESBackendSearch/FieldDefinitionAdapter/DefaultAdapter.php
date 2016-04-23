<?php

namespace ESBackendSearch\FieldDefinitionAdapter;

use ESBackendSearch\FieldSelectionInformation;
use ESBackendSearch\Service;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\BoolQuery;
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
                //'index' => 'not_analyzed'
            ]
        ];
    }

    /**
     * @param Concrete $object
     * @return mixed
     */
    public function getIndexData($object) {
        return $this->fieldDefinition->getForWebserviceExport($object);
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

        /*
        if(is_array($fieldFilter)) {
            $boolQuery = new BoolQuery();
            $boolQuery->addParameter("minimum_should_match", 1);
            foreach($fieldFilter as $filterEntry) {
                $boolQuery->add($this->buildQueryEntry($filterEntry, $path), BoolQuery::SHOULD);
            }
            return $boolQuery;
        } else {
            return $this->buildQueryEntry($fieldFilter, $path);
        }
*/
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
            $this->fieldType, ['operators' => ['must', 'should', 'must_not']
        ])];
    }
}