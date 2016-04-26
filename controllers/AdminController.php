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


}