<?php

namespace ESBackendSearch\FieldDefinitionAdapter;

use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\BoolQuery;
use ONGR\ElasticsearchDSL\Query\NestedQuery;
use ONGR\ElasticsearchDSL\Query\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermsQuery;
use Pimcore\Model\Object\ClassDefinition\Data;
use Pimcore\Model\Object\Concrete;

class Href extends DefaultAdapter implements IFieldDefinitionAdapter {

    /**
     * @return array
     */
    public function getESMapping() {
        return [
            $this->fieldDefinition->getName(),
            [
                'type' => 'nested',
                'properties' => [
                    'type' =>  ["type" => "string", "index" => "not_analyzed"],
                    'subtype' =>  ["type" => "string", "index" => "not_analyzed"],
                    'id' => ["type" => "long"]
                ]
            ]
        ];
    }


    /**
     * @param $fieldFilter
     *
     * filter field format as follows:
     *   - simple sdtObject like
     *      (object) [
     *          'type' => 'object|asset|document'
     *          'id'   => 3242  ( or id-array like [3234,2432,24342,35435]
     *      ]
     *      --> creates TermQuery
     *
     *   - stdObject with sub query
     *      (object) [
     *         'type'      => 'object|asset|document'
     *         'className' => 'CLASSNAME' (optional only with type object),
     *         'filters'   => [ STANDARD FULLFEATURES FILTER ARRAY ]
     *      ]
     *       --> creates a sub query with given information, receives ids and then creates TermsQuery
     *
     *   - array of stdObjects
     *      --> creates BoolQuery with Term(s)Queries combined as SHOULD (min_should_match = 1)
     *
     * @param string $path
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $path = "")
    {
        return parent::getQueryPart($fieldFilter, $path);
    }

    protected function buildQueryEntry($filterEntry, $path)
    {
        if($filterEntry instanceof \stdClass) {

            $path = $path . $this->fieldDefinition->getName();

            if($filterEntry->type == "object") {

                $boolQuery = new BoolQuery();
                $boolQuery->add(new TermQuery($path . ".type", $filterEntry->type));

                if($filterEntry->id) {

                    if(is_array($filterEntry->id)) {
                        $boolQuery->add(new TermsQuery($path . ".id", $filterEntry->id));
                    } else {
                        $boolQuery->add(new TermQuery($path . ".id", $filterEntry->id));
                    }

                } else if($filterEntry->className && $filterEntry->filters) {

                    $results = $this->service->doFilter($filterEntry->className, $filterEntry->filters);
                    $ids = [];
                    foreach($results['hits']['hits'] as $hit) {
                        $ids[] = $hit['_id'];
                    }

                    $boolQuery->add(new TermsQuery($path . ".id", $ids));

                } else {
                    throw new \Exception("invalid filter entry definition " . print_r($filterEntry, true));
                }

                return new NestedQuery($path, $boolQuery);
            } else {
                throw new \Exception("filter type " . $filterEntry->type . " not implemented yet!");
            }
        } else {
            throw new \Exception("invalid filter entry for relations filter: " . print_r($filterEntry, true));
        }
    }


}