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


pimcore.registerNS("pimcore.bundle.advancedObjectSearch.searchConfig.conditionGroupPanel");
pimcore.bundle.advancedObjectSearch.searchConfig.conditionGroupPanel = Class.create(pimcore.bundle.advancedObjectSearch.searchConfig.conditionAbstractPanel, {

    getConditionPanel: function(panel, data) {
        var niceName = t("bundle_advancedObjectSearch_group");

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
                this.getInnerConditionPanel(myId, data)
            ]
        });

    },

    getInnerConditionPanel: function(myId, data) {
        var helper = new pimcore.bundle.advancedObjectSearch.searchConfig.conditionPanelContainerBuilder(this.classId, this, myId, this.conditionEntryPanelLayout);
        this.conditionsContainerInner = helper.buildConditionsContainerInner();

        if(data.filterEntryData) {
            helper.populateConditionsContainerInner(data.filterEntryData);
        }

        return Ext.create('Ext.panel.Panel',{
            border: false,
            width: "100%",
            items: [
                this.getOperatorCombobox(data),
                this.conditionsContainerInner
            ]
        });
    },

    getOperatorCombobox: function(data) {
        this.operatorField = Ext.create('Ext.form.ComboBox',
            {

                fieldLabel:  t("bundle_advancedObjectSearch_operator"),
                store: ["must", "should"],
                value: data ? data.operator : "",
                queryMode: 'local',
                width: 300,
                listeners: {
                    change: function( item, newValue, oldValue, eOpts ) {

                    }.bind(this)
                }
            }
        );

        return this.operatorField;
    },

    getFilterValues: function() {

        var conditionsData = [];
        if(this.conditionsContainerInner) {
            var conditions = this.conditionsContainerInner.items.getRange();
            for (var i=0; i<conditions.length; i++) {
                var condition = conditions[i].panelInstance.getFilterValues();
                conditionsData.push(condition);
            }
            return {
                "filterEntryData": conditionsData,
                "operator": this.operatorField.getValue(),
                "fieldname": "~~group~~"
            };
        }

        return null;
    }

});
