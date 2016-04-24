<?php

namespace ESBackendSearch;

use ESBackendSearch\FieldDefinitionAdapter\DefaultAdapter;
use ESBackendSearch\FieldDefinitionAdapter\IFieldDefinitionAdapter;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\BoolQuery;
use ONGR\ElasticsearchDSL\Query\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\WildcardQuery;
use ONGR\ElasticsearchDSL\Search;
use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\User;

class Service {

    /**
     * @var User
     */
    protected $user;

    /**
     * Service constructor.
     * @param User|null $user  - if user is set, filtering considers user permissions
     */
    public function __construct(User $user = null)
    {
        $this->user = $user;
    }

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

                // add given builder interface without any further processing
                $search->addFilter($filterEntryObject->getFilterEntryData(), $filterEntryObject->getOuterOperator());

            } else {
                $fieldDefinition = $objectClass->getFieldDefinition($filterEntryObject->getFieldname());
                $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition);

                if($filterEntryObject->getOperator() == FilterEntry::EXISTS || $filterEntryObject->getOperator() == FilterEntry::NOT_EXISTS) {

                    //add exists filter generated by filter definition adapter
                    $search->addFilter($fieldDefinitionAdapter->getExistsFilter($filterEntryObject->getFilterEntryData()), $filterEntryObject->getOuterOperator());

                } else {

                    //add query part generated by filter definition adapter
                    $search->addFilter($fieldDefinitionAdapter->getQueryPart($filterEntryObject->getFilterEntryData()), $filterEntryObject->getOuterOperator());

                }

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
     * @param $classId
     * @param FilterEntry $filters
     *
     * @param string|BuilderInterface $fullTextQuery
     * @return array
     */
    public function doFilter($classId, array $filters, $fullTextQuery, $from = null, $size = null) {
        $client = Plugin::getESClient();

        $classDefinition = \Pimcore\Model\Object\ClassDefinition::getById($classId);

        $search = $this->getFilter($classDefinition, $filters);

        if($fullTextQuery instanceof BuilderInterface) {
            $search->addQuery($fullTextQuery);
        } else if(!empty($fullTextQuery)) {
            $search->addQuery(new QueryStringQuery($fullTextQuery));
        }

        if($size) {
            $search->setSize($size);
        }
        if($from) {
            $search->setFrom($from);
        }

        if($this->user) {
            $this->addPermissionsExcludeFilter($search);
        }

        $params = [
            'index' => strtolower($classDefinition->getName()),
            'type' => $classDefinition->getName(),
            'body' => $search->toArray()
        ];

        \Logger::info("Filter-Params: " . json_encode($params));

        return $client->search($params);
    }

    /**
     * adds filter to exclude forbidden paths
     *
     * @param Search $search
     * @throws \Exception
     */
    protected function addPermissionsExcludeFilter(Search $search) {
        //exclude forbidden objects
        if (!$this->user->isAllowed("objects")) {
            throw new \Exception("User not allowed to search for objects");
        } else {
            $forbiddenObjectPaths = \Pimcore\Model\Element\Service::findForbiddenPaths("object", $this->user);
            if (count($forbiddenObjectPaths) > 0) {
                $boolFilter = new BoolQuery();

                for ($i = 0; $i < count($forbiddenObjectPaths); $i++) {
                    $boolFilter->add(new WildcardQuery("path", $forbiddenObjectPaths[$i] . "*"), BoolQuery::MUST);
                }

                $search->addFilter($boolFilter, BoolQuery::MUST_NOT);
            }
        }
    }

    public function extractTotalCountFromResult($searchResult) {
        return $searchResult['hits']['total'];
    }

    public function extractIdsFromResult($searchResult) {
        $ids = [];

        foreach($searchResult['hits']['hits'] as $hit) {
            $ids[] = $hit['_id'];
        }

        return $ids;
    }
}