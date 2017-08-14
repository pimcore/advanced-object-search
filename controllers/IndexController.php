<?php


class ESBackendSearch_IndexController extends \Pimcore\Controller\Action\Admin
{
    
    public function indexAction()
    {

        $service = new \ESBackendSearch\Service();
        $client = \ESBackendSearch\Plugin::getESClient();


        $service->updateMapping(\Pimcore\Model\Object\ClassDefinition::getByName("Product"));

        // reachable via http://your.domain/plugin/ESBackendSearch/index/index
    }
}
