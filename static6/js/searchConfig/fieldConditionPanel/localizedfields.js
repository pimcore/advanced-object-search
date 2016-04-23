
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.localizedfields");
pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.localizedfields = Class.create(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.default, {

    getConditionPanel: function() {
        this.subPanel = new pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel[this.fieldSelectionInformation.context.subType](this.fieldSelectionInformation);

        this.languageField = Ext.create('Ext.form.ComboBox',
            {
                fieldLabel: t("plugin_esbackendsearch_language"),
                name: "language",
                store: this.fieldSelectionInformation.context.languages,
                // value: data.condition,
                queryMode: 'local',
                width: 300,
                displayField: 'data',
                forceSelection: true,
                listeners: {
                    change: function( item, newValue, oldValue, eOpts ) {
                        // var record = item.getStore().findRecord('fieldName', newValue);
                        // var data = record.data;
                        //
                        // this.itemPanel.setTitle("BBBB");

                    }.bind(this)
                }
            }
        );

        return Ext.create('Ext.panel.Panel', {
            items: [
                this.languageField,
                this.subPanel.getConditionPanel()
            ]

        });
    },

    getFilterValues: function() {
        var subValue = {};
        subValue[this.languageField.getValue()] = [this.subPanel.getFilterValues()];

        return {
            "fielname": this.fieldSelectionInformation.fieldType,
            "filterEntryData": subValue
        };
    }


});
