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


namespace AdvancedObjectSearchBundle;

use AdvancedObjectSearchBundle\Tools\Installer;
use Elasticsearch\Client;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class AdvancedObjectSearchBundle extends AbstractPimcoreBundle
{
    /**
     * @var array
     */
    protected static $config;
    public static function getConfig() {
        if(empty(self::$config)) {
            $file = PIMCORE_CUSTOM_CONFIGURATION_DIRECTORY . "/advancedobjectsearch/config.php";
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
     * @inheritDoc
     */
    public function getCssPaths()
    {
        return [
            '/bundles/advancedobjectsearch/css/admin.css'
        ];
    }

    public function getJsPaths()
    {
        return [
            '/bundles/advancedobjectsearch/js/startup.js',
			'/bundles/advancedobjectsearch/js/selector.js',
			'/bundles/advancedobjectsearch/js/helper.js',
			'/bundles/advancedobjectsearch/js/searchConfigPanel.js',
			'/bundles/advancedobjectsearch/js/searchConfig/conditionPanelContainerBuilder.js',
			'/bundles/advancedobjectsearch/js/searchConfig/conditionPanel.js',
			'/bundles/advancedobjectsearch/js/searchConfig/resultPanel.js',
			'/bundles/advancedobjectsearch/js/searchConfig/conditionAbstractPanel.js',
			'/bundles/advancedobjectsearch/js/searchConfig/conditionEntryPanel.js',
			'/bundles/advancedobjectsearch/js/searchConfig/conditionGroupPanel.js',
			'/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/default.js',
			'/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/localizedfields.js',
			'/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/numeric.js',
			'/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/href.js',
			'/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/objects.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/multihref.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/fieldcollections.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/objectbricks.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/objectsMetadata.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/multihrefMetadata.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/checkbox.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/select.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/language.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/country.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/user.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/multiselect.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/countrymultiselect.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/languagemultiselect.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/datetime.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/date.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/time.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/quantityValue.js'
        ];
    }



    /**
     * @return Installer
     */
    public function getInstaller()
    {
        return new Installer();
    }
}
