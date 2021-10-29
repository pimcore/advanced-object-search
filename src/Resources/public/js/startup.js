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


pimcore.registerNS("pimcore.bundle.advancedObjectSearch");

pimcore.bundle.advancedObjectSearch = Class.create(pimcore.plugin.admin, {
    getClassName: function() {
        return "pimcore.plugin.advancedObjectSearch";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },
 
    pimcoreReady: function (params,broker){
        var perspectiveCfg = pimcore.globalmanager.get("perspective");
        var user = pimcore.globalmanager.get("user");

        var searchMenu = pimcore.globalmanager.get("layout_toolbar").searchMenu;
        if(searchMenu && perspectiveCfg.inToolbar("search.advancedObjectSearch") && user.isAllowed("bundle_advancedsearch_search")) {
            pimcore.bundle.advancedObjectSearch.helper.rebuildEsSearchMenu();
            pimcore.bundle.advancedObjectSearch.helper.initializeStatusIcon();
        }
    },

    onPerspectiveEditorLoadPermissions: function (context, menu, permissions) {
        if(context == 'toolbar' && menu == 'search' &&
            permissions[context][menu].indexOf('items.advancedObjectSearch') == -1) {
            permissions[context][menu].push('items.advancedObjectSearch');
        }
    }
});

var advancedObjectSearchPlugin = new pimcore.bundle.advancedObjectSearch();

