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


pimcore.registerNS("pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.time");
pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.time = Class.create(pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.datetime, {

    showDateField: false,

    getFilterValues: function() {

        var filterEntryData = {};
        var operatorFieldValue = this.operatorField.getValue();

        var dateString = "0000-01-01";
        if (this.timefield.getValue()) {
            var timeValue = this.timefield.getValue();
            timeValue = Ext.Date.format(timeValue, "H:i:s");

            dateString += "T" +  timeValue;
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
