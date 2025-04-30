/**
* This source file is available under the terms of the
* Pimcore Open Core License (POCL)
* Full copyright and license information is available in
* LICENSE.md which is distributed with this source code.
*
*  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.com)
*  @license    Pimcore Open Core License (POCL)
*/


pimcore.registerNS("pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.numeric");
pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.numeric = Class.create(pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.default, {

    getConditionPanel: function() {

        var termFieldValue = "",
            operatorValue = "";

        if(this.data.filterEntryData && isNaN(this.data.filterEntryData)) {

            var key = Object.keys(this.data.filterEntryData)[0];
            if(key) {
                termFieldValue = this.data.filterEntryData[key];
                operatorValue = key;
            }
        } else {
            termFieldValue = this.data.filterEntryData;
            operatorValue = "eq";
        }

        this.termField = Ext.create('Ext.form.field.Number',
            {
                width: 400,
                style: "padding-left: 20px",
                fieldLabel:  t("bundle_advancedObjectSearch_term"),
                value: termFieldValue
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
                this.getOperatorCombobox(operatorValue),
                this.termField,
                this.inheritanceField
            ]
        });
    },

    getFilterValues: function() {

        var filterEntryData = {},
            operator = "must",
            operatorFieldValue = this.operatorField.getValue();

        if(operatorFieldValue == "eq") {
            filterEntryData = this.termField.getValue()
        } else {
            filterEntryData[operatorFieldValue] = this.termField.getValue();
        }

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
