
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


        this.conditionsContainerInner = Ext.create('Ext.panel.Panel',{
            tbar: [{
                iconCls: "pimcore_icon_add",
                menu: addMenu
            }],
            collapsible: true,
            title: t("plugin_esbackendsearch_filters"),
            border: false,
            items: []
        });

        this.termField = Ext.create('Ext.form.field.Text',
            {
                fieldLabel:  t("plugin_esbackendsearch_fulltextterm"),
                width: "100%"
            }
        );

        return Ext.create('Ext.panel.Panel',{
            border: false,
            items: [
                this.termField,
                this.conditionsContainerInner
            ]
        });
    },

    getSaveData: function() {
        var conditionsData = [];
        var conditions = this.conditionsContainerInner.items.getRange();
        for (var i=0; i<conditions.length; i++) {
            var condition = conditions[i].panelInstance.getFilterValues();
            conditionsData.push(condition);
        }
        return {
            "filters": conditionsData,
            "fulltextSearchTerm": this.termField.getValue()
        };
    }



});