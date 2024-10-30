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

namespace AdvancedObjectSearchBundle\Controller;

use AdvancedObjectSearchBundle\Event\AdvancedObjectSearchEvents;
use AdvancedObjectSearchBundle\Event\FilterListingEvent;
use AdvancedObjectSearchBundle\Model\SavedSearch;
use AdvancedObjectSearchBundle\Service;
use Pimcore\Bundle\AdminBundle\Helper\QueryParams;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Db;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Listing;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminController
 *
 * @Route("/admin")
 */
class AdminController extends UserAwareController
{
    use JsonHelperTrait;

    /**
     * @Route("/get-fields")
     */
    public function getFieldsAction(Request $request, Service $service): JsonResponse
    {
        $type = strip_tags($request->query->getString('type'));

        $allowInheritance = false;

        switch ($type) {
            case 'class':
                $classId = strip_tags($request->query->getString('class_id'));
                $definition = DataObject\ClassDefinition::getById($classId);
                $allowInheritance = $definition->getAllowInherit();
                break;

            case 'fieldcollection':
                $key = strip_tags($request->query->getString('key'));
                $definition = DataObject\Fieldcollection\Definition::getByKey($key);
                break;

            case 'objectbrick':
                $key = strip_tags($request->query->getString('key'));
                $definition = DataObject\Objectbrick\Definition::getByKey($key);

                $classId = strip_tags($request->query->getString('class_id'));
                $classDefinition = DataObject\ClassDefinition::getById($classId);
                $allowInheritance = $classDefinition->getAllowInherit();

                break;

            default:
                throw new \Exception("Invalid type '$type''");

        }

        $fieldSelectionInformationEntries = $service->getFieldSelectionInformationForClassDefinition($definition, $allowInheritance);

        $fields = [];
        foreach ($fieldSelectionInformationEntries as $entry) {
            $fields[] = $entry->toArray();
        }

        return $this->jsonResponse(['data' => $fields]);
    }

    /**
     * @Route("/grid-proxy")
     */
    public function gridProxyAction(Request $request, Service $service, EventDispatcherInterface $eventDispatcher): JsonResponse | Response
    {
        $requestedLanguage = $request->request->getString('language');
        if ($requestedLanguage) {
            if ($requestedLanguage != 'default') {
                $request->setLocale($requestedLanguage);
            }
        } else {
            $requestedLanguage = $request->getLocale();
        }

        if ($request->request->has('data')) {
            return $this->forward('Pimcore\Bundle\AdminBundle\Controller\Admin\DataObject\DataObjectController::gridProxyAction', [], $request->query->all());
        } else {

            // get list of objects
            $class = DataObject\ClassDefinition::getById($request->query->getString('classId'));
            $className = $class->getName();

            $fields = $request->request->all('fields');

            $limit = $request->request->getInt('limit', 20);
            $start = $request->request->getInt('start');

            $listClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\Listing';

            $data = json_decode($request->request->getString('filter'), true);
            $results = $service->doFilter($data['classId'], $data['conditions']['filters'], $data['conditions']['fulltextSearchTerm'], $start, $limit);

            $total = $service->extractTotalCountFromResult($results);
            $ids = $service->extractIdsFromResult($results);

            /**
             * @var Listing $list
             */
            $list = new $listClass();
            $list->setObjectTypes(['object', 'folder', 'variant']);

            $conditionFilters = [];
            $idField = DataObjectService::getVersionDependentDatabaseColumnName('id');
            $keyColumn = DataObjectService::getVersionDependentDatabaseColumnName('key');
            $pathColumn = DataObjectService::getVersionDependentDatabaseColumnName('path');
            if (!$this->getPimcoreUser()->isAdmin()) {
                $userIds = $this->getPimcoreUser()->getRoles();
                $userIds[] = $this->getPimcoreUser()->getId();

                $conditionFilters[] = ' (
                                                    (select list from users_workspaces_object where userId in (' . implode(',', $userIds) . ') and LOCATE(CONCAT('. $pathColumn .', ' . $keyColumn . '),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_object where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT('. $pathColumn .', ' . $keyColumn . '))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )';
            }

            if (!empty($ids)) {
                $conditionFilters[] = $idField . ' IN (' . implode(',', $ids) . ')';
                $list->setOrderKey(' FIELD(' . $idField . ', ' . implode(',', $ids) . ')', false);
            } else {
                $conditionFilters[] = '1=2';
            }

            $list->setCondition(implode(' AND ', $conditionFilters));

            $eventDispatcher->dispatch(new FilterListingEvent($list), AdvancedObjectSearchEvents::LISTING_FILER);

            $list->load();

            $objects = [];
            foreach ($list->getObjects() as $object) {
                $o = DataObject\Service::gridObjectData($object, $fields, $requestedLanguage);
                $objects[] = $o;
            }

            return $this->jsonResponse(['data' => $objects, 'success' => true, 'total' => $total]);
        }
    }

