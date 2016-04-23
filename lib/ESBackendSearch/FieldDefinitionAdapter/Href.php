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
     * field type for search frontend
     *
     * @var string
     */
    protected $fieldType = "href";

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
     *   - simple array like
     *      [
     *          'type' => 'object|asset|document'
     *          'id'   => 3242  ( or id-array like [3234,2432,24342,35435]
     *      ]
     *      --> creates TermQuery
     *
     *   - array with sub query
     *      [
     *         'type'      => 'object|asset|document'
     *         'className' => 'CLASSNAME' (optional only with type object),
     *         'filters'   => [ STANDARD FULL FEATURED FILTER ARRAY ]
     *      ]
     *       --> creates a sub query with given information, receives ids and then creates TermsQuery
     *
     * @param string $path
     * @return BuilderInterface
     */
    public function getQueryPart($fieldFilter, $path = "")
    {
        if(is_array($fieldFilter)) {

            $path = $path . $this->fieldDefinition->getName();

            if($fieldFilter['type'] == "object") {

                $boolQuery = new BoolQuery();
                $boolQuery->add(new TermQuery($path . ".type", $fieldFilter['type']));

                if($fieldFilter['id']) {

                    if(is_array($fieldFilter['id'])) {
                        $boolQuery->add(new TermsQuery($path . ".id", $fieldFilter['id']));
                    } else {
                        $boolQuery->add(new TermQuery($path . ".id", $fieldFilter['id']));
                    }

                } else if($fieldFilter['className'] && $fieldFilter['filters']) {

                    $results = $this->service->doFilter($fieldFilter['className'], $fieldFilter['filters'], "");
                    $ids = [];
                    foreach($results['hits']['hits'] as $hit) {
                        $ids[] = $hit['_id'];
                    }

                    $boolQuery->add(new TermsQuery($path . ".id", $ids));

                } else {
                    throw new \Exception("invalid filter entry definition " . print_r($fieldFilter, true));
                }

                return new NestedQuery($path, $boolQuery);
            } else {
                throw new \Exception("filter type " . $fieldFilter['type'] . " not implemented yet!");
            }
        } else {
            throw new \Exception("invalid filter entry for relations filter: " . print_r($fieldFilter, true));
        }
    }



}