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
            searchMenu.add({
                text: t("plugin_esbackendsearch"),
                iconCls: "pimcore_icon_esbackendsearch",
                handler: function () {
                    try {
                        pimcore.globalmanager.get("plugin_es_search").activate();
                    }
                    catch (e) {
                        pimcore.globalmanager.add("plugin_es_search", new pimcore.plugin.esbackendsearch.searchConfigPanel());
                    }
                }
            });
        }

    }
});

var esbackendsearchPlugin = new pimcore.plugin.esbackendsearch();

