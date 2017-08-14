/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */


pimcore.registerNS("pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.href");
pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.href = Class.create(pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.default, {

    inheritanceField: null,

    getConditionPanel: function() {

        this.subPanel = Ext.create('Ext.panel.Panel', {});

        var typeStore =  Ext.create('Ext.data.ArrayStore', {
            fields: [ 'key', 'label'],
            data: this.fieldSelectionInformation.context.allowedTypes
        });

        this.typeField = Ext.create('Ext.form.ComboBox',
            {
                fieldLabel: t("bundle_advancedObjectSearch_type"),
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
                                    fieldLabel:  t("bundle_advancedObjectSearch_ids"),
                                    width: 400,
                                    value: this.data.filterEntryData && this.data.filterEntryData.id ? this.data.filterEntryData.id.join() : ""
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
                                    fieldLabel: t("bundle_advancedObjectSearch_subclass"),
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
                                                this.subConditions = new pimcore.bundle.advancedObjectSearch.searchConfig.conditionPanel(newValue, null, "auto");
                                                this.subConditionsPanel.add(this.subConditions.getConditionPanel());
                                            }

                                        }.bind(this)
                                    }
                                }
                            );

                            this.subConditionsPanel = Ext.create('Ext.panel.Panel', {});

                            if(this.data.filterEntryData && this.data.filterEntryData.classId) {
                                this.subConditions = new pimcore.bundle.advancedObjectSearch.searchConfig.conditionPanel(this.data.filterEntryData.classId, this.data.filterEntryData, "auto");
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

        this.inheritanceField = Ext.create('Ext.form.field.Checkbox',
            {
                fieldLabel:  t("bundle_advancedObjectSearch_ignoreInheritance"),
                style: "padding-left: 20px",
                value: this.data.ignoreInheritance,
                hidden: !this.fieldSelectionInformation.context.classInheritanceEnabled
            }
        );

        return Ext.create('Ext.panel.Panel', {
            items: [
                {
                    xtype: 'panel',
                    layout: 'hbox',
                    style: "padding-bottom: 10px",
                    items: [
                        this.typeField,
                        this.inheritanceField
                    ]
                },
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
            fieldname: this.fieldSelectionInformation.fieldName,
            filterEntryData: subValue,
            ignoreInheritance: this.inheritanceField.getValue()
        };
    }


});
