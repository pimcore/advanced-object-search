/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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


pimcore.bundle.advancedObjectSearch.searchConfig.resultAbstractPanel.addMethods(pimcore.element.helpers.gridColumnConfig);

