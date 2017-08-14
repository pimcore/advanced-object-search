
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.time");
pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.time = Class.create(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel.datetime, {

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
