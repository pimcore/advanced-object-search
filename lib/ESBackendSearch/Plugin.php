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
        if(!file_exists(PIMCORE_WEBSITE_PATH . "/config/esbackendsearch")) {
            \Pimcore\File::mkdir(PIMCORE_WEBSITE_PATH . "/config/esbackendsearch");
            copy(PIMCORE_PLUGINS_PATH . "/ESBackendSearch/install/config.php", PIMCORE_WEBSITE_PATH . "/config/esbackendsearch/config.php");
        }

        \Pimcore\File::mkdir(PIMCORE_WEBSITE_VAR . "/plugins/ESBackendSearch");
        file_put_contents(PIMCORE_WEBSITE_VAR . "/plugins/ESBackendSearch/installed.dummy", "true");

        return true;
    }

    public static function needsReloadAfterInstall()
    {
        return true;
    }


    public static function uninstall()
    {
        unlink(PIMCORE_WEBSITE_VAR . "/plugins/ESBackendSearch/installed.dummy");
    }

    public static function isInstalled()
    {
        return file_exists(PIMCORE_WEBSITE_VAR . "/plugins/ESBackendSearch/installed.dummy");
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
            $config = self::getConfig();
            self::$esClient = \Elasticsearch\ClientBuilder::create()->setHosts($config['hosts'])->build();
        }

        return self::$esClient;
    }

    /**
     * @var array
     */
    protected static $config;
    public static function getConfig() {
        if(empty(self::$config)) {
            $file = \Pimcore\Config::locateConfigFile("esbackendsearch/config.php");
            if(file_exists($file)) {
                $config = include($file);
            } else {
                throw new \Exception($file . " doesn't exist");
            }
            self::$config = $config;
        }

        return self::$config;
    }


    /**
     * @param string $language
     * @return string path to the translation file relative to plugin directory
     */
    public static function getTranslationFile($language)
    {
        return sprintf('/ESBackendSearch/texts/%s.csv', $language);
    }
}
