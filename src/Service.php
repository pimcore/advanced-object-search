<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


namespace AdvancedObjectSearchBundle;

use AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter\DefaultAdapter;
use AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter\IFieldDefinitionAdapter;
use AdvancedObjectSearchBundle\Filter\FieldSelectionInformation;
use AdvancedObjectSearchBundle\Filter\FilterEntry;
use AdvancedObjectSearchBundle\Tools\Installer;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\WildcardQuery;
use ONGR\ElasticsearchDSL\Search;
use Pimcore\Bundle\AdminBundle\Security\User\TokenStorageUserResolver;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Definition;
use Pimcore\Model\User;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Service {

    /**
     * @var User
     */
    protected $user;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Client
     */
    protected $esClient;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Service constructor.
     * @param LoggerInterface $logger
     * @param TokenStorageUserResolver $userResolver
     * @param Client $esClient
     * @param ContainerInterface $container
     */
    public function __construct(LoggerInterface $logger, TokenStorageUserResolver $userResolver, Client $esClient, ContainerInterface $container)
    {
        $this->user = $userResolver->getUser();
        $this->logger = $logger;
        $this->esClient = $esClient;
        $this->container = $container;
    }

    /**
     * returns field definition adapter for given field definition
     *
     * @param ClassDefinition\Data $fieldDefinition
     * @param bool $considerInheritance
     * @return IFieldDefinitionAdapter
     */
    public function getFieldDefinitionAdapter(ClassDefinition\Data $fieldDefinition, bool $considerInheritance) {
        $adapter = null;

        try {
            $adapter = $this->container->get("bundle.advanced_object_search.filter." . $fieldDefinition->fieldtype);
        } catch (\Exception $exception) {
            $adapter = $this->container->get("bundle.advanced_object_search.filter.default");
        }

        $adapter->setConsiderInheritance($considerInheritance);
        $adapter->setFieldDefinition($fieldDefinition);

        return $adapter;
    }

    /**
     * returns selectable fields with their type information for search frontend
     *
     * @param ClassDefinition|Definition $definition
     *
     * @return FieldSelectionInformation[]
     */
    public function getFieldSelectionInformationForClassDefinition($definition, $allowInheritance = false) {

        $fieldSelectionInformationEntries = [];

        $fieldDefinitions = $definition->getFieldDefinitions();
        foreach($fieldDefinitions as $fieldDefinition) {
            $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition, $allowInheritance);
            $fieldSelectionInformationEntries = array_merge($fieldSelectionInformationEntries, $fieldDefinitionAdapter->getFieldSelectionInformation());
        }

        return $fieldSelectionInformationEntries;
    }

    /**
     * returns index name for given class name
     *
     * @param $classname string
     * @return string
     */
    public function getIndexName($classname) {
        $config = AdvancedObjectSearchBundle::getConfig();
        return $config['index-prefix'] . strtolower($classname);
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
            $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition, $objectClass->getAllowInherit());
            list($key, $mappingEntry) = $fieldDefinitionAdapter->getESMapping();
            $mappingProperties[$key] = $mappingEntry;
        }

        $mappingParams = [
            "index" => $this->getIndexName($objectClass->getName()),
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
     * updates mapping for given Object class
     *  - update mapping without recreating index
     *  - if that fails, delete and create index and try update mapping again and resets update queue
     *  - if that also fails, throws exception
     *
     * @param ClassDefinition $classDefinition
     */
    public function updateMapping(ClassDefinition $classDefinition) {

        //updating mapping without recreating index
        try {
            $this->doUpdateMapping($classDefinition);
            return true;
        } catch (\Exception $e) {
            $this->logger->info($e);
            //try recreating index
            $this->createIndex($classDefinition);
        }

        $this->doUpdateMapping($classDefinition);

        //only reset update queue when index was recreated
        $db = \Pimcore\Db::get();
        $db->executeQuery("UPDATE " . Installer::QUEUE_TABLE_NAME . " SET in_queue = 1 WHERE classId = ?", [$classDefinition->getId()]);
        return true;
    }

    /**
     * updates mapping for index - throws exception if not successful
     *
     * @param ClassDefinition $classDefinition
     */
    protected function doUpdateMapping(ClassDefinition $classDefinition) {
        $mapping = $this->generateMapping($classDefinition);
        $response = $this->esClient->indices()->putMapping($mapping);
        $this->logger->debug(json_encode($response));
    }

    /**
     * creates new elastic search index and deletes old one if exists
     *
     * @param ClassDefinition $classDefinition
     */
    protected function createIndex(ClassDefinition $classDefinition) {
        $indexName = $this->getIndexName($classDefinition->getName());

        try {
            $this->deleteIndex($classDefinition);
        } catch (\Exception $e) {
            $this->logger->debug($e);
        }

        try {
            $this->logger->info("Creating index $indexName for class " . $classDefinition->getName());
            $body = [
                'settings' => [
                    'index' => [
                        'mapping' => [
                            'nested_fields' => [
                                'limit' => 200
                            ]
                        ]
                    ]
                ]
            ];
            $response = $this->esClient->indices()->create(["index" => $indexName, "body" => $body]);
            $this->logger->debug(json_encode($response));
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }


    /**
     * deletes index
     *
     * @param ClassDefinition $classDefinition
     */
    public function deleteIndex(ClassDefinition $classDefinition) {
        $indexName = $this->getIndexName($classDefinition->getName());
        $this->logger->info("Deleting index $indexName for class " . $classDefinition->getName());
        $response = $this->esClient->indices()->delete(["index" => $indexName]);
        $this->logger->debug(json_encode($response));
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
            $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition, $object->getClass()->getAllowInherit());
            $data[$key] = $fieldDefinitionAdapter->getIndexData($object);
        }

        $checksum = crc32(json_encode($data));
        $data['o_checksum'] = $checksum;

        $params = [
            'index' => $this->getIndexName($object->getClassName()),
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
     * @param bool $ignoreUpdateQueue - if true doesn't fillup update queue for children objects
     */
    public function doUpdateIndexData(Concrete $object, $ignoreUpdateQueue = false) {

        $params = [
            'index' => $this->getIndexName($object->getClassName()),
            'type' =>  $object->getClassName(),
            'id' => $object->getId()
        ];

        try {
            $indexDocument = $this->esClient->get($params);
            $originalChecksum = $indexDocument["_source"]["o_checksum"];
        } catch(\Exception $e) {
            $this->logger->debug($e->getMessage());
            $originalChecksum = -1;
        }

        $indexUpdateParams = $this->getIndexData($object);

        if($indexUpdateParams['body']['o_checksum'] != $originalChecksum) {
            $response = $this->esClient->index($indexUpdateParams);
            $this->logger->info("Updates es index for data object " . $object->getId());
            $this->logger->debug(json_encode($response));

        } else {
            $this->logger->info("Not updating index for data object " . $object->getId() . " - nothing has changed.");
        }



        //updates update queue for object
        $this->updateUpdateQueueForDataObject($object);

        if(!$ignoreUpdateQueue) {
            //sets all children as dirty
            $this->fillupUpdateQueue($object);
        }
    }

    /**
     * Updates object queue - either inserts entry (if not exists) or updates in_queue flag to false
     *
     * @param Concrete $object
     */
    protected function updateUpdateQueueForDataObject(Concrete $object) {
        $db = \Pimcore\Db::get();

        //add object to update queue (if not exists) or set in_queue to false
        $currentEntry = $db->fetchRow("SELECT in_queue FROM " . Installer::QUEUE_TABLE_NAME . " WHERE o_id = ?", [$object->getId()]);
        if(!$currentEntry) {
            $db->insert(Installer::QUEUE_TABLE_NAME, ['o_id' => $object->getId(), 'classId' => $object->getClassId()]);
        } else if($currentEntry['in_queue']) {
            $db->executeQuery("UPDATE " . Installer::QUEUE_TABLE_NAME . " SET in_queue = 0, worker_timestamp = 0, worker_id = null WHERE o_id = ?", [$object->getId()]);
        }
    }


    /**
     * Delete given object from index
     *
     * @param Concrete $object
     */
    public function doDeleteFromIndex(Concrete $object) {

        $params = [
            'index' => $this->getIndexName($object->getClassName()),
            'type' =>  $object->getClassName(),
            'id' => $object->getId()
        ];

        try {
            $response = $this->esClient->delete($params);
            $this->logger->info("Deleting data object " . $object->getId() . " from es index.");
            $this->logger->debug(json_encode($response));
        } catch (Missing404Exception $e) {
            $this->logger->info("Cannot delete data object " . $object->getId() . " from es index because not found.");
        }

    }


    /**
     * fills update queue based on path of given object -> for all sub objects
     *
     * @param Concrete $object
     */
    public function fillupUpdateQueue(Concrete $object) {
        $db = \Pimcore\Db::get();
        //need check, if there are sub objects because update on empty result set is too slow
        $objects = $db->fetchCol("SELECT o_id FROM objects WHERE o_path LIKE ?", array($object->getFullPath() . "/%"));
        if($objects) {
            $updateStatement = "UPDATE " . Installer::QUEUE_TABLE_NAME . " SET in_queue = 1 WHERE o_id IN (".implode(',',$objects).")";
            $db->executeQuery($updateStatement);
        }
    }

    /**
     * processes elements in the queue for updating index data
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     * @return int number of entries
     */
    public function processUpdateQueue($limit = 200) {

        $workerId = uniqid();
        $workerTimestamp = time();
        $db = \Pimcore\Db::get();

        $db->executeQuery("UPDATE " . Installer::QUEUE_TABLE_NAME . " SET worker_id = ?, worker_timestamp = ? WHERE in_queue = 1 AND (ISNULL(worker_timestamp) OR worker_timestamp < ?) LIMIT " . intval($limit),
            [$workerId, $workerTimestamp, $workerTimestamp - 3000]);

        $entries = $db->fetchCol("SELECT o_id FROM " . Installer::QUEUE_TABLE_NAME . " WHERE worker_id = ?", array($workerId));

        if($entries) {
            foreach($entries as $objectId) {
                $this->logger->info("Worker $workerId updating index for element " . $objectId);
                $object = Concrete::getById($objectId);
                if($object) {
                    $this->doUpdateIndexData($object);
                }
            }
            return count($entries);
        } else {
            return 0;
        }
    }

    /**
     * returns if update queue is empty
     *
     * @return bool
     */
    public function updateQueueEmpty() {
        $db = \Pimcore\Db::get();
        $count = $db->fetchOne("SELECT count(*) FROM " . Installer::QUEUE_TABLE_NAME . " WHERE in_queue = 1");

        return $count == 0;
    }


    /**
     * @param ClassDefinition $objectClass
     * @param FilterEntry[] $filters
     *
     * either array of FilterEntry objects like
     *
     *   new \ESBackendSearch\FilterEntry("keywords", "testx", \ONGR\ElasticsearchDSL\Query\BoolQuery::SHOULD)
     *
     * or associative array like
     *
     *  [
     *      "fieldname" => "price",
     *      "filterEntryData" => ["gte" => 50.77,"lte" => 50.77],
     *      "operator" => \ONGR\ElasticsearchDSL\Query\BoolQuery::MUST
     *  ]
     *  --> gets converted into FilterEntry objects
     *
     * or special case for sub groups
     *
     *  [
     *      "fieldname" => "~~group~~",
     *      "filterEntryData" => [ normal filter array, either FilterEntry objects or associative array like above ],
     *      "operator" => \ONGR\ElasticsearchDSL\Query\BoolQuery::MUST
     *  ]
     *  --> gets converted into BoolQuery with corresponding FilterEntry objects recursively
     *
     *
     * @return \ONGR\ElasticsearchDSL\Search
     */
    public function getFilter(ClassDefinition $objectClass, array $filters) {
        $search = new Search();
        if(!empty($filters)) {
            $search->addPostFilter($this->doPopulateQuery(new BoolQuery(), $objectClass, $filters), BoolQuery::MUST);
        }
        return $search;
    }

    /**
     * populates bool query from given filters. for details to filters see comment on getFilter() method
     *
     * @param BoolQuery $query
     * @param ClassDefinition $objectClass
     * @param array $filters
     * @return BoolQuery
     * @throws \Exception
     */
    protected function doPopulateQuery(BoolQuery $query, ClassDefinition $objectClass, array $filters) {
        foreach($filters as $filterEntry) {

            $filterEntryObject = $this->buildFilterEntryObject($filterEntry);

            if($filterEntryObject->getFilterEntryData() instanceof BuilderInterface) {

                // add given builder interface without any further processing
                $query->add($filterEntryObject->getFilterEntryData(), $filterEntryObject->getOuterOperator());

            } else if($filterEntryObject->isGroup()) {

                $subQuery = new BoolQuery();
                $query->add($this->doPopulateQuery($subQuery, $objectClass, $filterEntryObject->getFilterEntryData()), $filterEntryObject->getOuterOperator());

            } else {
                $fieldDefinition = $objectClass->getFieldDefinition($filterEntryObject->getFieldname());
                $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition, $objectClass->getAllowInherit());

                if($filterEntryObject->getOperator() == FilterEntry::EXISTS || $filterEntryObject->getOperator() == FilterEntry::NOT_EXISTS) {

                    //add exists filter generated by filter definition adapter
                    $query->add($fieldDefinitionAdapter->getExistsFilter($filterEntryObject->getFilterEntryData(), $filterEntryObject->getIgnoreInheritance()), $filterEntryObject->getOuterOperator());

                } else {

                    //add query part generated by filter definition adapter
                    $query->add($fieldDefinitionAdapter->getQueryPart($filterEntryObject->getFilterEntryData(), $filterEntryObject->getIgnoreInheritance()), $filterEntryObject->getOuterOperator());

                }

            }
        }
        return $query;
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
            return new FilterEntry($filterEntry['fieldname'], $filterEntry['filterEntryData'], $filterEntry['operator'], $filterEntry['ignoreInheritance']);
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
            'index' => $this->getIndexName($classDefinition->getName()),
            'type' => $classDefinition->getName(),
            'body' => $search->toArray()
        ];

        $this->logger->info("Filter-Params: " . json_encode($params));

        return $this->esClient->search($params);
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
