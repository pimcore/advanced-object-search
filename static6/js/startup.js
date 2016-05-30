pimcore.registerNS("pimcore.plugin.esbackendsearch");

pimcore.plugin.esbackendsearch = Class.create(pimcore.plugin.admin, {
    getClassName: function() {
        return "pimcore.plugin.esbackendsearch";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },
 
    pimcoreReady: function (params,broker){

        var perspectiveCfg = pimcore.globalmanager.get("perspective");

        var searchMenu = pimcore.globalmanager.get("layout_toolbar").searchMenu;
        if(searchMenu && perspectiveCfg.inToolbar("search.esBackendSearch")) {

            if(pimcore.globalmanager.get("user").isAllowed("plugin_es_search")) {
                var subMenu = Ext.create('Ext.menu.Item', {
                    text: t("plugin_esbackendsearch"),
                    iconCls: "pimcore_icon_esbackendsearch",
                    hideOnClick: false,
                    menu: {
                        cls: "pimcore_navigation_flyout",
                        shadow: false,
                        items: [{
                            text: t("plugin_esbackendsearch_new"),
                            iconCls: "pimcore_icon_esbackendsearch",
                            handler: function () {
                                var esSearch = new pimcore.plugin.esbackendsearch.searchConfigPanel();
                                pimcore.globalmanager.add(esSearch.getTabId(), esSearch);
                            }
                        }]
                    }

                });

                searchMenu.add(subMenu);

                subMenu.getMenu().add({
                    text: t("plugin_esbackendsearch_search"),
                    iconCls: "pimcore_icon_esbackendsearch",
                    handler: function () {
                        new pimcore.plugin.esbackendsearch.selector();
                    }
                });
            }
        }
    }
});

var esbackendsearchPlugin = new pimcore.plugin.esbackendsearch();

