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


pimcore.registerNS("pimcore.bundle.advancedObjectSearch.searchConfig.conditionEntryPanel");
pimcore.bundle.advancedObjectSearch.searchConfig.conditionEntryPanel = Class.create(pimcore.bundle.advancedObjectSearch.searchConfig.conditionAbstractPanel, {

    getConditionPanel: function(panel, data) {
        var niceName = t("bundle_advancedObjectSearch_condition");

        if(typeof data == "undefined") {
            data = {};
        }

        var fieldStore = new Ext.data.JsonStore({
            autoDestroy: true,
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: '/admin/bundle/advanced-object-search/admin/get-fields',
                reader: {
                    rootProperty: 'data',
                    idProperty: 'fieldName'
                },
                extraParams: {class_id: this.classId, type: 'class' }
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

                fieldLabel: t("bundle_advancedObjectSearch_field"),
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
                            if(pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel[fieldSelectionInformation.fieldType]) {
                                this.fieldCondition = new pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel[fieldSelectionInformation.fieldType](fieldSelectionInformation, data, this.classId);
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

        var padding = (this.mainPanelLayout == 'hbox' ? "padding-left: 20px" : "");
        this.fieldConditionPanel = Ext.create('Ext.panel.Panel', {
            flex: 1,
            style: padding
        });

        var myId = Ext.id();

        return Ext.create('Ext.panel.Panel', {
            id: myId,
            bodyStyle: "padding: 15px 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            layout: this.mainPanelLayout,
            scrollable: true,
            border: 1,
            panelInstance: this,
            items: [
                this.fieldSelection, this.fieldConditionPanel
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
