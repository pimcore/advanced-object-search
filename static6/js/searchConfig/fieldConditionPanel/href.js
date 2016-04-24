
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
                                    width: 400
                                }
                            );

                            this.subPanel.add(this.idsField);
                        } else {

                            var classStore = pimcore.globalmanager.get("object_types_store");
                            var filteredClassStore = null;

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
                                    // value: data.condition,
                                    queryMode: 'local',
                                    width: 300,
                                    forceSelection: true,
                                    listeners: {
                                        change: function( item, newValue, oldValue, eOpts ) {

                                            if(newValue != oldValue) {
                                                this.subConditionsPanel.removeAll();
                                                this.subConditions = new pimcore.plugin.esbackendsearch.searchConfig.conditionPanel(newValue, "auto");
                                                this.subConditionsPanel.add(this.subConditions.getConditionPanel());
                                            }

                                        }.bind(this)
                                    }
                                }
                            );

                            this.subConditionsPanel = Ext.create('Ext.panel.Panel', {});


                            this.subPanel.add(this.classSelection, this.subConditionsPanel);
                            pimcore.layout.refresh();

                        }
                    }.bind(this)
                }
            }
        );

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
            var saveData = this.subConditions.getSaveData();
            subValue.filters = saveData.filters;
            subValue.fulltextSearchTerm = saveData.fulltextSearchTerm;

        } else {

            subValue.type = this.typeField.getValue();
            subValue.id = this.idsField.getValue().split(",");

        }

        return {
            "fieldname": this.fieldSelectionInformation.fieldName,
            "filterEntryData": subValue
        };
    }


});
