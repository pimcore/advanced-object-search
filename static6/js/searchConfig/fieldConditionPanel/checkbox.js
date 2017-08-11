
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.checkbox");
pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.checkbox = Class.create(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.default, {

    getConditionPanel: function() {

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
                this.getOperatorCombobox(this.data.operator),
                this.inheritanceField
            ]
        });
    },

    getFilterValues: function() {

       return {
            fieldname: this.fieldSelectionInformation.fieldName,
            filterEntryData: this.operatorField.getValue() == "must",
            operator: "must",
            ignoreInheritance: this.inheritanceField.getValue()
        };

    }


});
