
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.quantityValue");
pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.quantityValue = Class.create(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.numeric, {

    getConditionPanel: function() {

        var termFieldValue = "";
        var unitFieldValue = "";
        var operatorValue = "";
        if(isNaN(this.data.filterEntryData)) {

            var key = Object.keys(this.data.filterEntryData)[0];
            if(key) {
                termFieldValue = this.data.filterEntryData[key]['value'];
                unitFieldValue = this.data.filterEntryData[key]['unit'];
                operatorValue = key;
            }

        } else {
            termFieldValue = this.data.filterEntryData['value'];
            unitFieldValue = this.data.filterEntryData['unit'];
            operatorValue = "eq";
        }

        this.unitStore = new Ext.data.JsonStore({
            autoDestroy: true,
            root: 'data',
            fields: ['id', 'abbreviation']
        });


        this.termField = Ext.create('Ext.form.field.Number',
            {
                width: 200,
                value: termFieldValue
            }
        );

        pimcore.helpers.quantityValue.initUnitStore(this.setUnitStoreData.bind(this), this.fieldSelectionInformation.context.units);

        this.unitField = Ext.create('Ext.form.ComboBox',
            {
                store: this.unitStore,
                value: unitFieldValue,
                queryMode: 'local',
                width: 200,
                valueField: 'id',
                displayField: 'abbreviation',
                forceSelection: true
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
                {
                    xtype: "fieldcontainer",
                    style: "padding-left: 20px",
                    layout: 'hbox',
                    fieldLabel:  t("plugin_esbackendsearch_term"),
                    items: [
                        this.termField,
                        this.unitField
                    ]
                },
                this.inheritanceField
            ]
        });
    },

    setUnitStoreData: function(data) {
        this.unitStore.loadData(data.data);

        if (this.unitField) {
            this.unitField.reset();
        }

    },

    getFilterValues: function() {

        var filterEntryData = {
            unit: this.unitField.getValue(),
            value: {}
        };
        var operatorFieldValue = this.operatorField.getValue();


        if(operatorFieldValue == "eq") {
            filterEntryData.value = this.termField.getValue()
        } else {
            filterEntryData.value[operatorFieldValue] = this.termField.getValue();
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
