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
                    new pimcore.plugin.esbackendsearch.selector(function(selection)  {

                        var id = selection.id;
                        if(id) {
                            Ext.Ajax.request({
                                url: "/plugin/ESBackendSearch/admin/load-search",
                                params: {
                                    id: id
                                },
                                method: "get",
                                success: function (response) {
                                    var rdata = Ext.decode(response.responseText);

                                    console.log(rdata);

                                }.bind(this)
                            });


                        }
                        console.log(selection);
                    });
                }
            });
        }

    }
});

var esbackendsearchPlugin = new pimcore.plugin.esbackendsearch();

