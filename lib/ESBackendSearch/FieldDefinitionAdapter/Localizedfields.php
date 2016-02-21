<?php

namespace ESBackendSearch\FieldDefinitionAdapter;

use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\BoolQuery;
use ONGR\ElasticsearchDSL\Query\NestedQuery;
use Pimcore\Config;
use Pimcore\Model\Object\ClassDefinition\Data;
use Pimcore\Model\Object\Concrete;
use Pimcore\Tool;

class Localizedfields extends DefaultAdapter implements IFieldDefinitionAdapter {

    /**
     * @var Data\Localizedfields
     */
    protected $fieldDefinition;


    /**
     * @return array
     */
    public function getESMapping() {

        $children = $this->fieldDefinition->getChilds();
        $childMappingProperties = [];
        foreach($children as $child) {
            $fieldDefinitionAdapter = $this->service->getFieldDefinitionAdapter($child);
            list($key, $mappingEntry) = $fieldDefinitionAdapter->getESMapping();
            $childMappingProperties[$key] = $mappingEntry;
        }

        $mappingProperties = [];
        $languages = Tool::getValidLanguages();
        foreach($languages as $language) {
            $mappingProperties[$language] = [
                'type' => 'nested',
                'properties' => $childMappingProperties
            ];
        }

        return [
            $this->fieldDefinition->getName(),
            [
                'type' => 'nested',
                'properties' => $mappingProperties
            ]
        ];
    }


    /**
     * @param Concrete $object
     * @return array
     */
    public function getIndexData($object)
    {
        $webserviceData = $this->fieldDefinition->getForWebserviceExport($object);
        $data = [];

        foreach($webserviceData as $entry) {
            $data[$entry->language][$entry->name] = $entry->value;
        }
        return $data;
    }


    /**
     * @param $fieldFilter
     *
     * filter field format as follows:
     *  stdObject with language as key and languageFilter array as values like
     *   (object) [
     *      'en' => [
     *         FIELD NAME => LANGUAGE FILTER
     *      ]
     *    ]
     *
     *  LANGUAGE FILTER format is like normal filter field format, e.g. as follows
     *     - simple string like
     *         "filter for value"
     *     - array of simple strings like
     *        [ "filter for value1", "filter for value2" ]
     *  see other FieldDefinitionAdapters for details
     *
     *
     * @param string $path
     * @return BoolQuery
     */
    public function getQueryPart($fieldFilter, $path = "") {

        $languageQueries = [];

        foreach($fieldFilter as $language => $languageFilters) {
            $path = $this->fieldDefinition->getName() . "." . $language;
            $languageBoolQuery = new BoolQuery();

            foreach($languageFilters as $field => $localizedFieldFilter) {

                $fieldDefinition = $this->fieldDefinition->getFielddefinition($field);
                $adapter = $this->service->getFieldDefinitionAdapter($fieldDefinition);

                $languageBoolQuery->add($adapter->getQueryPart($localizedFieldFilter, $path . "."));
            }

            $languageQueries[] = new NestedQuery($path, $languageBoolQuery);
        }

        if(count($languageQueries) == 1) {
            return $languageQueries[0];
        } else {
            $boolQuery = new BoolQuery();
            foreach($languageQueries as $query) {
                 $boolQuery->add($query);
            }
            return $boolQuery;
        }
    }

}