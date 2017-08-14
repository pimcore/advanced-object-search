
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.localizedfields");
pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.localizedfields = Class.create(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.default, {

    getConditionPanel: function() {

        var language = "";
        var subData = null;
        if(this.data && this.data.filterEntryData) {
            language = Object.keys(this.data.filterEntryData)[0];
            
            if(language) {
                subData = this.data.filterEntryData[language][0];
            }
            
        }

        this.subPanel = new pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel[this.fieldSelectionInformation.context.subType](this.fieldSelectionInformation, subData);

        this.languageField = Ext.create('Ext.form.ComboBox',
            {
                fieldLabel: t("plugin_esbackendsearch_language"),
                name: "language",
                store: this.fieldSelectionInformation.context.languages,
                value: language,
                queryMode: 'local',
                width: 300,
                displayField: 'data',
                forceSelection: true
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
            "fieldname": this.fieldSelectionInformation.fieldType,
            "filterEntryData": subValue
        };
    }


});
