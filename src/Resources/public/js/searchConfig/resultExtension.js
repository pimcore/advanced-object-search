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


pimcore.registerNS("pimcore.bundle.advancedObjectSearch.searchConfig.");
pimcore.bundle.advancedObjectSearch.searchConfig.ResultExtension = Class.create({
    initialize: function () {
        this.extensionBag = null;
    },

    getExtensionBag: function () {
        return this.extensionBag;
    },

    setExtensionBag: function (extensionBag) {
        this.extensionBag = extensionBag;
    },

    supports: function (extensionBag) {
        return true;
    },

    getLayout: function () {
        return "-";
    },

    getFilterData: function () {
        return {};
    },

    isInSecondaryTbar: function() {
        return false;
    }
});

pimcore.bundle.advancedObjectSearch.searchConfig.ResultPanelExtensionBag = Class.create({
    initialize: function (panel, predefinedFilter) {
        this.predefinedFilter = predefinedFilter;
        this.searchConfig = panel.gridConfigData;
        this.panel = panel;
        this.extensions = [];
    },

    destroy: function () {
        // cleanup
        this.extensions = [];
        this.panel = null;
        this.searchConfig = null;
    },

    addExtension: function (extension) {
        if (!extension.supports(this)) {
            return;
        }

        extension.setExtensionBag(this);
        this.extensions.push(extension);
    },

    getPredefinedFilter: function (key) {
        if (!this.predefinedFilter) {
            return null;
        }

        var filter = this.predefinedFilter[key];

        delete this.predefinedFilter[key];

        return filter;
    },

    getExtensions: function () {
        return this.extensions;
    },

    isClassId: function (id) {
        try {
            return this.searchConfig.classId == id;
        } catch (exception) {
            return false;
        }
    },

    hasField: function (fieldname) {
        return this._hasFieldRecursive(fieldname, this.panel.gridConfigData.columns);
    },

    _hasFieldRecursive: function (fieldname, columns) {
        try {
            for (var i = 0; i < columns.length; i++) {
                var column = columns[i];

                if (column.isOperator && this._hasFieldRecursive(fieldname, column.attributes.childs)) {
                    return true
                } else if (column.attribute == fieldname || column.key == fieldname) {
                    return true;
                }
            }

            return false;
        } catch (exception) {
            return false;
        }
    },

    hasOperator: function (config) {
        return this._hasOperatorRecursive(config, this.panel.gridConfigData.columns);
    },

    _hasOperatorRecursive: function (config, columns) {
        try {
            for (var i = 0; i < columns.length; i++) {
                var column = columns[i];

                if (column.isOperator && this._isSearchedOperator(column, config)) {
                    return true;
                }

                if (column.isOperator) {
                    this._hasOperatorRecursive(config, column.attributes.childs);
                }
            }
        } catch (exception) {
            return false;
        }
    },

    _isSearchedOperator: function (operator, config) {
        var valid = true;

        Ext.iterate(config, function (key, value) {
            if (operator.attributes[key] != value) {
                valid = false;
            }
        });

        return valid;
    },

    getFilterData: function () {
        var filterData = {};

        this.extensions.forEach(function (extension) {
            Ext.merge(filterData, extension.getFilterData());
        });

        if (this.predefinedFilter && Object.keys(this.predefinedFilter).length) {
            Ext.merge(filterData, this.predefinedFilter);
        }

        return filterData;
    },

    addCustomFilter: function () {
        if (this.panel && this.panel.store) {
            this.panel.store.getProxy().extraParams.customFilter = Ext.encode(this.getFilterData());
        }
    },

    update: function () {
        if (this.panel && this.panel.store) {
            this.addCustomFilter();
            this.panel.store.load();
        }
    }
});