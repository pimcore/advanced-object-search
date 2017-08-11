
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.select");
pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.select = Class.create(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.default, {

    getConditionPanel: function() {

        var optionStore =  Ext.create('Ext.data.JsonStore', {
            fields: ['key', 'value'],
            data: this.fieldSelectionInformation.context.options
        });

        this.termField = Ext.create('Ext.form.ComboBox',
            {
                fieldLabel:  t("plugin_esbackendsearch_term"),
                width: 400,
                store: optionStore,
                queryMode: 'local',
                style: "padding-left: 20px",
                valueField: 'value',
                displayField: 'key',
                value: this.data.filterEntryData
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
                this.getOperatorCombobox(this.data.operator),
                this.termField,
                this.inheritanceField
            ]
        });
    }

});
