<?php

namespace ESBackendSearch;

use Elasticsearch\Client;
use Pimcore\API\Plugin as PluginLib;
use Pimcore\Model\Object\Concrete;

class Plugin extends PluginLib\AbstractPlugin implements PluginLib\PluginInterface
{

    public function init()
    {
        parent::init();

        // register your events here

        // using anonymous function
        \Pimcore::getEventManager()->attach("document.postAdd", function ($event) {
            // do something
            $document = $event->getTarget();
        });

        // using methods
        \Pimcore::getEventManager()->attach("object.postUpdate", array($this, "handleObject"));

        // for more information regarding events, please visit:
        // http://www.pimcore.org/wiki/display/PIMCORE/Event+API+%28EventManager%29+since+2.1.1
        // http://framework.zend.com/manual/1.12/de/zend.event-manager.event-manager.html
        // http://www.pimcore.org/wiki/pages/viewpage.action?pageId=12124202
    }

    public function handleObject($event)
    {
        // do something
        $object = $event->getTarget();
        if($object instanceof Concrete) {
            $service = new \ESBackendSearch\Service();
            $service->doUpdateIndexData($object);
        }
    }

    public static function install()
    {
        // implement your own logic here
        return true;
    }
    
    public static function uninstall()
    {
        // implement your own logic here
        return true;
    }

    public static function isInstalled()
    {
        // implement your own logic here
        return true;
    }


    /**
     * @var Client
     */
    protected static $esClient = null;

    /**
     * @return Client
     */
    public static function getESClient() {

        if(empty(self::$esClient)) {
            self::$esClient = \Elasticsearch\ClientBuilder::create()->setHosts(["frischeis.dev.elements.pm"])->build();
        }

        return self::$esClient;
    }
}
