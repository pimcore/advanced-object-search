

pimcore.registerNS("pimcore.plugin.esbackendsearch.helper");
pimcore.plugin.esbackendsearch.helper = {

    rebuildEsSearchMenu: function() {

        var searchMenu = pimcore.globalmanager.get("layout_toolbar").searchMenu;

        var esSearchMenu = pimcore.globalmanager.get("plugin_essearch_menu");

        if(!esSearchMenu) {
            esSearchMenu = Ext.create('Ext.menu.Item', {
                text: t("plugin_esbackendsearch"),
                iconCls: "pimcore_icon_esbackendsearch",
                hideOnClick: false,
                menu: {
                    cls: "pimcore_navigation_flyout",
                    shadow: false,
                    items: []
                }

            });
            searchMenu.add(esSearchMenu);

            pimcore.globalmanager.add("plugin_essearch_menu", esSearchMenu);
        }
        esSearchMenu.getMenu().removeAll();
        
        esSearchMenu.getMenu().add({
            text: t("plugin_esbackendsearch_new"),
            iconCls: "pimcore_icon_esbackendsearch",
            handler: function () {
                var esSearch = new pimcore.plugin.esbackendsearch.searchConfigPanel();
                pimcore.globalmanager.add(esSearch.getTabId(), esSearch);
            }
        });
        esSearchMenu.getMenu().add({
            text: t("plugin_esbackendsearch_search"),
            iconCls: "pimcore_icon_esbackendsearch",
            handler: function () {
                new pimcore.plugin.esbackendsearch.selector();
            }
        });

        Ext.Ajax.request({
            url: "/plugin/ESBackendSearch/admin/load-short-cuts",
            method: "get",
            success: function (response) {
                var rdata = Ext.decode(response.responseText);

                if(rdata.entries && rdata.entries.length) {
                    esSearchMenu.getMenu().add("-");

                    for(var i = 0; i < rdata.entries.length; i++) {
                        var id = rdata.entries[i].id;
                        esSearchMenu.getMenu().add({
                            text: rdata.entries[i].name,
                            iconCls: "pimcore_icon_esbackendsearch",
                            handler: function (id) {
                                pimcore.plugin.esbackendsearch.helper.openEsSearch(id);
                            }.bind(this, id)
                        });
                    }
                }

            }.bind(this)
        });


        //adding status icon
        var statusBar = Ext.get("pimcore_status");

        var statusIcon = Ext.get(statusBar.insertHtml('afterBegin',
            '<div id="pimcore_icon_esbackendsearch_toolbar" data-menu-tooltip="'
            + t("plugin_esbackendsearch_updating_index") + '"></div>'));

        pimcore.helpers.initMenuTooltips();

        this.checkIndexStatus(statusIcon);

    },

    checkIndexStatus: function(statusIcon) {

        Ext.Ajax.request({
            url: "/plugin/ESBackendSearch/admin/check-index-status",
            method: "get",
            success: function (response) {
                var rdata = Ext.decode(response.responseText);

                if(rdata.indexUptodate === true) {
                    statusIcon.hide();
                } else {
                    statusIcon.show();
                }

                setTimeout(this.checkIndexStatus.bind(this, statusIcon), 10000);

            }.bind(this)
        });

    },

    openEsSearch: function(id, callback) {
        Ext.Ajax.request({
            url: "/plugin/ESBackendSearch/admin/load-search",
            params: {
                id: id
            },
            method: "get",
            success: function (response) {
                var rdata = Ext.decode(response.responseText);

                var tabId = "pimcore_search_" + id;
                try {
                    pimcore.globalmanager.get(tabId).activate();
                }
                catch (e) {
                    var esSearch = new pimcore.plugin.esbackendsearch.searchConfigPanel(rdata);
                    pimcore.globalmanager.add(esSearch.getTabId(), esSearch);
                }

                if(callback) {
                    callback();
                }

            }.bind(this)
        });

    }

};