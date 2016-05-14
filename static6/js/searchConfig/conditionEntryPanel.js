
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.conditionEntryPanel");
pimcore.plugin.esbackendsearch.searchConfig.conditionEntryPanel = Class.create(pimcore.plugin.esbackendsearch.searchConfig.conditionAbstractPanel, {

    getConditionPanel: function(panel, data) {
        var niceName = t("plugin_esbackendsearch_condition");

        if(typeof data == "undefined") {
            data = {};
        }

        var fieldStore = new Ext.data.JsonStore({
            autoDestroy: true,
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: '/plugin/ESBackendSearch/admin/get-fields',
                reader: {
                    rootProperty: 'data',
                    idProperty: 'fieldName'
                },
                extraParams: {class_id: this.classId }
            },
            fields: ['fieldName','fieldLabel', 'fieldType', 'context'],
            listeners: {
                load: function (store) {
                    // if(this.fieldsCombobox) {
                    //     this.fieldsCombobox.setValue(this.data);
                    // }
                }.bind(this)
            }
        });

        var fieldSelection = Ext.create('Ext.form.ComboBox',
            {

                fieldLabel: t("plugin_esbackendsearch_field"),
                name: "condition",
                store: fieldStore,
                // value: data.condition,
                queryMode: 'local',
                width: 400,
                valueField: 'fieldName',
                displayField: 'fieldLabel',
                listeners: {
                    change: function( item, newValue, oldValue, eOpts ) {
                        var record = item.getStore().findRecord('fieldName', newValue);
                        var data = record.data;

                        this.fieldConditionPanel.removeAll();
                        if(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel[data.fieldType]) {
                            this.fieldCondition = new pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel[data.fieldType](data);
                            this.fieldConditionPanel.add(this.fieldCondition.getConditionPanel());
                        } else {
                            console.log("ERROR - no implementation for field condition panel for " + data.fieldType);
                        }

                    }.bind(this)
                }
            }
        );

        var padding = (this.mainPanelLayout == 'hbox' ? "padding-left: 20px" : "");
        this.fieldConditionPanel = Ext.create('Ext.panel.Panel', {
            flex: 1,
            style: padding
        });

        var myId = Ext.id();

        return Ext.create('Ext.panel.Panel', {
            id: myId,
            bodyStyle: "padding: 15px 10px;",
            tbar: this.getTopBar(niceName, myId, panel, data),
            layout: this.mainPanelLayout,
            scrollable: true,
            border: 1,
            panelInstance: this,
            items: [
                fieldSelection, this.fieldConditionPanel
            ]
        });

    },

    getFilterValues: function() {
        if(this.fieldCondition) {
            return this.fieldCondition.getFilterValues();
        }
        return null;
    }

});
