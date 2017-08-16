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

    parent: null,
    gridConfigData: {},

    fieldObject: {},
    initialize: function(parent, gridConfigData) {
        this.parent = parent;
        if(gridConfigData) {
            this.gridConfigData = gridConfigData;
        }
    },

    getLayout: function () {

        if (this.layout == null) {
            this.layout = new Ext.Panel({
                title: t('bundle_advancedObjectSearch_results'),
                border: false,
                iconCls: "pimcore_bundle_advancedObjectSearch_grid",
                layout: "fit",
                listeners: {
                    activate: function() {
                        if(this.parent) {
                            var saveData = this.parent.getSaveData(true);
                            this.updateGrid(saveData.classId);
                        }
                    }.bind(this)
                }
            });

            //this is needed
            this.sqlButton = {};
        }

        return this.layout;
    },

    updateGrid: function(classId) {
        this.classId = classId;
        var classStore = pimcore.globalmanager.get("object_types_store");
        var classRecord = classStore.findRecord("id", this.classId);
        if(classRecord) {
            this.selectedClass = classRecord.data.text;

            if (this.gridConfigData.language) {
                this.gridLanguage = this.gridConfigData.language;
                this.createGrid(true, this.gridConfigData.columns);
            } else {
                Ext.Ajax.request({
                    url: "/admin/object-helper/grid-get-column-config",
                    params: {name: this.selectedClass, gridtype: "grid"},
                    success: this.createGrid.bind(this, false)
                });
            }
        }

    },

    createGrid: function(fromConfig, response) {
        var fields = [];

        var itemsPerPage = 20;

        if (response.responseText) {
            response = Ext.decode(response.responseText);

            if (response.pageSize) {
                itemsPerPage = response.pageSize;
            }

            fields = response.availableFields;
            this.gridLanguage = response.language;
        } else {
            fields = response;
        }

        this.fieldObject = {};
        for(var i = 0; i < fields.length; i++) {
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
        gridHelper.enableEditor = true;
        gridHelper.limit = itemsPerPage;

        this.store = gridHelper.getStore();
        this.store.setPageSize(itemsPerPage);

        var proxy = this.store.getProxy();
        // proxy.setActionMethods({
        //     create : 'POST',
        //     read   : 'POST',
        //     update : 'POST',
        //     destroy: 'POST'
        // });

        proxy.extraParams.filter = this.parent.getSaveData();

        var gridColumns = gridHelper.getGridColumns();

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            width: 40,
            items: [
                {
                    tooltip: t('open'),
                    icon: "/pimcore/static6/img/flat-color-icons/cursor.svg",
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

        this.toolbarFilterInfo =  new Ext.Button({
            iconCls: "pimcore_icon_filter_condition",
            hidden: true,
            text: '<b>' + t("filter_active") + '</b>',
            tooltip: t("filter_condition"),
            handler: function (button) {
                Ext.MessageBox.alert(t("filter_condition"), button.pimcore_filter_condition);
            }.bind(this)
        });

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        var plugins = [this.cellEditing ];

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
            tbar: [
                this.languageInfo, '-', this.toolbarFilterInfo, '->'
                ,"-",{
                    text: t("export_csv"),
                    iconCls: "pimcore_icon_export",
                    handler: function(){

                        Ext.MessageBox.show({
                            title:t('warning'),
                            msg: t('csv_object_export_warning'),
                            buttons: Ext.Msg.OKCANCEL ,
                            fn: function(btn){
                                if (btn == 'ok'){
                                    this.exportPrepare();
                                }
                            }.bind(this),
                            icon: Ext.MessageBox.WARNING
                        });



                    }.bind(this)
                },"-",{
                    text: t("grid_column_config"),
                    iconCls: "pimcore_icon_table_col pimcore_icon_overlay_edit",
                    handler: this.openColumnConfig.bind(this)
                } 
            ],
            listeners: {
                rowdblclick: function (grid, record, tr, rowIndex, e, eOpts ) {

                }.bind(this)
            }
        });
        this.grid.on("rowcontextmenu", this.onRowContextmenu.bind(this));

        this.grid.on("afterrender", function (grid) {
            this.updateGridHeaderContextMenu(grid);
        }.bind(this));

        this.grid.on("sortchange", function(grid, sortinfo) {
            this.sortinfo = sortinfo;
        }.bind(this));

        // check for filter updates
        this.grid.on("filterchange", function () {
            this.filterUpdateFunction(this.grid, this.toolbarFilterInfo);
        }.bind(this));

        gridHelper.applyGridEvents(this.grid);

        this.store.load();

        this.layout.removeAll();
        this.layout.add(this.grid);
        this.layout.updateLayout();
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts ) {

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

                    } catch (e2) { console.log(e2); }
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

    batchPrepare: function(columnIndex, onlySelected){
        // no batch for system properties
        if(this.systemColumns.indexOf(this.grid.getColumns()[columnIndex].dataIndex) > -1) {
            return;
        }

        var jobs = [];
        if(onlySelected) {
            var selectedRows = this.grid.getSelectionModel().getSelection();
            for (var i=0; i<selectedRows.length; i++) {
                jobs.push(selectedRows[i].get("id"));
            }
            this.batchOpen(columnIndex,jobs);

        } else {

            var filters = "";
            var condition = "";

            if(this.sqlButton.pressed) {
                condition = this.sqlEditor.getValue();
            } else {
                var filterData = this.store.getFilters().items;
                if(filterData.length > 0) {
                    filters = this.store.getProxy().encodeFilters(filterData);
                }
            }

            var params = {
                filter: this.parent.getSaveData(),
                classId: this.classId,
                objecttype: this.objecttype,
                language: this.gridLanguage
            };


            Ext.Ajax.request({
                url: "/admin/bundle/advanced-object-search/admin/get-batch-jobs",
                method: "POST",
                params: params,
                success: function (columnIndex,response) {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata.success && rdata.jobs) {
                        this.batchOpen(columnIndex, rdata.jobs);
                    }

                }.bind(this,columnIndex)
            });
        }

    },

    exportPrepare: function(){
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
            filter: this.parent.getSaveData(),
            classId: this.classId,
            objecttype: this.objecttype,
            language: this.gridLanguage,
            "ids[]": ids,
            "fields[]": fieldKeys
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

    openColumnConfig: function() {
        var fields = this.getGridConfig().columns;

        var fieldKeys = Object.keys(fields);

        var visibleColumns = [];
        for(var i = 0; i < fieldKeys.length; i++) {
            if(!fields[fieldKeys[i]].hidden) {
                var fc = {
                    key: fieldKeys[i],
                    label: fields[fieldKeys[i]].fieldConfig.label,
                    dataType: fields[fieldKeys[i]].fieldConfig.type,
                    layout: fields[fieldKeys[i]].fieldConfig.layout
                };
                if (fields[fieldKeys[i]].fieldConfig.width) {
                    fc.width = fields[fieldKeys[i]].fieldConfig.width;
                }
                visibleColumns.push(fc);
            }
        }

        var objectId;
        if(this["object"] && this.object["id"]) {
            objectId = this.object.id;
        } else if (this["element"] && this.element["id"]) {
            objectId = this.element.id;
        }

        var columnConfig = {
            language: this.gridLanguage,
            classid: this.classId,
            objectId: objectId,
            selectedGridColumns: visibleColumns
        };
        var dialog = new pimcore.object.helpers.gridConfigDialog(columnConfig, function(data) {
                this.gridLanguage = data.language;
                this.createGrid(true, data.columns);
            }.bind(this), function(data) {
                Ext.Ajax.request({
                    url: "/admin/object-helper/grid-get-column-config",
                    params: {name: this.selectedClass, gridtype: "grid"},
                    success: this.createGrid.bind(this, false)
                });
            }.bind(this)
        )
    },

    getSaveData: function() {
        if(this.grid) {
            var config = this.getGridConfig();
            var columnsConfig = [];

            var keys = Object.keys(config.columns);
            for (var i = 0; i < keys.length; i++) {

                var entry = config.columns[keys[i]].fieldConfig;
                if(entry) {
                    entry.position = config.columns[keys[i]].position;
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