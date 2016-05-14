
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
        var helper = new pimcore.plugin.esbackendsearch.searchConfig.helper();
        this.conditionsContainerInner = helper.buildConditionsContainerInner(this.classId, this, "root-panel", this.conditionEntryPanelLayout);

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
            if(condition) {
                conditionsData.push(condition);
            }
        }
        return {
            "filters": conditionsData,
            "fulltextSearchTerm": this.termField.getValue()
        };
    }



});