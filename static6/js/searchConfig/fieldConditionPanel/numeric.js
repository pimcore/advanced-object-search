
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.numeric");
pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.numeric = Class.create(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.default, {

    getConditionPanel: function() {

        this.termField = Ext.create('Ext.form.field.Number',
            {
                width: 400,
                style: "padding-left: 20px",
                fieldLabel:  t("plugin_esbackendsearch_term")
            }
        );

        return Ext.create('Ext.panel.Panel', {
            layout: 'hbox',
            items: [
                this.getOperatorCombobox(),
                this.termField
            ]
        });
    },

    getFilterValues: function() {

        var filterEntryData = {};
        var operatorFieldValue = this.operatorField.getValue();

        if(operatorFieldValue == "eq") {
            filterEntryData = this.termField.getValue()
        } else {
            filterEntryData[operatorFieldValue] = this.termField.getValue();
        }


        var operator = "must";
        if(operatorFieldValue == "exists" || operatorFieldValue == "not_exists") {
            operator = operatorFieldValue;
        }

        return {
            "fieldname": this.fieldSelectionInformation.fieldName,
            "filterEntryData": filterEntryData,
            "operator": operator
        };

    }

});
