
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.default");
pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.default = Class.create({

    fieldSelectionInformation: null,
    termField: null,
    operatorField: null,

    initialize: function(fieldSelectionInformation) {
        this.fieldSelectionInformation = fieldSelectionInformation;
    },

    getConditionPanel: function() {

        this.termField = Ext.create('Ext.form.field.Text',
            {
                fieldLabel:  t("plugin_esbackendsearch_term"),
                width: 400,
                style: "padding-left: 20px"
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

    getOperatorCombobox: function() {
        this.operatorField = Ext.create('Ext.form.ComboBox',
            {

                fieldLabel:  t("plugin_esbackendsearch_operator"),
                name: "condition",
                store: this.fieldSelectionInformation.context.operators,
                // value: data.condition,
                queryMode: 'local',
                width: 300,
                valueField: 'fieldName',
                displayField: 'fieldLabel',
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

        return this.operatorField;
    },

    getFilterValues: function() {

       return {
            fieldname: this.fieldSelectionInformation.fieldName,
            filterEntryData: this.termField.getValue(),
            operator: this.operatorField.getValue()
        };

    }


});
