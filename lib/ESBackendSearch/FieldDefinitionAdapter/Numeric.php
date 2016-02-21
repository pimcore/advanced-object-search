<?php

namespace ESBackendSearch\FieldDefinitionAdapter;

use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Filter\RangeFilter;
use ONGR\ElasticsearchDSL\Query\BoolQuery;
use ONGR\ElasticsearchDSL\Query\RangeQuery;
use ONGR\ElasticsearchDSL\Query\TermQuery;
use Pimcore\Model\Object\ClassDefinition\Data;

class Numeric extends DefaultAdapter implements IFieldDefinitionAdapter {

    /**
     * @return array
     */
    public function getESMapping() {
        return [
            $this->fieldDefinition->getName(),
            [
                'type' => 'float',
                'index' => 'not_analyzed'
            ]
        ];
    }


    /**
     * @param $fieldFilter
     *
     * filter field format as follows:
     *   - simple number like
     *       234.54   --> creates TermQuery
     *   - stdObject with gt, gte, lt, lte like
     *      (object) ["gte" => 40, "lte" => 45] --> creates RangeQuery
     *   - array of simple numbers and sttObjects like
     *      [ 234.54, (object) ["gte" => 40, "lte" => 45] ]
     *
     * @param string $path
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $path = "") {
        return parent::getQueryPart($fieldFilter, $path);
    }

    /**
     * @param $filterEntry
     * @param $path
     * @return BuilderInterface
     */
    protected function buildQueryEntry($filterEntry, $path) {
        if($filterEntry instanceof \stdClass) {
            return new RangeQuery($path . $this->fieldDefinition->getName(), get_object_vars($filterEntry));
        } else {
            return new TermQuery($path . $this->fieldDefinition->getName(), $filterEntry);
        }
    }

}