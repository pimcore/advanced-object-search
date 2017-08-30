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


namespace AdvancedObjectSearchBundle\Controller;

use AdvancedObjectSearchBundle\Model\SavedSearch;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminController
 * @Route("/admin")
 */
class AdminController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController {

    /**
     * @param Request $request
     * @Route("/get-fields")
     */
    public function getFieldsAction(Request $request) {

        $type = strip_tags($request->get("type"));

        $allowInheritance = false;

        switch ($type) {
            case "class":
                $classId = intval($request->get("class_id"));
                $definition = DataObject\ClassDefinition::getById($classId);
                $allowInheritance = $definition->getAllowInherit();
                break;

            case "fieldcollection":
                $key = strip_tags($request->get("key"));
                $definition = DataObject\Fieldcollection\Definition::getByKey($key);
                $allowInheritance = false;
                break;

            case "objectbrick":
                $key = strip_tags($request->get("key"));
                $definition = DataObject\Objectbrick\Definition::getByKey($key);

                $classId = intval($request->get("class_id"));
                $classDefinition = DataObject\ClassDefinition::getById($classId);
                $allowInheritance = $classDefinition->getAllowInherit();

                break;

            default:
                throw new \Exception("Invalid type '$type''");


        }

        $service = $this->get('bundle.advanced_object_search.service');
        $fieldSelectionInformationEntries = $service->getFieldSelectionInformationForClassDefinition($definition, $allowInheritance);

        $fields = [];
        foreach($fieldSelectionInformationEntries as $entry) {
            $fields[] = $entry->toArray();
        }

        return $this->json(['data' => $fields]);
    }

    /**
     * @param Request $request
     * @Route("/grid-proxy")
     */
    public function gridProxyAction(Request $request) {
        $requestedLanguage = $request->get("language");
        if ($requestedLanguage) {
            if ($requestedLanguage != "default") {
                $request->setLocale($requestedLanguage);
            }
        } else {
            $requestedLanguage = $request->getLocale();
        }

        if ($request->get("data")) {
            return $this->forward("PimcoreAdminBundle:Admin/Object:gridProxy", [], $request->query->all());
        } else {

            // get list of objects
            $class = DataObject\ClassDefinition::getById($request->get("classId"));
            $className = $class->getName();

            $fields = array();
            if ($request->get("fields")) {
                $fields = $request->get("fields");
            }

            $start = 0;
            $limit = 20;
            if ($request->get("limit")) {
                $limit = $request->get("limit");
            }
            if ($request->get("start")) {
                $start = $request->get("start");
            }

            $listClass = "\\Pimcore\\Model\\DataObject\\" . ucfirst($className) . "\\Listing";


            //get ID list from ES Service
            $service = $this->get('bundle.advanced_object_search.service');
            $data = json_decode($request->get("filter"), true);
            $results = $service->doFilter($data['classId'], $data['conditions']['filters'], $data['conditions']['fulltextSearchTerm'], $start, $limit);

            $total = $service->extractTotalCountFromResult($results);
            $ids = $service->extractIdsFromResult($results);

            /**
             * @var $list \Pimcore\Model\DataObject\Listing
             */
            $list = new $listClass();
            $list->setObjectTypes(["object", "folder", "variant"]);

            if(!empty($ids)) {
                $list->setCondition("o_id IN (" . implode(",", $ids) . ")");
                $list->setOrderKey(" FIELD(o_id, " . implode(",", $ids) . ")", false);
            } else {
                $list->setCondition("1=2");
            }

            $list->load();

            $objects = array();
            foreach ($list->getObjects() as $object) {
                $o = DataObject\Service::gridObjectData($object, $fields, $requestedLanguage);
                $objects[] = $o;
            }
            return $this->json(array("data" => $objects, "success" => true, "total" => $total));

        }
    }

    /**
     * @param Request $request
     * @Route("/get-batch-jobs")
     */
    public function getBatchJobsAction(Request $request)
    {
        if ($request->get("language")) {
            $request->setLocale($request->get("language"));
        }

        $class = DataObject\ClassDefinition::getById($request->get("classId"));

        //get ID list from ES Service
        $service = $this->get('bundle.advanced_object_search.service');
        $data = json_decode($request->get("filter"), true);
        $results = $service->doFilter($data['classId'], $data['conditions']['filters'], $data['conditions']['fulltextSearchTerm']);

        $ids = $service->extractIdsFromResult($results);

        $className = $class->getName();
        $listClass = "\\Pimcore\\Model\\DataObject\\" . ucfirst($className) . "\\Listing";
        $list = new $listClass();
        $list->setObjectTypes(["object", "folder", "variant"]);
        $list->setCondition("o_id IN (" . implode(",", $ids) . ")");
        $list->setOrderKey(" FIELD(o_id, " . implode(",", $ids) . ")", false);

        if ($request->get("objecttype")) {
            $list->setObjectTypes(array($request->get("objecttype")));
        }

        $jobs = $list->loadIdList();

        return $this->json(array("success"=>true, "jobs"=>$jobs));
    }


