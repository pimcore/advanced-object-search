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


pimcore.registerNS("pimcore.layout.portlets.advancedObjectSearch");
pimcore.layout.portlets.advancedObjectSearch = Class.create(pimcore.layout.portlets.abstract, {

    getType: function () {
        return "pimcore.layout.portlets.advancedObjectSearch";
    },


    getName: function () {
        return t("bundle_advancedObjectSearch");
    },

    getIcon: function () {
        return "pimcore_bundle_advancedObjectSearch";
    },

    getLayout: function (portletId) {

        var defaultConf = this.getDefaultConfig();



        defaultConf.tools = [
            {
                type:'search',
                handler: function() {
                    pimcore.bundle.advancedObjectSearch.helper.openEsSearch(this.config);
                }.bind(this)
            },
            {
                type:'gear',
                handler: this.editSettings.bind(this)
            },
            {
                type:'close',
                handler: this.remove.bind(this)
            }
        ];

        this.layout = Ext.create('Portal.view.Portlet', Object.assign(defaultConf, {
            title: this.getName(),
            iconCls: this.getIcon(),
            height: 275,
            layout: "fit",
            items: []
        }));

        if(this.config) {
            this.updateChart();
        }

        this.layout.portletId = portletId;
        return this.layout;
    },

    editSettings: function () {
        var selector = new pimcore.bundle.advancedObjectSearch.selector(this.updateSettings.bind(this));
    },

    updateSettings: function(searchId, callback) {

        this.config = searchId;
        Ext.Ajax.request({
            url: "/admin/portal/update-portlet-config",
            method: 'PUT',
            params: {
                key: this.portal.key,
                id: this.layout.portletId,
                config: this.config
            },
            success: function () {
                this.updateChart();
                callback();
            }.bind(this)
        });
    },

    updateChart: function() {
        if(this.config) {
            Ext.Ajax.request({
                url: "/admin/bundle/advanced-object-search/admin/load-search",
                params: {
                    id: this.config
                },
                method: "get",
                success: function (response) {
                    var searchConfig = Ext.decode(response.responseText);
                    var resultPanel = new pimcore.bundle.advancedObjectSearch.searchConfig.resultPanel(
                        function() {
                            return response.responseText;
                        },
                        searchConfig.gridConfig,
                        true
                    );
                    var resultPanelLayout = resultPanel.getLayout();
                    resultPanel.updateGrid(searchConfig.classId);
                    this.layout.removeAll();
                    this.layout.add(resultPanelLayout);
                    this.layout.updateLayout();

                }.bind(this)
            });


        }
    }

});
