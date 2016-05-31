
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.href");
pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.href = Class.create(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.default, {

    getConditionPanel: function() {

        this.subPanel = Ext.create('Ext.panel.Panel', {});

        var typeStore =  Ext.create('Ext.data.ArrayStore', {
            fields: [ 'key', 'label'],
            data: this.fieldSelectionInformation.context.allowedTypes
        });

        this.typeField = Ext.create('Ext.form.ComboBox',
            {
                fieldLabel: t("plugin_esbackendsearch_type"),
                store: typeStore,
                // value: data.condition,
                queryMode: 'local',
                width: 300,
                forceSelection: true,
                valueField: 'key',
                displayField: 'label',
                listeners: {
                    change: function( item, newValue, oldValue, eOpts ) {
                        this.subPanel.removeAll();
                        if(newValue != "object_filter") {

                            this.idsField = Ext.create('Ext.form.field.Text',
                                {
                                    fieldLabel:  t("plugin_esbackendsearch_ids"),
                                    width: 400,
                                    value: this.data.filterEntryData && this.data.filterEntryData.id ? this.data.filterEntryData.id.join() : ""
                                }
                            );

                            this.subPanel.add(this.idsField);
                        } else {

                            var classStore = pimcore.globalmanager.get("object_types_store");
                            var filteredClassStore = null;

                            console.log(this.fieldSelectionInformation);

                            if(this.fieldSelectionInformation.context.allowedClasses.length) {
                                var filteredClassStore = Ext.create('Ext.data.Store', {});

                                classStore.each(function(record) {
                                    if(this.fieldSelectionInformation.context.allowedClasses.indexOf(record.data.text) > -1) {
                                        filteredClassStore.add(record)
                                    }
                                }.bind(this));
                            } else {
                                filteredClassStore = classStore;
                            }


                            this.classSelection = Ext.create('Ext.form.ComboBox',
                                {
                                    fieldLabel: t("plugin_esbackendsearch_subclass"),
                                    store: filteredClassStore,
                                    valueField: 'id',
                                    displayField: 'translatedText',
                                    triggerAction: 'all',
                                    value: this.data.filterEntryData ? this.data.filterEntryData.classId : "",
                                    queryMode: 'local',
                                    width: 300,
                                    forceSelection: true,
                                    listeners: {
                                        change: function( item, newValue, oldValue, eOpts ) {

                                            if(newValue != oldValue) {
                                                this.subConditionsPanel.removeAll();
                                                this.subConditions = new pimcore.plugin.esbackendsearch.searchConfig.conditionPanel(newValue, null, "auto");
                                                this.subConditionsPanel.add(this.subConditions.getConditionPanel());
                                            }

                                        }.bind(this)
                                    }
                                }
                            );

                            this.subConditionsPanel = Ext.create('Ext.panel.Panel', {});

                            if(this.data.filterEntryData && this.data.filterEntryData.classId) {
                                this.subConditions = new pimcore.plugin.esbackendsearch.searchConfig.conditionPanel(this.data.filterEntryData.classId, this.data.filterEntryData, "auto");
                                this.subConditionsPanel.add(this.subConditions.getConditionPanel());
                            }

                            this.subPanel.add(this.classSelection, this.subConditionsPanel);
                            pimcore.layout.refresh();

                        }
                    }.bind(this)
                }
            }
        );

        if(this.data.filterEntryData) {
            if(this.data.filterEntryData.id) {
                this.typeField.setValue("object");
            } else {
                this.typeField.setValue("object_filter");
            }
        }

        return Ext.create('Ext.panel.Panel', {
            items: [
                this.typeField,
                this.subPanel
            ]

        });
    },

    getFilterValues: function() {

        var subValue = {};

        if(this.typeField.getValue() == "object_filter") {

            subValue.type = "object";
            subValue.classId = this.classSelection.getValue();
            if(this.subConditions) {
                var saveData = this.subConditions.getSaveData();
                subValue.filters = saveData.filters;
                subValue.fulltextSearchTerm = saveData.fulltextSearchTerm;
            }

        } else {

            subValue.type = this.typeField.getValue();
            if(this.idsField) {
                subValue.id = this.idsField.getValue().split(",");
            }

        }

        return {
            "fieldname": this.fieldSelectionInformation.fieldName,
            "filterEntryData": subValue
        };
    }


});
