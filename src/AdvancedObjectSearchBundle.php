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

use AdvancedObjectSearchBundle\Installer;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class AdvancedObjectSearchBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    /**
     * @inheritDoc
     */
    protected function getComposerPackageName()
    {
        return 'pimcore/advanced-object-search';
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

    /**
     * @inheritDoc
     */
    public function getJsPaths()
    {
        return [
            '/bundles/advancedobjectsearch/js/startup.js',
            '/bundles/advancedobjectsearch/js/selector.js',
            '/bundles/advancedobjectsearch/js/helper.js',
            '/bundles/advancedobjectsearch/js/searchConfigPanel.js',
            '/bundles/advancedobjectsearch/js/searchConfig/conditionPanelContainerBuilder.js',
            '/bundles/advancedobjectsearch/js/searchConfig/conditionPanel.js',
            '/bundles/advancedobjectsearch/js/searchConfig/resultAbstractPanel.js',
            '/bundles/advancedobjectsearch/js/searchConfig/resultPanel.js',
            '/bundles/advancedobjectsearch/js/searchConfig/resultExtension.js',
            '/bundles/advancedobjectsearch/js/searchConfig/conditionAbstractPanel.js',
            '/bundles/advancedobjectsearch/js/searchConfig/conditionEntryPanel.js',
            '/bundles/advancedobjectsearch/js/searchConfig/conditionGroupPanel.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/default.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/localizedfields.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/numeric.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/manyToManyOne.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/manyToManyObjectRelation.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/manyToManyRelation.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/fieldcollections.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/objectbricks.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/advancedManyToManyObjectRelation.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/advancedManyToManyRelation.js',
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
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/quantityValue.js',
            '/bundles/advancedobjectsearch/js/searchConfig/fieldConditionPanel/table.js',
            '/bundles/advancedobjectsearch/js/portlet/advancedObjectSearch.js'
        ];
    }

    /**
     * @return Installer
     */
    public function getInstaller()
    {
        return $this->container->get(Installer::class);
    }
}
