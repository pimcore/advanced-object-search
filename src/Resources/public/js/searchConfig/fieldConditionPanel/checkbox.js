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


pimcore.registerNS("pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.checkbox");
pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.checkbox = Class.create(pimcore.bundle.advancedObjectSearch.searchConfig.fieldConditionPanel.default, {

    getConditionPanel: function() {

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
                this.inheritanceField
            ]
        });
    },

    getFilterValues: function() {

       return {
            fieldname: this.fieldSelectionInformation.fieldName,
            filterEntryData: this.operatorField.getValue() == "must",
            operator: "must",
            ignoreInheritance: this.inheritanceField.getValue()
        };

    }


});
