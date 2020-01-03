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


pimcore.registerNS("pimcore.bundle.advancedObjectSearch.searchConfig.resultPanel");
pimcore.bundle.advancedObjectSearch.searchConfig.resultPanel = Class.create(pimcore.object.helpers.gridTabAbstract, {
    systemColumns: ["id", "fullpath", "type", "subtype", "filename", "classname", "creationDate", "modificationDate"],
    noBatchColumns: [],
    batchAppendColumns: [],
    batchRemoveColumns: [],

    getSaveDataCallback: null,
    gridConfigData: {},

    portletMode: false,

    fieldObject: {},
    initialize: function (getSaveDataCallback, gridConfigData, portletMode) {
        this.getSaveDataCallback = getSaveDataCallback;
        this.settings = {};
        this.gridPageSize = 25;

        if (gridConfigData) {
            this.gridConfigData = gridConfigData;
        }

        this.portletMode = portletMode;

        if(!this.portletMode) {
            this.extensionBag = new pimcore.bundle.advancedObjectSearch.searchConfig.ResultPanelExtensionBag(this, typeof gridConfigData != 'undefined' ? gridConfigData.predefinedFilter : null);
            pimcore.plugin.broker.fireEvent("onAdvancedObjectSearchResult", this.extensionBag);
        }
    },

    getLayout: function (initialFilter) {

        this.initialFilter = initialFilter;

        if (this.layout == null) {
            this.layout = new Ext.Panel({
                border: false,
                layout: "fit",
                listeners: {
                    activate: function () {

                        var classId = null;
                        if(this.initialFilter) {
                            classId = this.initialFilter.classId;
                        } else if(this.getSaveDataCallback) {
                            var saveData = this.getSaveDataCallback(true);
                            classId = saveData.classId;
                        }

                        if(classId) {
                            this.updateGrid(classId);
                        }
                    }.bind(this)
                }
            });

            if(!this.portletMode) {
                this.layout.setTitle(t('bundle_advancedObjectSearch_results'));
                this.layout.setIconCls('pimcore_bundle_advancedObjectSearch_grid');

                this.layout.on("destroy", function () {
                    this.extensionBag.destroy();
                    this.extensionBag = null;
                }.bind(this));
            }

            //this is needed
            this.sqlButton = {};
        }

        return this.layout;
    },

    updateGrid: function (classId) {
        this.classId = classId;
        var classStore = pimcore.globalmanager.get("object_types_store");
        var classRecord = classStore.findRecord("id", this.classId);
        if (classRecord) {
            this.selectedClass = classRecord.data.text;

            if (this.gridConfigData.language) {
                this.gridLanguage = this.gridConfigData.language;

                Ext.Ajax.request({
                    url: "/admin/object-helper/grid-get-column-config",
                    params: {name: this.selectedClass, gridtype: "grid"},
                    success: function (response) {
                        response = Ext.decode(response.responseText);
                        var settings = response.settings || {};
                        this.availableConfigs = response.availableConfigs;
                        this.sharedConfigs = response.sharedConfigs;

                        this.createGrid(true, this.gridConfigData.columns, settings);
                    }.bind(this)
                });


            } else {
                Ext.Ajax.request({
                    url: "/admin/object-helper/grid-get-column-config",
                    params: {name: this.selectedClass, gridtype: "grid"},
                    success: this.createGrid.bind(this, false)
                });
            }
        }

    },

    getTableDescription: function () {
        Ext.Ajax.request({
            url: "/admin/object-helper/grid-get-column-config",
            params: {
                id: this.classId,
                gridtype: "grid",
                gridConfigId: this.settings ? this.settings.gridConfigId : null,
                searchType: this.searchType
            },
            success: this.createGrid.bind(this, false)
        });
    },

    createGrid: function (fromConfig, response, settings, save) {
        var fields = [];

        var itemsPerPage = this.gridPageSize;

        if (response.responseText) {
            response = Ext.decode(response.responseText);

            if (response.pageSize) {
                this.gridPageSize = response.pageSize;
                itemsPerPage = response.pageSize;
            }

            fields = response.availableFields;
            this.gridLanguage = response.language;

            this.settings = response.settings || {};
            this.availableConfigs = response.availableConfigs;
            this.sharedConfigs = response.sharedConfigs;
        } else {
            fields = response;
            this.settings = settings;
            if (this.columnConfigButton) {
                this.buildColumnConfigMenu(true);
            }
        }

        this.fieldObject = {};
        for (var i = 0; i < fields.length; i++) {
            this.fieldObject[fields[i].key] = fields[i];
        }

        var gridHelper = new pimcore.object.helpers.grid(
            this.selectedClass,
            fields,
            "/admin/bundle/advanced-object-search/admin/grid-proxy?classId=" + this.classId,
            {
                language: this.gridLanguage
            },
            false
        );

        gridHelper.showSubtype = false;
        gridHelper.showKey = true;
        gridHelper.enableEditor = !this.portletMode;
        gridHelper.limit = itemsPerPage;

        this.store = gridHelper.getStore(this.noBatchColumns, this.batchAppendColumns, this.batchRemoveColumns);
        this.store.setPageSize(itemsPerPage);


        var gridColumns = gridHelper.getGridColumns();

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            width: 40,
            items: [
                {
                    tooltip: t('open'),
                    icon: "/bundles/pimcoreadmin/img/flat-color-icons/cursor.svg",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        pimcore.helpers.openObject(data.id);
                    }.bind(this)
                }
            ]
        });


        this.pagingtoolbar = Ext.create("Ext.PagingToolbar", {
            pageSize: itemsPerPage,
            store: this.store,
            displayInfo: true,
            displayMsg: '{0} - {1} / {2}',
            emptyMsg: t("no_objects_found")
        });

        this.languageInfo = new Ext.Toolbar.TextItem({
            text: t("grid_current_language") + ": " + pimcore.available_languages[this.gridLanguage]
        });

        this.toolbarFilterInfo = new Ext.Button({
            iconCls: "pimcore_icon_filter_condition",
            hidden: true,
            text: '<b>' + t("filter_active") + '</b>',
            tooltip: t("filter_condition"),
            handler: function (button) {
                Ext.MessageBox.alert(t("filter_condition"), button.pimcore_filter_condition);
            }.bind(this)
        });



        this.columnConfigButton = new Ext.SplitButton({
            text: t('grid_options'),
            iconCls: "pimcore_icon_table_col pimcore_icon_overlay_edit",
            handler: function () {
                this.openColumnConfig();
            }.bind(this),
            menu: []
        });

        this.buildColumnConfigMenu(true);

        var tbars = [];
        var plugins = [];

        if(!this.portletMode) {
            var tbar = [this.languageInfo, '-', this.toolbarFilterInfo];
            var secondaryTbar = [];

            if (this.extensionBag.getExtensions().length) {
                this.extensionBag.getExtensions().forEach(function (extension) {
                    if(extension.isInSecondaryTbar()) {
                        secondaryTbar.push(extension.getLayout());
                    } else {
                        tbar.push(extension.getLayout());
                    }
                }.bind(this));
            }

            this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1
            });
            plugins.push(this.cellEditing);

            tbar = tbar.concat(['->', "-", {
                text: t("export_csv"),
                iconCls: "pimcore_icon_export",
                handler: function () {

                    Ext.MessageBox.show({
                        title: t('warning'),
                        msg: t('csv_object_export_warning'),
                        buttons: Ext.Msg.OKCANCEL,
                        fn: function (btn) {
                            if (btn == 'ok') {
                                this.exportPrepare();
                            }
                        }.bind(this),
                        icon: Ext.MessageBox.WARNING
                    });


                }.bind(this)
            }, "-", this.columnConfigButton
            ]);

            tbars = [{
                xtype: 'toolbar',
                dock: 'top',
                items: tbar
            }];

            if(secondaryTbar.length) {
                tbars.unshift(
                    {
                        xtype: 'toolbar',
                        dock: 'top',
                        items: secondaryTbar
                    }
                );
            }
        }

        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            store: this.store,
            border: true,
            columns: gridColumns,
            columnLines: true,
            plugins: plugins,
            stripeRows: true,
            cls: 'pimcore_object_grid_panel',
            bodyCls: "pimcore_editable_grid",
            trackMouseOver: true,
            viewConfig: {
                forceFit: false,
                xtype: 'patchedgridview'
            },
            sortableColumns: false,
            selModel: gridHelper.getSelectionColumn(),
            bbar: this.pagingtoolbar,
            dockedItems: tbars
        });
        this.grid.on("rowcontextmenu", this.onRowContextmenu.bind(this));

        if(!this.portletMode) {
            this.grid.on("afterrender", function (grid) {
                this.updateGridHeaderContextMenu(grid);
            }.bind(this));
        }

        this.grid.on("sortchange", function (grid, sortinfo) {
            this.sortinfo = sortinfo;
        }.bind(this));

        // check for filter updates
        this.grid.on("filterchange", function () {
            this.filterUpdateFunction(this.grid, this.toolbarFilterInfo);
        }.bind(this));

        gridHelper.applyGridEvents(this.grid);

        var proxy = this.store.getProxy();

        if(this.initialFilter) {
            proxy.extraParams.filter = Ext.encode(this.initialFilter);
            this.initialFilter = null;
        } else {
            proxy.extraParams.filter = this.getSaveDataCallback();
        }

        if(this.extensionBag) {
            this.extensionBag.addCustomFilter();
        }

        this.store.load();

        this.layout.removeAll();
        this.layout.add(this.grid);
        this.layout.updateLayout();
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts) {

        var menu = new Ext.menu.Menu();
        var data = grid.getStore().getAt(rowIndex);
        var selectedRows = grid.getSelectionModel().getSelection();

        if (selectedRows.length <= 1) {

            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: "pimcore_icon_open",
                handler: function (data) {
                    pimcore.helpers.openObject(data.data.id, "object");
                }.bind(this, data)
            }));
            menu.add(new Ext.menu.Item({
                text: t('show_in_tree'),
                iconCls: "pimcore_icon_show_in_tree",
                handler: function () {
                    try {
                        try {
                            pimcore.treenodelocator.showInTree(record.id, "object", this);
                        } catch (e) {
                            console.log(e);
                        }

                    } catch (e2) {
                        console.log(e2);
                    }
                }
            }));

        } else {
            menu.add(new Ext.menu.Item({
                text: t('open_selected'),
                iconCls: "pimcore_icon_open",
                handler: function (data) {
                    var selectedRows = grid.getSelectionModel().getSelection();
                    for (var i = 0; i < selectedRows.length; i++) {
                        pimcore.helpers.openObject(selectedRows[i].data.id, "object");
                    }
                }.bind(this, data)
            }));
        }

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },

    batchPrepare: function (columnIndex, onlySelected, append, remove) {
        // no batch for system properties
        if (this.systemColumns.indexOf(this.grid.getColumns()[columnIndex].dataIndex) > -1) {
            return;
        }

        var jobs = [];
        if (onlySelected) {
            var selectedRows = this.grid.getSelectionModel().getSelection();
            for (var i = 0; i < selectedRows.length; i++) {
                jobs.push(selectedRows[i].get("id"));
            }
            this.batchOpen(columnIndex, jobs, append, remove);

        } else {

            var filters = "";
            var condition = "";

            if (this.sqlButton.pressed) {
                condition = this.sqlEditor.getValue();
            } else {
                var filterData = this.store.getFilters().items;
                if (filterData.length > 0) {
                    filters = this.store.getProxy().encodeFilters(filterData);
                }
            }

            var params = {
                filter: this.getSaveDataCallback(),
                classId: this.classId,
                objecttype: this.objecttype,
                language: this.gridLanguage,
                customFilter: Ext.encode(this.extensionBag.getFilterData())
            };


            Ext.Ajax.request({
                url: "/admin/bundle/advanced-object-search/admin/get-batch-jobs",
                method: "POST",
                params: params,
                success: function (columnIndex, response) {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata.success && rdata.jobs) {
                        this.batchOpen(columnIndex, rdata.jobs, append, remove);
                    }

                }.bind(this, columnIndex)
            });
        }

    },

    exportPrepare: function () {
        var jobs = [];

        var fields = this.getGridConfig().columns;
        var fieldKeys = Object.keys(fields);

        //create the ids array which contains chosen rows to export
        var ids = [];
        var selectedRows = this.grid.getSelectionModel().getSelection();
        for (var i = 0; i < selectedRows.length; i++) {
            ids.push(selectedRows[i].get("id"));
        }

        var params = {
            filter: this.getSaveDataCallback(),
            classId: this.classId,
            objecttype: this.objecttype,
            language: this.gridLanguage,
            "ids[]": ids,
            "fields[]": fieldKeys,
            customFilter: Ext.encode(this.extensionBag.getFilterData())
        };

        Ext.Ajax.request({
            url: "/admin/bundle/advanced-object-search/admin/get-export-jobs",
            params: params,
            method: "POST",
            success: function (response) {
                var rdata = Ext.decode(response.responseText);

                var fields = this.getGridConfig().columns;
                var fieldKeys = Object.keys(fields);

                if (rdata.success && rdata.jobs) {
                    this.exportProcess(rdata.jobs, rdata.fileHandle, fieldKeys, true);
                }

            }.bind(this)
        });
    },

    openColumnConfig: function () {
        var fields = this.getGridConfig().columns;

        var fieldKeys = Object.keys(fields);

        var visibleColumns = [];
        for(var i = 0; i < fieldKeys.length; i++) {
            var field = fields[fieldKeys[i]];
            if(!field.hidden) {
                var fc = {
                    key: fieldKeys[i],
                    label: field.fieldConfig.label,
                    dataType: field.fieldConfig.type,
                    layout: field.fieldConfig.layout
                };
                if (field.fieldConfig.width) {
                    fc.width = field.fieldConfig.width;
                }

                if (field.isOperator) {
                    fc.isOperator = true;
                    fc.attributes = field.fieldConfig.attributes;

                }

                visibleColumns.push(fc);
            }
        }

        var objectId;
        if (this["object"] && this.object["id"]) {
            objectId = this.object.id;
        } else if (this["element"] && this.element["id"]) {
            objectId = this.element.id;
        }

        var columnConfig = {
            language: this.gridLanguage,
            classid: this.classId,
            objectId: objectId,
            selectedGridColumns: visibleColumns,
            pageSize: this.gridPageSize
        };
        var dialog = new pimcore.object.helpers.gridConfigDialog(columnConfig, function (data) {
                this.gridLanguage = data.language;
                this.gridPageSize = data.pageSize;
                this.createGrid(true, data.columns, this.settings);
            }.bind(this), function (data) {
                Ext.Ajax.request({
                    url: "/admin/object-helper/grid-get-column-config",
                    params: {name: this.selectedClass, gridtype: "grid"},
                    success: this.createGrid.bind(this, false)
                });
            }.bind(this)
        )
    },

    getSaveData: function () {
        if (this.grid) {
            var config = this.getGridConfig();
            var gridColumns = this.grid.getView().getHeaderCt().getGridColumns();
            var columnsConfig = [];
            var keys = Object.keys(config.columns);

            for (var i = 0; i < keys.length; i++) {
                var entry = config.columns[keys[i]].fieldConfig;

                if (entry) {
                    entry.position = config.columns[keys[i]].position;

                    // store widths according to extjs
                    if (entry.layout || entry.isOperator) {
                        for (var j = 0; j < gridColumns.length; j++) {
                            var column = gridColumns[j];

                            if (column.dataIndex === entry.key && column.width != 100) {
                                if (entry.isOperator) {
                                    // operator columns need the width directly on the entry
                                    entry.width = column.width;
                                } else {
                                    // basic columns on the layout object
                                    entry.layout.width = column.width;
                                }

                                break;
                            }
                        }
                    }

                    columnsConfig.push(entry);
                }
            }

            config.columns = columnsConfig;

            this.gridConfigData.columns = columnsConfig;

            return config;
        } else {
            return this.gridConfigData;
        }
    }

});

/**
 * https://github.com/pimcore/advanced-object-search/issues/64
 * TODO pimcore.object.helpers.gridcolumnconfig for BC reasons, to be removed with next major version
 */
if (pimcore.object.helpers.gridcolumnconfig) {
    pimcore.bundle.advancedObjectSearch.searchConfig.resultPanel.addMethods(pimcore.object.helpers.gridcolumnconfig);
} else {
    pimcore.bundle.advancedObjectSearch.searchConfig.resultPanel.addMethods(pimcore.element.helpers.gridColumnConfig);
}