    protected function getCsvFile($fileHandle) {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $fileHandle . ".csv";
    }

    /**
     * @param Request $request
     * @Route("/get-export-jobs")
     */
    public function getExportJobsAction(Request $request) {
        if ($request->get("language")) {
            $request->setLocale($request->get("language"));
        }

        //get ID list from ES Service
        $service = $this->get('bundle.advanced_object_search.service');
        $data = json_decode($request->get("filter"), true);

        //TODO eventually add ID filter for preselected export

        $results = $service->doFilter(
            $data['classId'],
            $data['conditions']['filters'],
            $data['conditions']['fulltextSearchTerm'],
            0,
            9999 // elastic search cannot export more results than 9999 in one request
        );

        $ids = $service->extractIdsFromResult($results);
        $jobs = array_chunk($ids, 20);

        $fileHandle = uniqid("export-");
        file_put_contents($this->getCsvFile($fileHandle), "");
        return $this->json(array("success"=>true, "jobs"=> $jobs, "fileHandle" => $fileHandle));
    }

    /**
     * @param Request $request
     * @Route("/save")
     */
    public function saveAction(Request $request) {

        $data = $request->get("data");
        $data = json_decode($data);

        $id = (intval($request->get("id")));
        if($id) {
            $savedSearch = SavedSearch::getById($id);
        } else {
            $savedSearch = new SavedSearch();
            $savedSearch->setOwner($this->getUser());
        }

        $savedSearch->setName($data->settings->name);
        $savedSearch->setDescription($data->settings->description);
        $savedSearch->setCategory($data->settings->category);
        $savedSearch->setSharedUserIds($data->settings->shared_users);

        $config = ['classId' => $data->classId, "gridConfig" => $data->gridConfig, "conditions" => $data->conditions];
        $savedSearch->setConfig(json_encode($config));

        $savedSearch->save();

        return $this->json(["success" => true, "id" => $savedSearch->getId()]);
    }

    /**
     * @param Request $request
     * @Route("/delete")
     */
    public function deleteAction(Request $request) {

        $id = intval($request->get("id"));
        $savedSearch = SavedSearch::getById($id);

        if($savedSearch) {
            $savedSearch->delete();
            return $this->json(["success" => true, "id" => $savedSearch->getId()]);
        } else {
            return $this->json(["success" => false, "message" => "Saved Search with $id not found."]);
        }

    }

    /**
     * @param Request $request
     * @Route("/find")
     */
    public function findAction(Request $request) {

        $user = $this->getUser();

        $query = $request->get("query");
        if ($query == "*") {
            $query = "";
        }

        $query = str_replace("%", "*", $query);

        $offset = intval($request->get("start"));
        $limit = intval($request->get("limit"));

        $offset = $offset ? $offset : 0;
        $limit = $limit ? $limit : 50;

        $searcherList = new SavedSearch\Listing();
        $conditionParts = [];
        $conditionParams = [];

        //filter for current user
        $conditionParts[] = "(ownerId = ? OR sharedUserIds LIKE ?)";
        $conditionParams[] = $user->getId();
        $conditionParams[] = "%," . $user->getId() . ",%";

        //filter for query
        if (!empty($query)) {
            $conditionParts[] = "(name LIKE ? OR description LIKE ? OR category LIKE ?)";
            $conditionParams[] = "%" . $query . "%";
            $conditionParams[] = "%" . $query . "%";
            $conditionParams[] = "%" . $query . "%";
        }

        if (count($conditionParts) > 0) {
            $condition = implode(" AND ", $conditionParts);
            $searcherList->setCondition($condition, $conditionParams);
        }


        $searcherList->setOffset($offset);
        $searcherList->setLimit($limit);

        $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
        if ($sortingSettings['orderKey']) {
            $searcherList->setOrderKey($sortingSettings['orderKey']);
        }
        if ($sortingSettings['order']) {
            $searcherList->setOrder($sortingSettings['order']);
        }

        $results = []; //$searcherList->load();
        foreach($searcherList->load() as $result) {
            $results[] = [
                'id' => $result->getId(),
                'name' => $result->getName(),
                'description' => $result->getDescription(),
                'category' => $result->getCategory(),
                'owner' => $result->getOwner() ? $result->getOwner()->getUsername() . " (" . $result->getOwner()->getFirstname() . " " . $result->getOwner()->getLastName() . ")": "",
                'ownerId' => $result->getOwnerId()
            ];
        }

        // only get the real total-count when the limit parameter is given otherwise use the default limit
        if ($request->get("limit")) {
            $totalMatches = $searcherList->getTotalCount();
        } else {
            $totalMatches = count($results);
        }

        return $this->json(array("data" => $results, "success" => true, "total" => $totalMatches));

    }

