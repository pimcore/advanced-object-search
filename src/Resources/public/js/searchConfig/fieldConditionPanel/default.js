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


pimcore.registerNS("pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.default");
pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.default = Class.create({

    fieldSelectionInformation: null,
    data: {},
    termField: null,
    operatorField: null,
    inheritanceField: null,
    classId: null,

    initialize: function(fieldSelectionInformation, data, classId) {
        this.fieldSelectionInformation = fieldSelectionInformation;
        this.classId = classId;
        if(data) {
            this.data = data;
        }
    },

    getConditionPanel: function() {

        this.termField = Ext.create('Ext.form.field.Text',
            {
                fieldLabel:  t("bundle_advancedObjectSearch_term"),
                width: 400,
                style: "padding-left: 20px",
                value: this.data.filterEntryData
            }
        );

        this.inheritanceField = Ext.create('Ext.form.field.Checkbox',
            {
                fieldLabel:  t("bundle_advancedObjectSearch_ignoreInheritance"),
                style: "padding-left: 20px",
                value: this.data.ignoreInheritance,
                hidden: !this.fieldSelectionInformation.context.classInheritanceEnabled
            }
        );

        return Ext.create('Ext.panel.Panel', {
            layout: 'hbox',
            items: [
                this.getOperatorCombobox(this.data.operator),
                this.termField,
                this.inheritanceField
            ]
        });
    },

    getOperatorCombobox: function(value) {
        this.operatorField = Ext.create('Ext.form.ComboBox',
            {

                fieldLabel:  t("bundle_advancedObjectSearch_operator"),
                store: this.fieldSelectionInformation.context.operators,
                value: value,
                queryMode: 'local',
                width: 300,
                valueField: 'fieldName',
                displayField: 'fieldLabel',
                listeners: {
                    change: function( item, newValue, oldValue, eOpts ) {

                        if(this.termField) {
                            this.termField.setDisabled(newValue == "exists" || newValue == "not_exists");
                        }

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
            operator: this.operatorField.getValue(),
            ignoreInheritance: this.inheritanceField.getValue()
        };

    }


});
