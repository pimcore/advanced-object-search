<?php

namespace ESBackendSearch;

use ESBackendSearch\FieldDefinitionAdapter\DefaultAdapter;
use ESBackendSearch\FieldDefinitionAdapter\IFieldDefinitionAdapter;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\QueryStringQuery;
use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Model\Object\Concrete;

class Service {

    /**
     * returns field definition adapter for given field definition
     *
     * @param ClassDefinition\Data $fieldDefinition
     * @return IFieldDefinitionAdapter
     */
    public function getFieldDefinitionAdapter(ClassDefinition\Data $fieldDefinition) {
        $adapter = null;
        $adapterClassName = '\\ESBackendSearch\\FieldDefinitionAdapter\\' . ucfirst($fieldDefinition->fieldtype);
        if(@class_exists($adapterClassName)) {
            $adapter = new $adapterClassName($fieldDefinition, $this);
        } else {
            $adapter = new DefaultAdapter($fieldDefinition, $this);
        }

        return $adapter;
    }

    /**
     * returns selectable fields with their type information for search frontend
     *
     * @param ClassDefinition $objectClass
     *
     * @return FieldSelectionInformation[]
     */
    public function getFieldSelectionInformationForClassDefinition(ClassDefinition $objectClass) {

        $fieldSelectionInformationEntries = [];

        $fieldDefinitions = $objectClass->getFieldDefinitions();
        foreach($fieldDefinitions as $fieldDefinition) {
            $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition);
            $fieldSelectionInformationEntries = array_merge($fieldSelectionInformationEntries, $fieldDefinitionAdapter->getFieldSelectionInformation());
        }

        return $fieldSelectionInformationEntries;
    }

    /**
     * generates and returns mapping for given class definition
     *
     * @param ClassDefinition $objectClass
     * @return array
     */
    public function generateMapping(ClassDefinition $objectClass) {
        $fieldDefinitions = $objectClass->getFieldDefinitions();

        $mappingProperties = [
            "o_id" => ["type" => "long"],
            "o_checksum" => ["type" => "long"],
            "type" => ["type" => "string", "index" => "not_analyzed"],
            "key" =>  ["type" => "string", "index" => "not_analyzed"],
            "path" => ["type" => "string", "index" => "not_analyzed"]
        ];

        foreach($fieldDefinitions as $fieldDefinition) {
            $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition);
            list($key, $mappingEntry) = $fieldDefinitionAdapter->getESMapping();
            $mappingProperties[$key] = $mappingEntry;
        }

        $mappingParams = [
            "index" => strtolower($objectClass->getName()),
            "type" => $objectClass->getName(),
            "body" => [
                $objectClass->getName() => [
                    "_source" => [
                        "enabled" => true
                    ],
                    "properties" => $mappingProperties
                ]
            ]
        ];


        return $mappingParams;

    }


    /**
     * returns index data array for given object
     *
     * @param Concrete $object
     * @return array
     */
    public function getIndexData(Concrete $object) {

        $data = [
            "o_id" => $object->getId(),
            "type" => $object->getClassName(),
            "key" => $object->getKey(),
            "path" => $object->getPath()
        ];

        foreach ($object->getClass()->getFieldDefinitions() as $key => $fieldDefinition) {
            $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition);
            $data[$key] = $fieldDefinitionAdapter->getIndexData($object);
        }

        $checksum = crc32(json_encode($data));
        $data['o_checksum'] = $checksum;

        $params = [
            'index' => strtolower($object->getClassName()),
            'type' =>  $object->getClassName(),
            'id' => $object->getId(),
            'body' => $data
        ];

        return $params;
    }


    /**
     * Updates index for given object
     *
     * @param Concrete $object
     */
    public function doUpdateIndexData(Concrete $object) {

        $client = Plugin::getESClient();

        $params = [
            'index' => strtolower($object->getClassName()),
            'type' =>  $object->getClassName(),
            'id' => $object->getId()
        ];

        try {
            $indexDocument = $client->get($params);
            $originalChecksum = $indexDocument["_source"]["o_checksum"];
        } catch(\Exception $e) {
            \Logger::debug($e->getMessage());
            $originalChecksum = -1;
        }

        $indexUpdateParams = $this->getIndexData($object);

        if($indexUpdateParams['body']['o_checksum'] != $originalChecksum) {
            $response = $client->index($indexUpdateParams);
            \Logger::info("Updates es index for object " . $object->getId());
            \Logger::debug(json_encode($response));

        } else {
            \Logger::info("Not updating index for object " . $object->getId() . " - nothing has changed.");
        }

    }


    /**
     * @param ClassDefinition $objectClass
     * @param FilterEntry[] $filters
     *
     * either array of FilterEntry objects like
     *   new \ESBackendSearch\FilterEntry("keywords", "testx", \ONGR\ElasticsearchDSL\Query\BoolQuery::SHOULD)
     * or associative array like
     *  [
     *      "fieldname" => "price",
     *      "filterEntryData" => ["gte" => 50.77,"lte" => 50.77],
     *      "operator" => \ONGR\ElasticsearchDSL\Query\BoolQuery::MUST
     *  ]
     *  --> gets converted into FilterEntry objects
     *
     *
     *
     * @return \ONGR\ElasticsearchDSL\Search
     */
    public function getFilter(ClassDefinition $objectClass, array $filters) {

        $search = new \ONGR\ElasticsearchDSL\Search();

        foreach($filters as $filterEntry) {

            $filterEntryObject = $this->buildFilterEntryObject($filterEntry);

            if($filterEntryObject->getFilterEntryData() instanceof BuilderInterface) {
                $search->addFilter($filterEntryObject->getFilterEntryData(), $filterEntryObject->getOperator());
            } else {
                $fieldDefinition = $objectClass->getFieldDefinition($filterEntryObject->getFieldname());
                $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition);
                $search->addFilter($fieldDefinitionAdapter->getQueryPart($filterEntryObject->getFilterEntryData()), $filterEntryObject->getOperator());
            }
        }

        return $search;

    }


    /**
     * checks filter entry and creates FilterEntry object if necessary
     *
     * @param $filterEntry
     * @return FilterEntry
     * @throws \Exception
     */
    public function buildFilterEntryObject($filterEntry) {
        if(is_array($filterEntry)) {
            return new FilterEntry($filterEntry['fieldname'], $filterEntry['filterEntryData'], $filterEntry['operator']);
        } else if($filterEntry instanceof FilterEntry) {
            return $filterEntry;
        } else {
            throw new \Exception("invalid filter entry: " . print_r($filterEntry, true));
        }
    }

    /**
     * @param $className
     * @param FilterEntry $filters
     *
     * @param string|BuilderInterface $fullTextQuery
     * @return array
     */
    public function doFilter($className, array $filters, $fullTextQuery) {
        $client = Plugin::getESClient();
        $search = $this->getFilter(\Pimcore\Model\Object\ClassDefinition::getByName($className), $filters);

        if($fullTextQuery instanceof BuilderInterface) {
            $search->addQuery($fullTextQuery);
        } else if(!empty($fullTextQuery)) {
            $search->addQuery(new QueryStringQuery($fullTextQuery));
        }

        $params = [
            'index' => strtolower($className),
            'type' => $className,
            'body' => $search->toArray()
        ];

        \Logger::info("Filter-Params: " . json_encode($params));

        return $client->search($params);
    }
}