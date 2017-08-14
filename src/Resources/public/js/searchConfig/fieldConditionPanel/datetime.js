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


pimcore.registerNS("pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.datetime");
pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.datetime = Class.create(pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.numeric, {

    showTimeField: true,
    showDateField: true,

    getConditionPanel: function() {

        var termFieldValue = "";
        var operatorValue = "";
        if(typeof this.data.filterEntryData === "string") {

            termFieldValue = this.data.filterEntryData;
            operatorValue = "eq";


        } else if(this.data.filterEntryData) {
            var key = Object.keys(this.data.filterEntryData)[0];
            if(key) {
                termFieldValue = this.data.filterEntryData[key];
                operatorValue = key;
            }

        }

        var dateObject = null;
        if(termFieldValue) {
            dateObject = new Date(Date.parse(termFieldValue));
        }

        this.datefield = Ext.create('Ext.form.field.Date', {
            width: 180,
            value: dateObject,
            hidden: !this.showDateField

        });
        this.timefield = Ext.create('Ext.form.field.Time', {
            width: 90,
            format:"H:i",
            emptyText:"",
            value: dateObject,
            hidden: !this.showTimeField
        });

        this.inheritanceField = Ext.create('Ext.form.field.Checkbox',
            {
                fieldLabel:  t("bundle_advancedObjectSearch_ignoreInheritance"),
                style: "padding-left: 20px",
                value: this.data.ignoreInheritance,
                hidden: !this.fieldSelectionInformation.context.classInheritanceEnabled
            }
        );

        var operatorCombobox = this.getOperatorCombobox(operatorValue);
        operatorCombobox.on('change', function( item, newValue, oldValue, eOpts ) {
            if(this.datefield) {
                this.datefield.setDisabled(newValue == "exists" || newValue == "not_exists");
            }
            if(this.timefield) {
                this.timefield.setDisabled(newValue == "exists" || newValue == "not_exists");
            }
        }.bind(this));


        return Ext.create('Ext.panel.Panel', {
            layout: 'hbox',
            items: [
                operatorCombobox,
                {
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
                    fieldLabel: t("bundle_advancedObjectSearch_date"),
                    style: "padding-left: 20px",
                    items: [this.datefield, this.timefield]
                },
                this.inheritanceField
            ]
        });
    },


    getFilterValues: function() {

        var filterEntryData = {};
        var operatorFieldValue = this.operatorField.getValue();


        var value = this.datefield.getValue();
        var dateString = "";
        if(value) {

            if (this.timefield.getValue()) {
                dateString = Ext.Date.format(value, "Y-m-d");
                var timeValue = this.timefield.getValue();
                timeValue = Ext.Date.format(timeValue, "H:i:s");

                dateString += "T" +  timeValue;

            }
            else {
                dateString = Ext.Date.format(value, 'Y-m-d\\TH:i:s');
            }
        }


        if(operatorFieldValue == "eq") {
            filterEntryData = dateString
        } else {
            filterEntryData[operatorFieldValue] = dateString;
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