    /**
     * @param Request $request
     * @Route("/load-search")
     */
    public function loadSearchAction(Request $request) {

        $id = intval($request->get("id"));
        $savedSearch = SavedSearch::getById($id);
        if($savedSearch) {
            $config = json_decode($savedSearch->getConfig(), true);
            return $this->json([
                'id' => $savedSearch->getId(),
                'classId' => $config['classId'],
                'settings' => [
                    'name' => $savedSearch->getName(),
                    'description' => $savedSearch->getDescription(),
                    'category' => $savedSearch->getCategory(),
                    'sharedUserIds' => $savedSearch->getSharedUserIds(),
                    'isOwner' => $savedSearch->getOwnerId() == $this->getUser()->getId(),
                    'hasShortCut' => $savedSearch->isInShortCutsForUser($this->getUser())
                ],
                'conditions' => $config['conditions'],
                'gridConfig' => $config['gridConfig']
            ]);
        } else {
            return $this->json(["success" => false, "message" => "Saved Search with $id not found."]);
        }
    }

    /**
     * @param Request $request
     * @Route("/load-short-cuts")
     */
    public function loadShortCutsAction(Request $request) {

        $list = new SavedSearch\Listing();
        $list->setCondition("(ownerId = ? OR sharedUserIds LIKE ?) AND shortCutUserIds LIKE ?", [$this->getUser()->getId(), '%,' . $this->getUser()->getId() . ',%', '%,' . $this->getUser()->getId() . ',%']);
        $list->load();

        $entries = [];
        foreach($list->getSavedSearches() as $entry) {
            $entries[] = [
                "id" => $entry->getId(),
                "name" => $entry->getName()
            ];
        }

        return $this->json(['entries' => $entries]);
    }

    /**
     * @param Request $request
     * @Route("/toggle-short-cut")
     */
    public function toggleShortCutAction(Request $request) {
        $id = intval($request->get("id"));
        $savedSearch = SavedSearch::getById($id);
        if($savedSearch) {

            $user = $this->getUser();
            if($savedSearch->isInShortCutsForUser($user)) {
                $savedSearch->removeShortCutForUser($user);
            } else {
                $savedSearch->addShortCutForUser($user);
            }
            $savedSearch->save();
            return $this->json(['success' => 'true', 'hasShortCut' => $savedSearch->isInShortCutsForUser($user)]);

        } else {
            return $this->json(['success' => 'false']);
        }
    }

    /**
     * @param Request $request
     * @Route("/get-users")
     */
    public function getUsersAction(Request $request) {

        $users = [];

        // condition for users with groups having DAM permission
        $condition = [];
        $rolesList = new \Pimcore\Model\User\Role\Listing();
        $rolesList->addConditionParam("CONCAT(',', permissions, ',') LIKE ?", '%,bundle_advancedsearch_search,%');
        $rolesList->load();
        $roles = $rolesList->getRoles();

        foreach($roles as $role) {
            $condition[] = "CONCAT(',', roles, ',') LIKE '%," . $role->getId() . ",%'";
        }

        // get available user
        $list = new \Pimcore\Model\User\Listing();

        $condition[] = "admin = 1";
        $list->addConditionParam("((CONCAT(',', permissions, ',') LIKE ? ) OR " . implode(" OR ", $condition) . ")", '%,bundle_advancedsearch_search,%');
        $list->addConditionParam('id != ?', $this->getUser()->getId());
        $list->load();
        $userList = $list->getUsers();

        foreach($userList as $user) {
            $users[] = [
                'id' => $user->getId(),
                'label' => $user->getUsername()
            ];
        }

        return $this->json(['success' => true, 'total' => count($users), 'data' => $users]);
    }


    /**
     * @param Request $request
     * @Route("/check-index-status")
     */
    public function checkIndexStatusAction(Request $request)
    {

        $service = $this->get('bundle.advanced_object_search.service');
        return $this->json(['indexUptodate' => $service->updateQueueEmpty()]);

    }

}

