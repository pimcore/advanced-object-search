pimcore.registerNS("pimcore.plugin.esbackendsearch");

pimcore.plugin.esbackendsearch = Class.create(pimcore.plugin.admin, {
    getClassName: function() {
        return "pimcore.plugin.esbackendsearch";
    },

    initialize: function() {
        pimcore.plugin.broker.registerPlugin(this);
    },
 
    pimcoreReady: function (params,broker){
        pimcore.globalmanager.get("layout_toolbar").settingsMenu.add({
            text: t("plugin_es_search"),
            iconCls: "saved_search_icon",
            handler: function () {
                try {
                    pimcore.globalmanager.get("plugin_es_search").activate();
                }
                catch (e) {
                    pimcore.globalmanager.add("plugin_es_search", new pimcore.plugin.esbackendsearch.configpanel());
                }
            }
        });
    }
});

var esbackendsearchPlugin = new pimcore.plugin.esbackendsearch();

