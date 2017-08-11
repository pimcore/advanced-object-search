
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.fieldcollections");
pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.fieldcollections = Class.create(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.default, {

    collectionType: 'fieldcollection',

    getConditionPanel: function() {

        this.subPanel = Ext.create('Ext.panel.Panel', {});
        this.fieldConditionPanel = Ext.create('Ext.panel.Panel', {
            flex: 1
        });

        var typeStore =  Ext.create('Ext.data.ArrayStore', {
            fields: ['key'],
            data: this.fieldSelectionInformation.context.allowedTypes
        });

        this.typeField = Ext.create('Ext.form.ComboBox',
            {
                fieldLabel: t("plugin_esbackendsearch_type"),
                store: typeStore,
                queryMode: 'local',
                width: 300,
                forceSelection: true,
                valueField: 'key',
                displayField: 'key',
                listeners: {
                    change: function( item, newValue, oldValue, eOpts ) {

                        this.subPanel.removeAll();
                        this.fieldConditionPanel.removeAll();
                        if(newValue) {
                            this.subPanel.add(this.buildFieldSelection(newValue));
                        }
                        pimcore.layout.refresh();

                        //reset data after first load
                        this.data.filterEntryData = {};

                    }.bind(this)
                }
            }
        );

        if (this.data.filterEntryData && this.data.filterEntryData.type) {
            this.typeField.setValue(this.data.filterEntryData.type);
        }

        return Ext.create('Ext.panel.Panel', {
            items: [
                {
                    xtype: 'panel',
                    layout: 'hbox',
                    style: "padding-bottom: 10px",
                    items: [
                        this.typeField
                    ]
                },
                this.subPanel,
                this.fieldConditionPanel
            ]
        });
    },


    buildFieldSelection: function(collectionTypeKey) {

        var data = this.data ? (this.data.filterEntryData ? this.data.filterEntryData.filterCondition : null) : null;

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
                extraParams: { key: collectionTypeKey, type: this.collectionType, class_id: this.classId }
            },
            fields: ['fieldName','fieldLabel', 'fieldType', 'context'],
            listeners: {
                load: function (store) {
                    if(data.fieldname) {

                        if(data.fieldname == "localizedfields") {
                            //need to get real fieldname of localized fields
                            var language = Object.keys(data.filterEntryData)[0];
                            if(language) {
                                var fieldname = data.filterEntryData[language][0].fieldname;
                                if(fieldname) {
                                    this.fieldSelection.setValue(fieldname);
                                }
                            }

                        } else {
                            this.fieldSelection.setValue(data.fieldname);
                        }

                    }
                }.bind(this)
            }
        });

        this.fieldSelection = Ext.create('Ext.form.ComboBox',
            {

                fieldLabel: t("plugin_esbackendsearch_field"),
                name: "condition",
                store: fieldStore,
                queryMode: 'local',
                width: 400,
                valueField: 'fieldName',
                displayField: 'fieldLabel',
                listeners: {
                    change: function( item, newValue, oldValue, eOpts ) {
                        var record = item.getStore().findRecord('fieldName', newValue);
                        if(record) {
                            var fieldSelectionInformation = record.data;

                            this.fieldConditionPanel.removeAll();
                            if(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel[fieldSelectionInformation.fieldType]) {
                                this.fieldCondition = new pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel[fieldSelectionInformation.fieldType](fieldSelectionInformation, data);
                                this.fieldConditionPanel.add(this.fieldCondition.getConditionPanel());
                            } else {
                                console.log("ERROR - no implementation for field condition panel for " + fieldSelectionInformation.fieldType);
                            }

                            //after first change, reset data
                            data = {};
                        }

                    }.bind(this)
                }
            }
        );

        return this.fieldSelection;
    },




    getFilterValues: function() {

        var subValue = {};

        if(this.fieldCondition) {
            subValue.type = this.typeField.getValue();
            subValue.filterCondition = this.fieldCondition.getFilterValues();
        }

        return {
            fieldname: this.fieldSelectionInformation.fieldName,
            filterEntryData: subValue
        };
    }


});
