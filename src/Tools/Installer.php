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


namespace AdvancedObjectSearchBundle\Tools;

use AdvancedObjectSearchBundle\Model\SavedSearch;
use Pimcore\Config;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Pimcore\Extension\Bundle\Installer\Exception\InstallationException;

class Installer extends AbstractInstaller {

    const QUEUE_TABLE_NAME = "bundle_advancedobjectsearch_update_queue";

    public function install()
    {

        if(!file_exists(PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/advancedobjectsearch")) {
            \Pimcore\File::mkdir(PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/advancedobjectsearch");
            copy(__DIR__ . "/../Resources/install/config.php", PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/advancedobjectsearch/config.php");
        }

        //create tables
        \Pimcore\Db::get()->query(
            "CREATE TABLE IF NOT EXISTS `" . self::QUEUE_TABLE_NAME . "` (
              `o_id` bigint(10) NOT NULL DEFAULT '0',
              `classId` int(11) DEFAULT NULL,
              `in_queue` tinyint(1) DEFAULT NULL,
              `worker_timestamp` int(20) DEFAULT NULL,
              `worker_id` varchar(20) DEFAULT NULL,
              PRIMARY KEY (`o_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );

        \Pimcore\Db::get()->query(
            "CREATE TABLE IF NOT EXISTS `" . SavedSearch\Dao::TABLE_NAME . "` (
              `id` bigint(20) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) DEFAULT NULL,
              `description` varchar(255) DEFAULT NULL,
              `category` varchar(255) DEFAULT NULL,
              `ownerId` int(20) DEFAULT NULL,
              `config` text CHARACTER SET latin1,
              `sharedUserIds` varchar(1000) DEFAULT NULL,
              `shortCutUserIds` text CHARACTER SET latin1,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );

        //insert permission
        $key = 'bundle_advancedsearch_search';
        $permission = new \Pimcore\Model\User\Permission\Definition();
        $permission->setKey( $key );

        $res = new \Pimcore\Model\User\Permission\Definition\Dao();
        $res->configure( \Pimcore\Db::get() );
        $res->setModel( $permission );
        $res->save();


        if($this->isInstalled()){
            return true;
        } else {
            return false;
        }

    }

    public function needsReloadAfterInstall()
    {
        return true;
    }

    public function isInstalled()
    {
        $result = null;
        try{
            if(Config::getSystemConfig()) {
                $result = \Pimcore\Db::get()->fetchAll("SHOW TABLES LIKE '" . self::QUEUE_TABLE_NAME . "';");
            }
        } catch(\Exception $e){}
        return !empty($result);

    }

    public function canBeInstalled()
    {
        return !$this->isInstalled();
    }

    public function canBeUninstalled()
    {
        return true;
    }

    public function uninstall()
    {
        $db = \Pimcore\Db::get();
        $db->query("DROP TABLE IF EXISTS `" . self::QUEUE_TABLE_NAME . "`;");
        $db->query("DROP TABLE IF EXISTS `" . SavedSearch\Dao::TABLE_NAME . "`;");

        $db->query("DELETE FROM users_permission_definitions WHERE `key` = 'bundle_advancedsearch_search'");

        if(self::isInstalled()){
            throw new InstallationException("Could not be uninstalled.");
        }
    }

}
