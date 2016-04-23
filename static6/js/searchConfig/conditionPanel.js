
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.conditionPanel");
pimcore.plugin.esbackendsearch.searchConfig.conditionPanel = Class.create({

    classId: null,
    conditionEntryPanelLayout: "hbox",

    initialize: function(classId, conditionEntryPanelLayout) {
        this.classId = classId;
        if(conditionEntryPanelLayout) {
            this.conditionEntryPanelLayout = conditionEntryPanelLayout;
        }
    },

    getConditionPanel: function() {
        // drop down menu for adding new conditions
        var addMenu = [];

        addMenu.push({
            iconCls: "pimcore_icon_add",

            handler: function(type, data) {
                var itemClass = new pimcore.plugin.esbackendsearch.searchConfig.conditionEntryPanel(this.classId, this.conditionEntryPanelLayout);
                var item = itemClass.getConditionPanel(this, data);
                this.conditionsContainerInner.add(item);
                item.updateLayout();
                this.conditionsContainerInner.updateLayout();

            }.bind(this),
            // true => returns pretty name
            text: t("plugin_esbackendsearch_condition")
        });


        this.conditionsContainerInner = new Ext.Panel({
            tbar: [{
                iconCls: "pimcore_icon_add",
                menu: addMenu
            }],
            border: false,
            items: []
        });

        return this.conditionsContainerInner;
    },

    getSaveData: function() {
        var conditionsData = [];
        var conditions = this.conditionsContainerInner.items.getRange();
        for (var i=0; i<conditions.length; i++) {
            var condition = conditions[i].panelInstance.getFilterValues();
            conditionsData.push(condition);
        }
        return conditionsData;
    }



});