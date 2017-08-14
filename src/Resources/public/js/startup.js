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
            pimcore.plugin.esbackendsearch.helper.rebuildEsSearchMenu();
        }
    }
});

var esbackendsearchPlugin = new pimcore.plugin.esbackendsearch();

