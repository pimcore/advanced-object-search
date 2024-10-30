<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace AdvancedObjectSearchBundle;

use AdvancedObjectSearchBundle\Event\AdvancedObjectSearchEvents;
use AdvancedObjectSearchBundle\Event\FilterSearchEvent;
use AdvancedObjectSearchBundle\Filter\FieldDefinitionAdapter\FieldDefinitionAdapterInterface;
use AdvancedObjectSearchBundle\Filter\FieldSelectionInformation;
use AdvancedObjectSearchBundle\Filter\FilterEntry;
use AdvancedObjectSearchBundle\Tools\IndexConfigService;
use Doctrine\DBAL\Exception as DoctrineDbalException;
use Exception;
use ONGR\ElasticsearchDSL\BuilderInterface;
use ONGR\ElasticsearchDSL\Query\Compound\BoolQuery;
use ONGR\ElasticsearchDSL\Query\FullText\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\TermLevel\WildcardQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use OpenSearch\Client as OpenSearchClient;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Fieldcollection\Definition;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Model\User;
use Pimcore\Security\User\TokenStorageUserResolver;
use Pimcore\Translation\Translator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Service
{
    protected ?User $user;

    protected array $coreFieldsConfig;

    private string $indexNamePrefix;

    public function __construct(
        private LoggerInterface $logger,
        private TokenStorageUserResolver $userResolver,
        private ContainerInterface $filterLocator,
        private EventDispatcherInterface $eventDispatcher,
        private Translator $translator,
        private IndexConfigService $indexConfigService,
        private OpenSearchClient $openSearchClient
    ) {
        $this->user = $this->userResolver->getUser();
        $this->indexNamePrefix = $indexConfigService->getIndexNamePrefix();
    }

    /**
     * returns field definition adapter for given field definition
     *
     * @param ClassDefinition\Data $fieldDefinition
     * @param bool $considerInheritance
     *
     * @return FieldDefinitionAdapterInterface
     *
     * @throws Exception
     */
    public function getFieldDefinitionAdapter(ClassDefinition\Data $fieldDefinition, bool $considerInheritance)
    {
        $fieldType = $fieldDefinition->getFieldType();

        if ($this->filterLocator->has($fieldType)) {
            $adapter = $this->filterLocator->get($fieldType);
        } else {
            $adapter = $this->filterLocator->get('default');
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
    public function getFieldSelectionInformationForClassDefinition($definition, $allowInheritance = false)
    {
        $fieldSelectionInformationEntries = [];

        $fieldDefinitions = array_merge(
            $this->getCoreFieldDefinitions(),
            $definition->getFieldDefinitions()
        );

        foreach ($fieldDefinitions as $fieldDefinition) {
            if ($definition instanceof ClassDefinition && $this->isExcludedField($definition->getName(), $fieldDefinition->getName())) {
                continue;
            }

            $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition, $allowInheritance);

            $fieldSelectionInformationEntries = array_merge($fieldSelectionInformationEntries, $fieldDefinitionAdapter->getFieldSelectionInformation());
        }

        return $fieldSelectionInformationEntries;
    }

    /**
     * returns index name for given class name
     *
     * @param string $classname
     *
     * @return string
     */
    public function getIndexName($classname)
    {
        return $this->indexNamePrefix . strtolower($classname);
    }

    /**
     * returns core fields index data array for given object
     */
    public function getCoreFieldsIndexData(Concrete $object)
    {
        $date = new \DateTime();

        return [
            'id' => $object->getId(),
            'index' => $object->getIndex(),
            'creationDate' => $date->setTimestamp($object->getCreationDate())->format(\DateTimeInterface::ATOM),
            'modificationDate' => $date->setTimestamp($object->getModificationDate())->format(\DateTimeInterface::ATOM),
            'published' => $object->getPublished(),
            'type' => $object->getType(),
            'className' => $object->getClassName(),
            'key' => $object->getKey(),
            'path' => $object->getPath()
        ];
    }

    /**
     * @param array $coreFieldsConfig
     */
    public function setCoreFieldsConfig(array $coreFieldsConfig)
    {
        $this->coreFieldsConfig = $coreFieldsConfig;
    }

    /**
     * @param string|null $fieldName
     *
     * @return array
     */
    public function getCoreFieldsConfig($fieldName = null)
    {
        if ($fieldName !== null && array_key_exists($fieldName, $this->coreFieldsConfig)) {
            return $this->coreFieldsConfig[$fieldName];
        }

        return $this->coreFieldsConfig;
    }

    /**
     * @param string $name
     * @param array $data
     *
     * @return ClassDefinition\Data
     */
    public function getCoreFieldDefinition($name, array $data)
    {
        $title = $this->translator->trans($data['title'], [], 'admin');
        $values = isset($data['values']) ? $data['values'] : [];

        /** @var ClassDefinition\Data $fieldDefinition */
        $fieldDefinition = new $data['fieldDefinition']();

        $fieldDefinition->setName($name);
        $fieldDefinition->setTitle($title);
        $fieldDefinition->setValues($values);

        return $fieldDefinition;
    }

    /**
     * @return array
     */
    public function getCoreFieldDefinitions()
    {
        $coreConfig = $this->getCoreFieldsConfig();

        $fieldDefinitions = [];

        foreach ($coreConfig as $fieldName => $properties) {
            if (!isset($properties['fieldDefinition'])) {
                continue;
            }

            $fieldDefinitions[$fieldName] = $this->getCoreFieldDefinition($fieldName, $properties);
        }

        return $fieldDefinitions;
    }

    /**
     * generates and returns mapping for given class definition
     *
     * @param ClassDefinition $objectClass
     *
     * @return array
     */
    public function generateMapping(ClassDefinition $objectClass)
    {
        $fieldDefinitions = $objectClass->getFieldDefinitions();

        $mappingProperties = array_map(
            function ($fieldProperties) {
                return [
                    'type' => $fieldProperties['type']
                ];
            },
            $this->getCoreFieldsConfig()
        );

        foreach ($fieldDefinitions as $fieldDefinition) {
            if ($this->isExcludedField($objectClass->getName(), $fieldDefinition->getName())) {
                continue;
            }

            $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition, $objectClass->getAllowInherit());
            list($key, $mappingEntry) = $fieldDefinitionAdapter->getESMapping();
            $mappingProperties[$key] = $mappingEntry;
        }

        return [
            'index' => $this->getIndexName($objectClass->getName()),
            'body' => [
                '_source' => [
                    'enabled' => true
                ],
                'properties' => $mappingProperties
            ]
        ];
    }

    /**
     * updates mapping for given Object class
     *  - update mapping without recreating index
     *  - remove index if Object class added to excluding list
     *  - if that fails, delete and create index and try update mapping again and resets update queue
     *  - if that also fails, throws exception
     *
     * @param ClassDefinition $classDefinition
     *
     * @return bool
     *
     * @throws Exception
     */
    public function updateMapping(ClassDefinition $classDefinition)
    {
        if ($this->isExcludedClass($classDefinition->getName())) {
            if ($this->openSearchClient->indices()->exists(['index' => $this->getIndexName($classDefinition->getName())])) {
                try {
                    $this->deleteIndex($classDefinition);
                } catch (Exception $e) {
                    $this->logger->error($e);
                }
            }

            return true;
        }

        if (!$this->openSearchClient->indices()->exists(['index' => $this->getIndexName($classDefinition->getName())])) {
            $this->createIndex($classDefinition);
        }

        try {
            //updating mapping without recreating index
            $this->doUpdateMapping($classDefinition);

            return true;
        } catch (Exception $e) {
            $this->logger->info($e);
            //try recreating index
            $this->createIndex($classDefinition);
        }

        $this->doUpdateMapping($classDefinition);

        //only reset update queue when index was recreated
        $db = Db::get();
        $db->executeQuery('UPDATE ' . Installer::QUEUE_TABLE_NAME . ' SET in_queue = 1 WHERE classId = ?', [$classDefinition->getId()]);

        return true;
    }

    /**
     * updates mapping for index - throws exception if not successful
     *
     * @param ClassDefinition $classDefinition
     *
     * @throws Exception
     */
    protected function doUpdateMapping(ClassDefinition $classDefinition)
    {
        $mapping = $this->generateMapping($classDefinition);
        $this->openSearchClient->indices()->putMapping($mapping);
    }

    /**
     * creates new search index and deletes old one if exists
     *
     * @param ClassDefinition $classDefinition
     */
    protected function createIndex(ClassDefinition $classDefinition)
    {
        $indexName = $this->getIndexName($classDefinition->getName());

        try {
            $this->deleteIndex($classDefinition);
        } catch (Exception $e) {
            $this->logger->error($e);
        }

        try {
            $this->logger->info("Creating index $indexName for class " . $classDefinition->getName());

            $this->openSearchClient->indices()->create([
                'index' => $indexName,
                'body' => [
                    'settings' => [
                        'index' => [
                            'mapping' => [
                                'nested_fields' => [
                                    'limit' => (int) $this->indexConfigService->getIndexConfiguration(
                                        'nested_fields_limit'
                                    )
                                ],
                                'total_fields' => [
                                    'limit' => (int) $this->indexConfigService->getIndexConfiguration(
                                        'total_fields_limit'
                                    )
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
        } catch (Exception $e) {
            $this->logger->error($e);
        }
    }

    public function deleteIndex(ClassDefinition $classDefinition): void
    {
        $indexName = $this->getIndexName($classDefinition->getName());
        $this->logger->info("Deleting index $indexName for class " . $classDefinition->getName());
        $this->openSearchClient->indices()->delete(['index' => $indexName]);
    }

    /**
     * returns index data array for given object
     *
     * @param Concrete $object
     *
     * @return array
     *
     * @throws Exception
     */
    public function getIndexData(Concrete $object)
    {
        $data = $this->getCoreFieldsIndexData($object);

        foreach ($object->getClass()->getFieldDefinitions() as $key => $fieldDefinition) {
            if ($this->isExcludedField($object->getClassName(), $key)) {
                continue;
            }

            $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition, $object->getClass()->getAllowInherit());
            $data[$key] = $fieldDefinitionAdapter->getIndexData($object);
        }

        $checksum = crc32(json_encode($data));
        $data['checksum'] = $checksum;

        return [
            'index' => $this->getIndexName($object->getClassName()),
            'id' => $object->getId(),
            'body' => $data
        ];
    }

    /**
     * Updates index for given object
     *
     * @param Concrete $object
     * @param bool $ignoreUpdateQueue - if true doesn't fillup update queue for children objects
     *
     * @return bool
     *
     * @throws Exception
     */
    public function doUpdateIndexData(Concrete $object, $ignoreUpdateQueue = false)
    {
        if ($this->isExcludedClass($object->getClassName())) {
            return true;
        }

        $params = [
            'index' => $this->getIndexName($object->getClassName()),
            'id' => $object->getId()
        ];

        try {
            $indexDocument = $this->openSearchClient->get($params);
            $originalChecksum = $indexDocument['_source']['checksum'] ?? -1;
        } catch (Exception $e) {
            $this->logger->debug($e->getMessage());
            $originalChecksum = -1;
        }

        $indexUpdateParams = $this->getIndexData($object);

        if ($indexUpdateParams['body']['checksum'] != $originalChecksum) {
            $this->openSearchClient->index($indexUpdateParams);
            $this->logger->info('Updates es index for data object ' . $object->getId());
            $this->openSearchClient->index($indexUpdateParams);
        } else {
            $this->logger->info('Not updating index for data object ' . $object->getId() . ' - nothing has changed.');
        }

        //updates update queue for object
        $this->updateUpdateQueueForDataObject($object);

        if (!$ignoreUpdateQueue) {
            //sets all children as dirty
            $this->fillupUpdateQueue($object);
        }

        return true;
    }

    /**
     * Updates object queue - either inserts entry (if not exists) or updates in_queue flag to false
     *
     * @param Concrete $object
     *
     * @throws DoctrineDbalException
     */
    protected function updateUpdateQueueForDataObject(Concrete $object)
    {
        $db = Db::get();
        //add object to update queue (if not exists) or set in_queue to false
        $currentEntry = $db->fetchAssociative('SELECT in_queue FROM ' . Installer::QUEUE_TABLE_NAME . ' WHERE `id` = ?', [$object->getId()]);
        if (!$currentEntry) {
            $db->insert(Installer::QUEUE_TABLE_NAME, ['id' => $object->getId(), 'classId' => $object->getClassId()]);
        } elseif ($currentEntry['in_queue']) {
            $db->executeQuery('UPDATE ' . Installer::QUEUE_TABLE_NAME . ' SET in_queue = 0, worker_timestamp = 0, worker_id = null WHERE `id` = ?', [$object->getId()]);
        }
    }

    public function doDeleteFromIndex(Concrete $object): void
    {
        if ($this->isExcludedClass($object->getClassName())) {
            return;
        }

        $params = [
            'index' => $this->getIndexName($object->getClassName()),
            'id' => $object->getId()
        ];

        $this->logger->info('Deleting data object ' . $object->getId() . ' from es index.');
        $this->openSearchClient->delete($params);
    }

    /**
     * fills update queue based on path of given object -> for all sub objects
     *
     * @param Concrete $object
     *
     * @throws DoctrineDbalException
     */
    public function fillupUpdateQueue(Concrete $object)
    {
        $db = Db::get();
        $idField = DataObjectService::getVersionDependentDatabaseColumnName('id');
        $pathField = DataObjectService::getVersionDependentDatabaseColumnName('path');
        //need check, if there are sub objects because update on empty result set is too slow

        $objects = $db->fetchFirstColumn('SELECT `'. $idField .'` FROM objects WHERE `' . $pathField . '` LIKE ?', [$object->getFullPath() . '/%']);
        if ($objects) {
            $updateStatement = 'UPDATE ' . Installer::QUEUE_TABLE_NAME . ' SET in_queue = 1 WHERE `id` IN ('.implode(',', $objects).')';
            $db->executeQuery($updateStatement);
        }
    }

    /**
     * processes elements in the queue for updating index data
     * can be run in parallel since each thread marks the entries it is working on and only processes these entries
     *
     * @param int $limit
     *
     * @return int number of entries
     *
     * @throws DoctrineDbalException
     */
    public function processUpdateQueue($limit = 200)
    {
        $workerId = uniqid('', true);
        $entries = $this->initUpdateQueue($workerId, $limit);
        if (!empty($entries)) {
            return $this->doProcessUpdateQueue($workerId, $entries);
        }

        return 0;
    }

    /**
     * @param string $workerId
     * @param int $limit
     *
     * @return array
     *
     * @throws DoctrineDbalException
     */
    public function initUpdateQueue(string $workerId, int $limit = 200): array
    {
        $workerTimestamp = time();
        $db = Db::get();

        $db->executeQuery('UPDATE ' . Installer::QUEUE_TABLE_NAME . ' SET worker_id = ?, worker_timestamp = ? WHERE in_queue = 1 AND (ISNULL(worker_timestamp) OR worker_timestamp < ?) LIMIT ' . intval($limit),
            [$workerId, $workerTimestamp, $workerTimestamp - 3000]);

        return $db->fetchFirstColumn('SELECT `id` FROM ' . Installer::QUEUE_TABLE_NAME . ' WHERE worker_id = ?', [$workerId]);
    }

    /**
     * @param string $workerId
     * @param array $entries
     *
     * @return int
     *
     * @throws DoctrineDbalException
     */
    public function doProcessUpdateQueue(string $workerId, array $entries): int
    {
        $db = Db::get();

        foreach ($entries as $objectId) {
            $this->logger->info("Worker $workerId updating index for element " . $objectId);
            $object = Concrete::getById($objectId);
            if ($object) {
                $this->doUpdateIndexData($object);
            } else {
                // Object no longer exists, remove from queue
                $db->executeQuery('DELETE FROM ' . Installer::QUEUE_TABLE_NAME . ' WHERE `id` = ?', [$objectId]);
            }
        }

        return count($entries);
    }

    /**
     * returns if update queue is empty
     *
     * @return bool
     *
     * @throws DoctrineDbalException
     */
    public function updateQueueEmpty()
    {
        $db = Db::get();
        $count = $db->fetchOne('SELECT count(*) FROM ' . Installer::QUEUE_TABLE_NAME . ' WHERE in_queue = 1');

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
     * @return \ONGR\ElasticsearchDSL\Search
     *
     * @throws Exception
     */
    public function getFilter(ClassDefinition $objectClass, array $filters)
    {
        $search = new Search();
        if (!empty($filters)) {
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
     *
     * @return BoolQuery
     *
     * @throws Exception
     */
    protected function doPopulateQuery(BoolQuery $query, ClassDefinition $objectClass, array $filters)
    {
        foreach ($filters as $filterEntry) {
            $filterEntryObject = $this->buildFilterEntryObject($filterEntry);

            if ($filterEntryObject->getFilterEntryData() instanceof BuilderInterface) {

                // add given builder interface without any further processing
                $query->add($filterEntryObject->getFilterEntryData(), $filterEntryObject->getOuterOperator());
            } elseif ($filterEntryObject->isGroup()) {
                $subQuery = new BoolQuery();
                $query->add($this->doPopulateQuery($subQuery, $objectClass, $filterEntryObject->getFilterEntryData()), $filterEntryObject->getOuterOperator());
            } else {
                $fieldDefinition = $objectClass->getFieldDefinition($filterEntryObject->getFieldname());

                $considerInheritance = $objectClass->getAllowInherit();

                if (! $fieldDefinition instanceof ClassDefinition\Data) {
                    $fieldName = $filterEntryObject->getFieldname();
                    $fieldDefinition = $this->getCoreFieldDefinition(
                        $fieldName,
                        $this->getCoreFieldsConfig($fieldName)
                    );
                    // skip inheritance for core fields
                    $considerInheritance = false;
                }

                $fieldDefinitionAdapter = $this->getFieldDefinitionAdapter($fieldDefinition, $considerInheritance);

                if ($filterEntryObject->getOperator() == FilterEntry::EXISTS || $filterEntryObject->getOperator() == FilterEntry::NOT_EXISTS) {

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
     * @param array|FilterEntry $filterEntry
     *
     * @return FilterEntry
     *
     * @throws Exception
     */
    public function buildFilterEntryObject($filterEntry)
    {
        if (is_array($filterEntry)) {

            //apply default values
            $filterEntry = array_merge(['operator' => null, 'ignoreInheritance' => null], $filterEntry);

            return new FilterEntry($filterEntry['fieldname'], $filterEntry['filterEntryData'], $filterEntry['operator'], $filterEntry['ignoreInheritance']);
        } elseif ($filterEntry instanceof FilterEntry) {
            return $filterEntry;
        } else {
            throw new Exception('invalid filter entry: ' . print_r($filterEntry, true));
        }
    }

    /**
     * @param string $classId
     * @param array $filters
     * @param BuilderInterface|string $fullTextQuery
     *
     * @return array
     *
     * @throws Exception
     */
    public function getIdsFromFilterNoLimit($classId, array $filters, $fullTextQuery): array
    {
        $search = $this->getDoFilter($classId, $filters, $fullTextQuery);
        $sort = new FieldSort('id', 'asc');

        $search->addSort($sort);

        $classDefinition = \Pimcore\Model\DataObject\ClassDefinition::getById($classId);
        $params = [
            'index' => $this->getIndexName($classDefinition->getName()),
            'track_total_hits' => true,
            'rest_total_hits_as_int' => true,
            'body' => $search->toArray()
        ];

        $this->logger->info('Filter-Params: ' . json_encode($params));

        $ids = [];
        do {
            $results = $this->openSearchClient->search($params);
            $total = $results['hits']['total'];
            $searchAfter = end($results['hits']['hits'])['sort'];
            $search->setSearchAfter($searchAfter);
            $params['body'] = $search->toArray();
            $ids = array_unique(array_merge($this->extractIdsFromResult($results), $ids));
        } while ($total !== count($ids));

        return $ids;
    }

    public function getDoFilter($classId, array $filters, $fullTextQuery): Search
    {
        $classDefinition = \Pimcore\Model\DataObject\ClassDefinition::getById($classId);

        $search = $this->getFilter($classDefinition, $filters);

        if ($fullTextQuery instanceof BuilderInterface) {
            $search->addQuery($fullTextQuery);
        } elseif (!empty($fullTextQuery)) {
            $search->addQuery(new QueryStringQuery($fullTextQuery));
        }

        $this->eventDispatcher->dispatch(new FilterSearchEvent($search), AdvancedObjectSearchEvents::SEARCH_FILTER);

        if ($this->user) {
            $this->addPermissionsExcludeFilter($search);
        }

        return $search;
    }

    /**
     * @param string $classId
     * @param array $filters
     * @param BuilderInterface|string $fullTextQuery
     * @param int $from
     * @param int $size
     *
     * @return array|callable
     *
     * @throws Exception
     */
    public function doFilter($classId, array $filters, $fullTextQuery, $from = null, $size = null)
    {
        $classDefinition = \Pimcore\Model\DataObject\ClassDefinition::getById($classId);

        $search = $this->getDoFilter($classId, $filters, $fullTextQuery);

        if ($size) {
            $search->setSize($size);
        }
        if ($from) {
            $search->setFrom($from);
        }

        if ($this->user) {
            $this->addPermissionsExcludeFilter($search);
        }

        $params = [
            'index' => $this->getIndexName($classDefinition->getName()),
            'track_total_hits' => true,
            'rest_total_hits_as_int' => true,
            'body' => $search->toArray()
        ];

        $this->logger->info('Filter-Params: ' . json_encode($params));

        return $this->openSearchClient->search($params);
    }

    /**
     * adds filter to exclude forbidden paths
     *
     * @param Search $search
     *
     * @throws Exception
     */
    protected function addPermissionsExcludeFilter(Search $search)
    {
        //exclude forbidden objects
        if (!$this->user->isAllowed('objects')) {
            throw new Exception('User not allowed to search for objects');
        } else {
            $forbiddenObjectPaths = \Pimcore\Model\Element\Service::findForbiddenPaths('object', $this->user);
            if (isset($forbiddenObjectPaths['forbidden'])) {   // @phpstan-ignore-line
                $forbiddenObjectPaths = array_keys($forbiddenObjectPaths['forbidden']);
            }
            if (count($forbiddenObjectPaths) > 0) {
                $boolFilter = new BoolQuery();
                $iMax = count($forbiddenObjectPaths);
                for ($i = 0; $i < $iMax; $i++) {
                    $boolFilter->add(new WildcardQuery('path', $forbiddenObjectPaths[$i] . '*'), BoolQuery::MUST);
                }

                $search->addPostFilter($boolFilter, BoolQuery::MUST_NOT);
            }
        }
    }

    public function extractTotalCountFromResult($searchResult)
    {
        return $searchResult['hits']['total'];
    }

    public function extractIdsFromResult($searchResult)
    {
        $ids = [];

        foreach ($searchResult['hits']['hits'] as $hit) {
            $ids[] = $hit['_id'];
        }

        return $ids;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function isExcludedClass(string $className): bool
    {
        $excludeClasses = $this->indexConfigService->getIndexConfiguration('exclude_classes');

        return isset($excludeClasses)
            && in_array($className, $excludeClasses);
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    protected function isExcludedField(string $className, string $fieldName): bool
    {
        $excludeFields = $this->indexConfigService->getIndexConfiguration('exclude_fields');

        return isset($excludeFields[$className])
            && in_array($fieldName, $excludeFields[$className]);
    }
}