    /**
     * @Route("/get-batch-jobs")
     */
    public function getBatchJobsAction(Request $request, Service $service): JsonResponse
    {
        if ($request->request->getString('language')) {
            $request->setLocale($request->request->getString('language'));
        }

        $class = DataObject\ClassDefinition::getById($request->request->getString('classId'));

        $data = json_decode($request->request->getString('filter'), true);
        $results = $service->doFilter($data['classId'], $data['conditions']['filters'] ?? [], $data['conditions']['fulltextSearchTerm'] ?? [], null, 9999);

        $ids = $service->extractIdsFromResult($results);

        $className = $class->getName();
        $listClass = '\\Pimcore\\Model\\DataObject\\' . ucfirst($className) . '\\Listing';
        $list = new $listClass();
        $list->setObjectTypes(['object', 'folder', 'variant']);
        $idField = DataObjectService::getVersionDependentDatabaseColumnName('id');
        $list->setCondition($idField . ' IN (' . implode(',', $ids) . ')');
        $list->setOrderKey(' FIELD('. $idField .', ' . implode(',', $ids) . ')', false);

        if ($request->request->getString('objecttype')) {
            $list->setObjectTypes([$request->request->getString('objecttype')]);
        }

        $jobs = $list->loadIdList();

        return $this->jsonResponse(['success' => true, 'jobs' => $jobs]);
    }

    protected function getCsvFile(string $fileHandle): string
    {
        return $fileHandle . '.csv';
    }

    /**
     * @Route("/get-export-jobs")
     */
    public function getExportJobsAction(Request $request, Service $service): JsonResponse
    {
        if ($request->request->has('language')) {
            $request->setLocale($request->request->getString('language'));
        }

        $data = json_decode($request->request->getString('filter'), true);

        if (empty($ids = $request->request->all('ids'))) {
            $ids = $service->getIdsFromFilterNoLimit(
                $data['classId'],
                $data['conditions']['filters'],
                $data['conditions']['fulltextSearchTerm']
            );
        }

        $jobs = array_chunk($ids, 20);

        $fileHandle = uniqid('export-');
        $storage = Tool\Storage::get('temp');
        $storage->write($this->getCsvFile($fileHandle), '');

        return $this->jsonResponse(['success' => true, 'jobs' => $jobs, 'fileHandle' => $fileHandle]);
    }

    /**
     * @Route("/save")
     */
    public function saveAction(Request $request): JsonResponse
    {
        $data = $request->request->getString('data');
        $data = json_decode($data);

        $id = $request->request->getInt('id');
        if ($id) {
            $savedSearch = SavedSearch::getById($id);
        } else {
            $savedSearch = new SavedSearch();
            $savedSearch->setOwner($this->getPimcoreUser());
        }

        $savedSearch->setName($data->settings->name);
        $savedSearch->setDescription($data->settings->description);
        $savedSearch->setCategory($data->settings->category);
        $savedSearch->setSharedUserIds(array_merge($data->settings->shared_users, $data->settings->shared_roles));
        $savedSearch->setShareGlobally($this->getPimcoreUser()->isAdmin() && $data->settings->share_globally);

        $config = ['classId' => $data->classId, 'gridConfig' => $data->gridConfig, 'conditions' => $data->conditions];
        $savedSearch->setConfig(json_encode($config));

        $savedSearch->save();

        return $this->jsonResponse(['success' => true, 'id' => $savedSearch->getId()]);
    }

    /**
     * @Route("/delete")
     */
    public function deleteAction(Request $request): JsonResponse
    {
        $id = $request->request->getInt('id');
        $savedSearch = SavedSearch::getById($id);

        if ($savedSearch) {
            $savedSearch->delete();

            return $this->jsonResponse(['success' => true, 'id' => $savedSearch->getId()]);
        }

        return $this->jsonResponse(['success' => false, 'message' => "Saved Search with $id not found."]);
    }

