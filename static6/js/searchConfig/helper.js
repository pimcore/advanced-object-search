pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.helper");
pimcore.plugin.esbackendsearch.searchConfig.helper = Class.create({

    buildConditionsContainerInner: function(classId, parentPanel, panelId, conditionEntryPanelLayout) {

        // drop down menu for adding new conditions
        var addMenu = Ext.create('Ext.menu.Menu');

        var toggleGroup = "panel_" + panelId; // + index + parent.data.id;
        var conditionsContainerInner = Ext.create('Ext.panel.Panel',{
            tbar: [
                {
                    iconCls: "pimcore_icon_add",
                    menu: addMenu
                }
            ],
            collapsible: true,
            title: t("plugin_esbackendsearch_filters"),
            border: false,
            items: []
        });


        addMenu.add({
            iconCls: "pimcore_icon_add",

            handler: function (type, data) {
                var itemClass = new pimcore.plugin.esbackendsearch.searchConfig.conditionEntryPanel(classId, conditionEntryPanelLayout);
                var item = itemClass.getConditionPanel(parentPanel, data);
                conditionsContainerInner.add(item);
                item.updateLayout();
                conditionsContainerInner.updateLayout();

            }.bind(this),
            // true => returns pretty name
            text: t("plugin_esbackendsearch_condition")
        });

        addMenu.add({
            iconCls: "pimcore_icon_add",

            handler: function (type, data) {
                var itemClass = new pimcore.plugin.esbackendsearch.searchConfig.conditionGroupPanel(classId, conditionEntryPanelLayout);
                var item = itemClass.getConditionPanel(parentPanel, data);
                conditionsContainerInner.add(item);
                item.updateLayout();
                conditionsContainerInner.updateLayout();

            }.bind(this),
            // true => returns pretty name
            text: t("plugin_esbackendsearch_group")
        });


        return conditionsContainerInner;

    }


});
