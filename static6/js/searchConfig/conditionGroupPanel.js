
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.conditionGroupPanel");
pimcore.plugin.esbackendsearch.searchConfig.conditionGroupPanel = Class.create(pimcore.plugin.esbackendsearch.searchConfig.conditionAbstractPanel, {

    getConditionPanel: function(panel, data) {
        var niceName = t("plugin_esbackendsearch_group");

        var myId = Ext.id();

        return Ext.create('Ext.panel.Panel', {
            id: myId,
            bodyStyle: "padding: 15px 10px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            layout: this.mainPanelLayout,
            scrollable: true,
            border: 1,
            panelInstance: this,
            items: [
                this.getInnerConditionPanel(myId)
            ]
        });

    },

    getInnerConditionPanel: function(myId) {
        var helper = new pimcore.plugin.esbackendsearch.searchConfig.helper();
        this.conditionsContainerInner = helper.buildConditionsContainerInner(this.classId, this, myId, this.conditionEntryPanelLayout);

        return Ext.create('Ext.panel.Panel',{
            border: false,
            width: "100%",
            items: [
                this.getOperatorCombobox(),
                this.conditionsContainerInner
            ]
        });
    },

    getOperatorCombobox: function() {
        this.operatorField = Ext.create('Ext.form.ComboBox',
            {

                fieldLabel:  t("plugin_esbackendsearch_operator"),
                store: ["must", "should"],
                // value: data.condition,
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