    /**
     * @Route("/find")
     */
    public function findAction(Request $request): JsonResponse
    {
        $user = $this->getPimcoreUser();

        $query = $request->query->getString('query');
        if ($query == '*') {
            $query = '';
        }

        $query = str_replace('%', '*', $query);

        $offset = $request->query->getInt('start');
        $limit = $request->query->getInt('limit', 50);

        $db = Db::get();
        $searcherList = new SavedSearch\Listing();
        $conditionParts = [];
        $conditionParams = [];

        //filter for current user
        $userIds = [$user->getId()];
        $userIds = array_merge($userIds, $user->getRoles());
        $userIds = implode('|', $userIds);
        $conditionParts[] = '(shareGlobally = 1 OR ownerId = ? OR sharedUserIds REGEXP ?)';
        $conditionParams[] = $user->getId();
        $conditionParams[] = ',(' . $userIds . '),';

        //filter for query
        if (!empty($query)) {
            $conditionParts[] = sprintf('(%s LIKE ? OR %s LIKE ? OR %s LIKE ?)',
                $db->quoteIdentifier('name'),
                $db->quoteIdentifier('description'),
                $db->quoteIdentifier('category')
            );
            $conditionParams[] = '%' . $query . '%';
            $conditionParams[] = '%' . $query . '%';
            $conditionParams[] = '%' . $query . '%';
        }

        $condition = implode(' AND ', $conditionParts);
        $searcherList->setCondition($condition, $conditionParams);

        $searcherList->setOffset($offset);
        $searcherList->setLimit($limit);

        $sortingSettings = [];
        if (class_exists(QueryParams::class)) {
            $sortingSettings = QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
        }

        if ($sortingSettings['orderKey'] ?? false) {
            $searcherList->setOrderKey($sortingSettings['orderKey']);
        }
        if ($sortingSettings['order'] ?? false) {
            $searcherList->setOrder($sortingSettings['order']);
        }

        $results = []; //$searcherList->load();
        foreach ($searcherList->load() as $result) {
            $results[] = [
                'id' => $result->getId(),
                'name' => $result->getName(),
                'description' => $result->getDescription(),
                'category' => $result->getCategory(),
                'owner' => $result->getOwner() ? $result->getOwner()->getUsername() . ' (' . $result->getOwner()->getFirstname() . ' ' . $result->getOwner()->getLastName() . ')' : '',
                'ownerId' => $result->getOwnerId()
            ];
        }

        // only get the real total-count when the limit parameter is given otherwise use the default limit
        if ($request->query->has('limit')) {
            $totalMatches = $searcherList->getTotalCount();
        } else {
            $totalMatches = count($results);
        }

        return $this->jsonResponse(['data' => $results, 'success' => true, 'total' => $totalMatches]);
    }

    /**
     * @Route("/load-search")
     */
    public function loadSearchAction(Request $request): JsonResponse
    {
        $id = $request->query->getInt('id');
        $savedSearch = SavedSearch::getById($id);
        if ($savedSearch) {
            $config = json_decode($savedSearch->getConfig(), true);
            $classDefinition = DataObject\ClassDefinition::getById($config['classId']);

            if (!empty($config['gridConfig']['columns'])) {
                $helperColumns = [];

                foreach ($config['gridConfig']['columns'] as &$column) {
                    if (!($column['isOperator'] ?? false)) {
                        $fieldDefinition = $classDefinition->getFieldDefinition($column['key']);
                        if ($fieldDefinition) {
                            $width = $column['layout']['width'] ?? null;
                            $column['layout'] = json_decode(json_encode($fieldDefinition), true);
                            if ($width) {
                                $column['layout']['width'] = $width;
                            }
                        }
                    }

                    if (!DataObject\Service::isHelperGridColumnConfig($column['key'])) {
                        continue;
                    }

                    // columnconfig has to be a stdclass
                    $helperColumns[$column['key']] = json_decode(json_encode($column));
                }

                // store the saved search columns in the session, otherwise they won't work
                Tool\Session::useBag($request->getSession(), function (AttributeBagInterface $session) use ($helperColumns) {
                    $existingColumns = $session->get('helpercolumns', []);
                    $helperColumns = array_merge($existingColumns, $helperColumns);
                    $session->set('helpercolumns', $helperColumns);
                }, 'pimcore_gridconfig');
            }

            return $this->jsonResponse([
                'id' => $savedSearch->getId(),
                'classId' => $config['classId'],
                'settings' => [
                    'name' => $savedSearch->getName(),
                    'description' => $savedSearch->getDescription(),
                    'category' => $savedSearch->getCategory(),
                    'sharedUserIds' => $savedSearch->getSharedUserIds(),
                    'shareGlobally' => $savedSearch->getShareGlobally(),
                    'isOwner' => $savedSearch->getOwnerId() == $this->getPimcoreUser()->getId(),
                    'hasShortCut' => $savedSearch->isInShortCutsForUser($this->getPimcoreUser())
                ],
                'conditions' => $config['conditions'],
                'gridConfig' => $config['gridConfig']
            ]);
        }

        return $this->jsonResponse(['success' => false, 'message' => "Saved Search with $id not found."]);
    }

