
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
        if(this.operatorField.getValue() == "eq") {
            filterEntryData = this.termField.getValue()
        } else {
            filterEntryData[this.operatorField.getValue()] = this.termField.getValue();
        }

        return {
            "fieldname": this.fieldSelectionInformation.fieldName,
            "filterEntryData": filterEntryData,
            "operator": "must"
        };

    }

});
