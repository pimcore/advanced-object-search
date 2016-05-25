
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfigPanel");
pimcore.plugin.esbackendsearch.searchConfigPanel = Class.create(pimcore.element.abstract, {

    initialize: function(data, parent) {
        this.parent = parent;
        this.data = data;

        var title = t("plugin_esbackendsearch");
        if(this.data && this.data.name) {
            title = title + ": " + this.data.name;
        }

        this.tab = new Ext.TabPanel({
            activeTab: 0,
            id: this.getTabId(),
            title: title,
            iconCls: "pimcore_icon_esbackendsearch",
            closable: true,
            forceLayout: true,
            items: [this.getConditions(), this.getResults(), this.getSaveAndShare()]
        });

/*
        if (this.data.fieldConfig) {
            this.setColumnConfig(this.data.fieldConfig.availableFields);
            this.setLanguage(this.data.fieldConfig.language);
        }

         // fill data into conditions
        if(this.data.conditions && this.data.conditions.length > 0) {
            for(var i=0; i<this.data.conditions.length; i++) {
                this.addCondition("item" + ucfirst(this.data.conditions[i].type), this.data.conditions[i]);
            }
        }

        this.updateClassDefPanel();
 */
        this.tab.on("activate", this.tabactivated.bind(this));

        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.add(this.tab);
        tabPanel.setActiveItem(this.getTabId());

        this.tab.on("destroy", function () {
            pimcore.globalmanager.remove(this.getTabId());
        }.bind(this));

        pimcore.layout.refresh();
    },

    getTabId: function() {
        if(!this.tabId) {
            if(this.data && this.data.id) {
                this.tabId = "pimcore_search_" + this.data.id;
            } else {
                this.tabId = "pimcore_search_" + uniqid();
            }
        }
        return this.tabId;
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem(this.getTabId());
    },


    tabactivated: function() {
        this.checkForChanges();
        this.setupChangeDetector();
    },

    setColumnConfig: function(columnConfig) {
        this.columnConfig = columnConfig;
    },

    getColumnConfig: function() {
        return this.columnConfig;
    },

    getLanguage: function() {
      return this.language;
    },

    setLanguage: function(language) {
        this.language = language;
    },

    getConditions: function() {
        this.conditionPanelContainer =  Ext.create('Ext.panel.Panel', {});

        var classStore = pimcore.globalmanager.get("object_types_store");

        this.classSelection = Ext.create('Ext.form.ComboBox',
            {
                fieldLabel: t("class"),
                store: classStore,
                valueField: 'id',
                displayField: 'translatedText',
                triggerAction: 'all',
                // value: data.condition,
                queryMode: 'local',
                //style: "margin: 10px",
                width: 300,
                forceSelection: true,
                listeners: {
                    change: function( item, newValue, oldValue, eOpts ) {

                        if(newValue != oldValue) {
                            this.conditionPanelContainer.removeAll();
                            this.conditionPanel = new pimcore.plugin.esbackendsearch.searchConfig.conditionPanel(newValue);
                            this.conditionPanelContainer.add(this.conditionPanel.getConditionPanel());
                        }

                    }.bind(this)
                }
            }
        );


        return new Ext.Panel({
            scrollable: true,
            style: "padding: 10px",
            labelWidth: 400,
            title: t("plugin_esbackendsearch_filter"),
            iconCls: "pimcore_icon_esbackendsearch_filter",
            items: [this.classSelection, this.conditionPanelContainer]
        });

    },

    getResults: function() {
        this.resultPanel = new pimcore.plugin.esbackendsearch.searchConfig.resultPanel(this);
        return this.resultPanel.getLayout();
    },

    getSaveAndShare: function () {

        this.settingsForm = Ext.create('Ext.form.FormPanel', {
            title: t("plugin_esbackendsearch_save_and_share"),
            iconCls: "pimcore_icon_esbackendsearch_saveAndShare",
            bodyStyle: "padding:10px;",
            autoScroll: true,
            border:false,
            items: [{
                xtype: "textfield",
                fieldLabel: t("name"),
                name: "name",
                width: 500
                //value: this.data.name,
            }, {
                name: "description",
                fieldLabel: t("description"),
                xtype: "textarea",
                width: 500,
                height: 100
                //value: this.data.description
            }, {
                xtype: "textfield",
                fieldLabel: t("plugin_esbackendsearch_category"),
                name: "category",
                width: 500
                //value: this.data.name,
            }, {
                xtype: "fieldset",
                title: "plugin_esbackendsearch_share",
                closeable: true,
                items: [
                    {
                        name: "description1",
                        fieldLabel: t("xsdf"),
                        xtype: "textarea",
                        width: 500,
                        height: 100
                        //value: this.data.description
                    }
                ]
            }],
            buttons: [{
                text: t("delete"),
                iconCls: "pimcore_icon_delete",
                handler: this.save.bind(this)
            },{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: this.save.bind(this)
            }]
        });

        return this.settingsForm;
    },



    getSaveData: function(raw) {
        var saveData = {};
        //saveData["settings"] = this.settingsForm.getForm().getFieldValues();
        //saveData["source"] = this.sourceForm.getForm().getFieldValues();

        /*if (this.columnConfig) {

            saveData["fieldConfig"] = {
                "availableFields" : this.columnConfig,
                "language" : this.language
            };
        }*/

        saveData['classId'] = this.classSelection.getValue();

        if(this.conditionPanel) {
            saveData["conditions"] = this.conditionPanel.getSaveData();
        }
        if(this.resultPanel) {
            saveData["gridConfig"] = this.resultPanel.getGridConfig();
        }

        if(this.settingsForm) {
            saveData["settings"] = this.settingsForm.getForm().getFieldValues();
        }
        if(raw) {
            return saveData;
        } else {
            return Ext.encode(saveData);
        }
    },

    save: function () {
        var saveData = this.getSaveData();

        Ext.Ajax.request({
            url: "/plugin/ESBackendSearch/admin/save",
            params: {
                id: this.data ? this.data.id : null,
                data: saveData
            },
            method: "post",
            success: function (response) {
                var rdata = Ext.decode(response.responseText);
                if (rdata && rdata.success) {
                    pimcore.helpers.showNotification(t("success"), t("plugin_esbackendsearch_save_success"), "success");

                    if(!this.data) {
                        this.data = {};
                    }
                    this.data.id = rdata.id;

                    this.resetChanges();
                }
                else {
                    pimcore.helpers.showNotification(t("error"), t("plugin_esbackendsearch_save_error"), "error",t(rdata.message));
                }
            }.bind(this)
        });

    }
});
