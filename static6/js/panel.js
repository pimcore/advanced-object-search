
pimcore.registerNS("pimcore.plugin.esbackendsearch.configpanel");
pimcore.plugin.esbackendsearch.configpanel = Class.create({

    initialize: function () {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("plugin_es_search_configpanel");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "plugin_es_search_configpanel",
                title: t("plugin_esbackendsearch"),
                iconCls: "saved_search_icon",
                border: false,
                layout: "border",
                closable:true,
                items: [/*this.getTree(), */this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("plugin_es_search_configpanel");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("plugin_es_search");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getTree: function () {
        if (!this.tree) {

            this.store = Ext.create('Ext.data.TreeStore', {
                autoLoad: true,
                autoSync: false,
                proxy: {
                    type: 'ajax',
                    url: '/plugin/SavedSearch/admin/list',
                    reader: {
                        type: 'json',
                        totalProperty : 'total',
                        rootProperty: 'nodes'
                    }
                },
                root: {
                    nodeType: 'async',
                    id: '0'
                }
            });


            this.tree = new Ext.tree.TreePanel({
                store: this.store,
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                containerScroll: true,
                border: true,
                width: 200,
                split: true,
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            text: "Add Config",
                            iconCls: "pimcore_icon_add",
                            handler: this.addField.bind(this)
                        }
                    ]
                },
                listeners: this.getTreeNodeListeners()
            });

            this.tree.on("render", function () {
                this.getRootNode().expand();
            });
        }

        return this.tree;
    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                region: "center"
            });
        }

        this.openConfig();

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick' : this.onTreeNodeClick.bind(this),
            "itemcontextmenu": this.onTreeNodeContextmenu.bind(this),
            'beforeitemappend': function( thisNode, newChildNode, index, eOpts ) {
                newChildNode.data.leaf = true;
            }

        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts ) {
        this.openConfig(record.id);
    },

    openConfig: function(id) {

        var existingPanel = Ext.getCmp("pimcore_plugin_es_backendsearch_panel");
        if(existingPanel) {
            this.editPanel.setActiveTab(existingPanel);
        } else {

            var data = {};
            var panel = new pimcore.plugin.esbackendsearch.searchConfigPanel(data, this);
            pimcore.layout.refresh();


        }

/*
        Ext.Ajax.request({
            url: "/plugin/SavedSearch/admin/get",
            params: {
                id: id
            },
            success: function (response) {
                var data = Ext.decode(response.responseText);

                var fieldPanel = new pimcore.plugin.savedsearch.item(data, this);
                pimcore.layout.refresh();
            }.bind(this)
        });
        */
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
        e.stopEvent();

        tree.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.deleteField.bind(this, tree, record)
        }));

        menu.showAt(e.pageX, e.pageY);
    },

    addField: function () {
        Ext.MessageBox.prompt(t('plugin_savedsearch_mbx_enterkey_title'), t('plugin_savedsearch_mbx_enterkey_prompt'), this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {

        var regresult = value.match(/[a-zA-Z0-9_\-]+/);
        if (button == "ok" && value.length > 2 && regresult == value) {
            Ext.Ajax.request({
                url: "/plugin/SavedSearch/admin/add",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    this.refresh(this.tree.getRootNode());

                    if(!data || !data.success) {
                        Ext.Msg.alert("Error", "Error adding config");
                    } else {
                        this.openConfig(data.id);
                    }

                    var menuContainer = pimcore.globalmanager.get("layout_toolbar").fileMenu.query("#savedSearchContainer", true);
                    var theMenu = menuContainer[0];

                    theMenu.menu.add(new Ext.menu.Item({
                        text: value,
                        iconCls: "pimcore_icon_menu_search",
                        handler: this.performSearch.bind(value)
                    }));


                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert("SavedSearch Config", "Invalid Name");
        }
    },

    performSearch: function() {
        pimcore.plugin.SavedSearch.prototype.performSearch(this);
    },


    deleteField: function (tree, record) {
        Ext.Msg.confirm(t('delete'), t('delete_message'), function(btn){
            if (btn == 'yes') {
                Ext.Ajax.request({
                    url: "/plugin/SavedSearch/admin/delete",
                    params: {
                        name: record.id
                    }
                });

                var menus = pimcore.globalmanager.get("layout_toolbar");
                var fileMenu = menus.fileMenu;
                var menuContainer = menus.fileMenu.query("#savedSearchContainer", true);
                var theMenu = menuContainer[0].menu;

                var filtered = theMenu.queryBy(function (item) {
                    if (record.id == item.text) {
                        return true;
                    } else {
                        return false;
                    }
                });

                if (filtered.length > 0) {
                    theMenu.remove(filtered[0]);
                    // filtered[0].disable();
                }
                this.getEditPanel().removeAll();
                this.store.remove(record);
            }
        }.bind(this));
    },

    refresh: function (record) {
        var ownerTree = record.getOwnerTree();

        record.data.expanded = true;
        ownerTree.getStore().load({
            node: record
        });
    }
});

