<?php

use Pimcore\Model\Object;

class ESBackendSearch_AdminController extends \Pimcore\Controller\Action\Admin {


    public function getFieldsAction() {

        $classId = intval($this->getParam("class_id"));

        $service = new \ESBackendSearch\Service();
        $fieldSelectionInformationEntries = $service->getFieldSelectionInformationForClassDefinition(\Pimcore\Model\Object\ClassDefinition::getById($classId));

        $fields = [];
        foreach($fieldSelectionInformationEntries as $entry) {
            $fields[] = $entry->toArray();
        }

        $this->_helper->json(['data' => $fields]);
    }

    public function gridProxyAction() {
        $requestedLanguage = $this->getParam("language");
        if ($requestedLanguage) {
            if ($requestedLanguage != "default") {
                $this->setLanguage($requestedLanguage, true);
            }
        } else {
            $requestedLanguage = $this->getLanguage();
        }

        if ($this->getParam("data")) {
            $this->forward("grid-proxy", "object", "admin");
        } else {

            // get list of objects
            $class = Object\ClassDefinition::getById($this->getParam("classId"));
            $className = $class->getName();

            $fields = array();
            if ($this->getParam("fields")) {
                $fields = $this->getParam("fields");
            }

            $start = 0;
            $limit = 20;
            if ($this->getParam("limit")) {
                $limit = $this->getParam("limit");
            }
            if ($this->getParam("start")) {
                $start = $this->getParam("start");
            }

            $listClass = "\\Pimcore\\Model\\Object\\" . ucfirst($className) . "\\Listing";


            //get ID list from ES Service
            $service = new ESBackendSearch\Service($this->getUser());
            $data = json_decode($this->getParam("filter"), true);
            $results = $service->doFilter($data['classId'], $data['conditions']['filters'], $data['conditions']['fulltextSearchTerm'], $start, $limit);

            $total = $service->extractTotalCountFromResult($results);
            $ids = $service->extractIdsFromResult($results);

            /**
             * @var $list \Pimcore\Model\Object\Listing
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
                $o = Object\Service::gridObjectData($object, $fields, $requestedLanguage);
                $objects[] = $o;
            }
            $this->_helper->json(array("data" => $objects, "success" => true, "total" => $total));

        }
    }

    public function getBatchJobsAction()
    {
        if ($this->getParam("language")) {
            $this->setLanguage($this->getParam("language"), true);
        }

        $class = Object\ClassDefinition::getById($this->getParam("classId"));

        //get ID list from ES Service
        $service = new ESBackendSearch\Service($this->getUser());
        $data = json_decode($this->getParam("filter"), true);
        $results = $service->doFilter($data['classId'], $data['conditions']['filters'], $data['conditions']['fulltextSearchTerm']);

        $ids = $service->extractIdsFromResult($results);

        $className = $class->getName();
        $listClass = "\\Pimcore\\Model\\Object\\" . ucfirst($className) . "\\Listing";
        $list = new $listClass();
        $list->setObjectTypes(["object", "folder", "variant"]);
        $list->setCondition("o_id IN (" . implode(",", $ids) . ")");
        $list->setOrderKey(" FIELD(o_id, " . implode(",", $ids) . ")", false);

        if ($this->getParam("objecttype")) {
            $list->setObjectTypes(array($this->getParam("objecttype")));
        }

        $jobs = $list->loadIdList();

        $this->_helper->json(array("success"=>true, "jobs"=>$jobs));
    }


    protected function getCsvFile($fileHandle) {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $fileHandle . ".csv";
    }

    public function getExportJobsAction() {
        if ($this->getParam("language")) {
            $this->setLanguage($this->getParam("language"), true);
        }

        //get ID list from ES Service
        $service = new ESBackendSearch\Service($this->getUser());
        $data = json_decode($this->getParam("filter"), true);

        $results = $service->doFilter(
            $data['classId'],
            $data['conditions']['filters'],
            $data['conditions']['fulltextSearchTerm'],
            0,
            9999 // elastic search cannot export more results dann 9999 in one request
        );

        $ids = $service->extractIdsFromResult($results);
        $jobs = array_chunk($ids, 20);

        $fileHandle = uniqid("export-");
        file_put_contents($this->getCsvFile($fileHandle), "");
        $this->_helper->json(array("success"=>true, "jobs"=> $jobs, "fileHandle" => $fileHandle));
    }


    public function saveAction() {

        $data = $this->getParam("data");
        $data = json_decode($data);

        $id = (intval($this->getParam("id")));
        if($id) {
            $savedSearch = \ESBackendSearch\SavedSearch::getById($id);
        } else {
            $savedSearch = new \ESBackendSearch\SavedSearch();
            $savedSearch->setOwner($this->getUser());
        }

        $savedSearch->setName($data->settings->name);
        $savedSearch->setDescription($data->settings->description);
        $savedSearch->setCategory($data->settings->category);

        $config = ['classId' => $data->classId, "gridConfig" => $data->gridConfig, "conditions" => $data->conditions];
        $savedSearch->setConfig(json_encode($config));

        $savedSearch->save();

        $this->_helper->json(["success" => true, "id" => $savedSearch->getId()]);
    }

    public function findAction() {

        $user = $this->getUser();

        $query = $this->getParam("query");
        if ($query == "*") {
            $query = "";
        }

        $query = str_replace("%", "*", $query);

        $offset = intval($this->getParam("start"));
        $limit = intval($this->getParam("limit"));

        $offset = $offset ? $offset : 0;
        $limit = $limit ? $limit : 50;

        $searcherList = new \ESBackendSearch\SavedSearch\Listing();
        $conditionParts = array();
        $conditionParams = array();
        $db = \Pimcore\Db::get();

        if (!empty($query)) {
            $conditionParts[] = "(name LIKE ? OR description LIKE ? OR category LIKE ?)";
            $conditionParams[] = "%" . $query . "%";
            $conditionParams[] = "%" . $query . "%";
            $conditionParams[] = "%" . $query . "%";
        }


        // filtering for objects
        if ($this->getParam("filter")) {

            //TODO
//            $conditionFilters = Object\Service::getFilterCondition($this->getParam("filter"), $class);
//            $join = "";
//            foreach ($bricks as $ob) {
//                $join .= " LEFT JOIN object_brick_query_" . $ob . "_" . $class->getId();
//
//                $join .= " `" . $ob . "`";
//                $join .= " ON `" . $ob . "`.o_id = `object_" . $class->getId() . "`.o_id";
//            }
//
//            $conditionParts[] = "( id IN (SELECT `object_" . $class->getId() . "`.o_id FROM object_" . $class->getId() . $join . " WHERE " . $conditionFilters . ") )";
        }


        if (count($conditionParts) > 0) {
            $condition = implode(" AND ", $conditionParts);

            //echo $condition; die();
            $searcherList->setCondition($condition, $conditionParams);
        }


        $searcherList->setOffset($offset);
        $searcherList->setLimit($limit);

        $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());
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
                'owner' => $result->getOwner() ? $result->getOwner()->getFirstname() . " " . $result->getOwner()->getLastName() . "(" . $result->getOwner()->getUsername() . ")": "",
                'ownerId' => $result->getOwnerId()
            ];
        }

        // only get the real total-count when the limit parameter is given otherwise use the default limit
        if ($this->getParam("limit")) {
            $totalMatches = $searcherList->getTotalCount();
        } else {
            $totalMatches = count($results);
        }

        $this->_helper->json(array("data" => $results, "success" => true, "total" => $totalMatches));

        $this->removeViewRenderer();
    }

    public function loadSearchAction() {

        $id = intval($this->getParam("id"));
        $savedSearch = \ESBackendSearch\SavedSearch::getById($id);
        if($savedSearch) {
            $config = json_decode($savedSearch->getConfig(), true);
            $this->_helper->json([
                'id' => $savedSearch->getId(),
                'classId' => $config['classId'],
                'settings' => [
                    'name' => $savedSearch->getName(),
                    'description' => $savedSearch->getDescription()
                ],
                'conditions' => $config['conditions'],
                'gridConfig' => $config['gridConfig']
            ]);
        }
    }

    public function testAction() {

        $x = new \ESBackendSearch\SavedSearch();
        $x->setName("Meins");
        $x->setOwner($this->user);
        $x->setCategory("mycategory");

        $x->save();


        $y = \ESBackendSearch\SavedSearch::getById(1);
        p_r($y);


        die("meins");
    }

}

