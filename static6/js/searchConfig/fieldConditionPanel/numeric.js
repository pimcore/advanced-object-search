
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.numeric");
pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.numeric = Class.create(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.default, {

    getConditionPanel: function() {

        var termFieldValue = "";
        var operatorValue = "";
        if(isNaN(this.data.filterEntryData)) {

            var key = Object.keys(this.data.filterEntryData)[0];
            if(key) {
                termFieldValue = this.data.filterEntryData[key];
                operatorValue = key;
            }

        } else {
            termFieldValue = this.data.filterEntryData;
            operatorValue = "eq";
        }

        this.termField = Ext.create('Ext.form.field.Number',
            {
                width: 400,
                style: "padding-left: 20px",
                fieldLabel:  t("plugin_esbackendsearch_term"),
                value: termFieldValue
            }
        );

        this.inheritanceField = Ext.create('Ext.form.field.Checkbox',
            {
                fieldLabel:  t("plugin_esbackendsearch_ignoreInheritance"),
                style: "padding-left: 20px",
                value: this.data.ignoreInheritance,
                hidden: !this.fieldSelectionInformation.context.classInheritanceEnabled
            }
        );

        return Ext.create('Ext.panel.Panel', {
            layout: 'hbox',
            items: [
                this.getOperatorCombobox(operatorValue),
                this.termField,
                this.inheritanceField
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
            fieldname: this.fieldSelectionInformation.fieldName,
            filterEntryData: filterEntryData,
            operator: operator,
            ignoreInheritance: this.inheritanceField.getValue()
        };

    }

});
