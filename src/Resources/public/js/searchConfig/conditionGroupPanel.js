
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.conditionGroupPanel");
pimcore.plugin.esbackendsearch.searchConfig.conditionGroupPanel = Class.create(pimcore.plugin.esbackendsearch.searchConfig.conditionAbstractPanel, {

    getConditionPanel: function(panel, data) {
        var niceName = t("plugin_esbackendsearch_group");

        var myId = Ext.id();

        return Ext.create('Ext.panel.Panel', {
            id: myId,
            bodyStyle: "padding: 15px 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            layout: this.mainPanelLayout,
            scrollable: true,
            border: 1,
            panelInstance: this,
            items: [
                this.getInnerConditionPanel(myId, data)
            ]
        });

    },

    getInnerConditionPanel: function(myId, data) {
        var helper = new pimcore.plugin.esbackendsearch.searchConfig.conditionPanelContainerBuilder(this.classId, this, myId, this.conditionEntryPanelLayout);
        this.conditionsContainerInner = helper.buildConditionsContainerInner();

        if(data.filterEntryData) {
            helper.populateConditionsContainerInner(data.filterEntryData);
        }

        return Ext.create('Ext.panel.Panel',{
            border: false,
            width: "100%",
            items: [
                this.getOperatorCombobox(data),
                this.conditionsContainerInner
            ]
        });
    },

    getOperatorCombobox: function(data) {
        this.operatorField = Ext.create('Ext.form.ComboBox',
            {

                fieldLabel:  t("plugin_esbackendsearch_operator"),
                store: ["must", "should"],
                value: data ? data.operator : "",
                queryMode: 'local',
                width: 300,
                listeners: {
                    change: function( item, newValue, oldValue, eOpts ) {

                    }.bind(this)
                }
            }
        );

        return this.operatorField;
    },

    getFilterValues: function() {

        var conditionsData = [];
        if(this.conditionsContainerInner) {
            var conditions = this.conditionsContainerInner.items.getRange();
            for (var i=0; i<conditions.length; i++) {
                var condition = conditions[i].panelInstance.getFilterValues();
                conditionsData.push(condition);
            }
            return {
                "filterEntryData": conditionsData,
                "operator": this.operatorField.getValue(),
                "fieldname": "~~group~~"
            };
        }

        return null;
    }

});