    /**
     * @Route("/load-short-cuts")
     */
    public function loadShortCutsAction(Request $request): JsonResponse
    {
        $list = new SavedSearch\Listing();
        $list->setCondition(
            '(shareGlobally = ? OR ownerId = ? OR sharedUserIds LIKE ?) AND shortCutUserIds LIKE ?',
            [
                true,
                $this->getPimcoreUser()->getId(),
                '%,' . $this->getPimcoreUser()->getId() . ',%',
                '%,' . $this->getPimcoreUser()->getId() . ',%'
            ]
        );
        $list->load();

        $entries = [];
        foreach ($list->getSavedSearches() as $entry) {
            $entries[] = [
                'id' => $entry->getId(),
                'name' => $entry->getName()
            ];
        }

        return $this->jsonResponse(['entries' => $entries]);
    }

    /**
     * @Route("/toggle-short-cut")
     */
    public function toggleShortCutAction(Request $request): JsonResponse
    {
        $id = $request->request->getInt('id');
        $savedSearch = SavedSearch::getById($id);
        if ($savedSearch) {
            $user = $this->getPimcoreUser();
            if ($savedSearch->isInShortCutsForUser($user)) {
                $savedSearch->removeShortCutForUser($user);
            } else {
                $savedSearch->addShortCutForUser($user);
            }
            $savedSearch->save();

            return $this->jsonResponse(['success' => 'true', 'hasShortCut' => $savedSearch->isInShortCutsForUser($user)]);
        }

        return $this->jsonResponse(['success' => 'false']);
    }

    /**
     * @Route("/get-users")
     */
    public function getUsersAction(Request $request): JsonResponse
    {
        $users = [];

        // condition for users with groups having DAM permission
        $condition = [];
        $rolesList = new \Pimcore\Model\User\Role\Listing();
        $rolesList->addConditionParam("CONCAT(',', permissions, ',') LIKE ?", '%,bundle_advancedsearch_search,%');
        $rolesList->load();
        $roles = $rolesList->getRoles();

        foreach ($roles as $role) {
            $condition[] = "CONCAT(',', roles, ',') LIKE '%," . $role->getId() . ",%'";
        }

        // get available user
        $list = new \Pimcore\Model\User\Listing();

        $condition[] = 'admin = 1';
        $list->addConditionParam("((CONCAT(',', permissions, ',') LIKE ? ) OR " . implode(' OR ', $condition) . ')', '%,bundle_advancedsearch_search,%');
        $list->addConditionParam('id != ?', $this->getPimcoreUser()->getId());
        $list->load();
        $userList = $list->getUsers();

        foreach ($userList as $user) {
            $users[] = [
                'id' => $user->getId(),
                'label' => $user->getName()
            ];
        }

        return $this->jsonResponse(['success' => true, 'total' => count($users), 'data' => $users]);
    }

    /**
     * @Route("/get-roles")
     */
    public function getRolesAction(): JsonResponse
    {
        $roles = [];

        $rolesList = new \Pimcore\Model\User\Role\Listing();
        $rolesList->setCondition('type = "role"');
        $rolesList->addConditionParam("CONCAT(',', permissions, ',') LIKE ?", '%,bundle_advancedsearch_search,%');
        $rolesList->load();

        foreach ($rolesList->getRoles() as $role) {
            $roles[] = [
                'id' => $role->getId(),
                'label' => $role->getName()
            ];
        }

        return $this->jsonResponse(['success' => true, 'total' => count($roles), 'data' => $roles]);
    }

    /**
     * @Route("/check-index-status")
     */
    public function checkIndexStatusAction(Request $request, Service $service): JsonResponse
    {
        return $this->jsonResponse(['indexUptodate' => $service->updateQueueEmpty()]);
    }
}
