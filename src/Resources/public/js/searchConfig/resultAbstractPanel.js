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


pimcore.registerNS("pimcore.bundle.advancedObjectSearch.searchConfig.resultAbstractPanel");
pimcore.bundle.advancedObjectSearch.searchConfig.resultAbstractPanel = Class.create(pimcore.object.helpers.gridTabAbstract, {
    systemColumns: ["id", "fullpath", "type", "subtype", "filename", "classname", "creationDate", "modificationDate"],
    noBatchColumns: [],
    batchAppendColumns: [],
    batchRemoveColumns: [],

    getSaveDataCallback: null,
    gridConfigData: {},

    portletMode: false,

    fieldObject: {},

    initialize: function($super) {
        $super();
        
        this.exportPrepareUrl = "/admin/bundle/advanced-object-search/admin/get-export-jobs";
        this.batchPrepareUrl = "/admin/bundle/advanced-object-search/admin/get-batch-jobs";
    },

    getLayout: function (initialFilter) {
    },

    updateGrid: function (classId) {
    },

    createGrid: function (fromConfig, response, settings, save) {
    }
});

/**
 * https://github.com/pimcore/advanced-object-search/issues/64
 * TODO pimcore.object.helpers.gridcolumnconfig for BC reasons, to be removed with next major version
 */
if (pimcore.object.helpers.gridcolumnconfig) {
    pimcore.bundle.advancedObjectSearch.searchConfig.resultAbstractPanel.addMethods(pimcore.object.helpers.gridcolumnconfig);
} else {
    pimcore.bundle.advancedObjectSearch.searchConfig.resultAbstractPanel.addMethods(pimcore.element.helpers.gridColumnConfig);
}
