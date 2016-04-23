<?php


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


    public function filterAction() {
        $service = new ESBackendSearch\Service();

        $data = json_decode($this->getParam("data"), true);

        $results = $service->doFilter($data['classId'], $data['conditions'], null);

        p_r($results); die();

    }


}