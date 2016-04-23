
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfigPanel");
pimcore.plugin.esbackendsearch.searchConfigPanel = Class.create(pimcore.element.abstract, {
    initialize: function(data, parent, skip) {
        if(skip) {
            return;
        }
        this.parent = parent;
        this.data = data;


        this.tab = new Ext.TabPanel({
            activeTab: 0,
            title: "hugo",
            closable: true,
            deferredRender: false,
            forceLayout: true,
            // Note, this must be the same id as used in panel.js
            id: "pimcore_plugin_es_backendsearch_panel",
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: this.save.bind(this)
            }],
            items: [/*this.getSettings(), this.getSource() ,*/ this.getConditions()]
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

        this.tab.on("activate", this.tabactivated.bind(this));
*/
        this.parent.editPanel.add(this.tab);
        this.parent.editPanel.setActiveTab(this.tab);
        this.parent.editPanel.updateLayout();
/*
        this.addPreviewPanel();*/
    },

        tabactivated: function() {
            this.checkForChanges();
            // this.setupChangeDetector();
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

        getPreview: function() {
            this.previewTab = new pimcore.plugin.savedsearch.previewTab(this, this.data.name, false).getLayout();
            return this.previewTab;
        },

        getConfigurationName: function() {
            return this.data.name;
        },

        addPreviewPanel: function () {
            if (this.data.source.selectedClass) {
                this.tab.add(this.getPreview());
            }
        },

        getClassTree: function(url, id) {
            var classTreeHelper = new pimcore.object.helpers.classTree(true);
            var tree = classTreeHelper.getClassTree(url, id);
            return tree;
        },

        getSettings: function () {

            this.settingsForm = new Ext.form.FormPanel({
                title: t("plugin_savedsearch_tab_general"),
                bodyStyle: "padding:10px;",
                autoScroll: true,
                border:false,
                items: [{
                    xtype: "textfield",
                    fieldLabel: t("name"),
                    name: "name",
                    width: 350,
                    value: this.data.name,
                    disabled: true
                }, {
                    name: "description",
                    fieldLabel: t("description"),
                    xtype: "textarea",
                    width: 500,
                    height: 100,
                    value: this.data.description
                }]
            });

            return this.settingsForm;
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
                    style: "margin: 10px",
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
                title: t("plugin_esbackendsearch_filter"),
                items: [this.classSelection, this.conditionPanelContainer]
            });

        },

        getSource: function () {

            var classStore = pimcore.globalmanager.get("object_types_store");

            this.sourceForm = new Ext.form.FormPanel({
                bodyStyle: "padding:10px;",
                autoScroll: true,
                border:false,
                title: t("plugin_savedsearch_tab_source"),
                items: [new Ext.form.ComboBox({
                    name: "selectedClass",
                    listWidth: 'auto',
                    width: 330,
                    store: classStore,
                    value: this.data.source.selectedClass,
                    valueField: 'id',
                    fieldLabel: t("please_select_a_type"),
                    displayField: 'translatedText',
                    triggerAction: 'all',
                    listeners: {
                        "select": this.changeClassSelect.bind(this)
                    }
                }),
                {
                    xtype: "textfield",
                    fieldLabel: t("plugin_savedsearch_alias"),
                    name: "alias",
                    width: 330,
                    value: this.data.source.alias
                }
                ]
            });


            this.objectTypeFieldSet = new Ext.form.FieldSet(
                {
                    title: t("plugin_savedsearch_fieldset_objecttypes"),
                    items: [
                        {
                            xtype: 'checkbox',
                            fieldLabel: t('plugin_savedsearch_show_type_object'),
                            name: 'showTypeObject',
                            checked: this.data.source.showTypeObject
                        },
                        {
                            xtype: 'checkbox',
                            fieldLabel: t('plugin_savedsearch_show_type_variant'),
                            name: 'showTypeVariant',
                            checked: this.data.source.showTypeVariant
                        }]
                }
            );

            this.sourceForm.add(this.objectTypeFieldSet);
            this.updateBrickConfig(this.data.source.selectedClass);

            return this.sourceForm;
        },


        updateBrickConfig: function(selectedClass) {
            if (this.bricksFieldSet) {
                this.sourceForm.remove(this.bricksFieldSet);
                delete this.bricksFieldSet;
            }

            Ext.Ajax.request({
                url: "/plugin/SavedSearch/admin/get-brick-config",
                params: {
                    id: selectedClass
//                     ,
//                     data: Ext.encode(saveData)
                },
                method: "post",
                success: function (response) {

                    var data = Ext.decode(response.responseText);

                    var allowedBrickTypes = data.allowedBrickTypes;

                    if (allowedBrickTypes.length > 0) {
                        this.bricksFieldSet = new Ext.form.FieldSet(
                            {
                                title: t("plugin_savedsearch_fieldset_bricks")
                            }
                        );

                        for (var i=0; i < allowedBrickTypes.length; i++) {
                            var brickType = allowedBrickTypes[i];
                            var isChecked = this.data.source["~" + brickType];

                            var checkBox = new Ext.form.Checkbox(
                                {
                                    fieldLabel: ts(brickType),
                                    name: "~" + brickType,
                                    checked: isChecked
                                }
                            );

                            this.bricksFieldSet.add(checkBox);
                        }

                        this.sourceForm.add(this.bricksFieldSet);
                        this.sourceForm.updateLayout();
                    }

                }.bind(this)
            });
        },

        updateClassDefPanel: function() {
            // oly add the class panel if we have a class

            if (!this.classOverviewContainerPanel) {
                this.classOverviewContainerPanel = new Ext.Panel({
                    region: "east",
                    width: 300,
                    autoScroll: true,
                    items: []
                });
                this.conditionsContainer.add(this.classOverviewContainerPanel);

            }


            if (this.data.source.selectedClass) {

                var classTreeInner = this.getClassTree("/admin/class/get-class-definition-for-column-config",
                    this.data.source.selectedClass);
                this.classTreeInner = classTreeInner;

                this.classOverviewContainerPanel.removeAll();
                this.classOverviewContainerPanel.add(classTreeInner);
                classTreeInner.updateLayout();

                this.classOverviewContainerPanel.updateLayout();
            }
        },

        changeClassSelect: function (field, newValue, oldValue) {
            if (newValue == oldValue) {
                return;
            }

            this.setColumnConfig(null);

            var selectedClass = newValue.data.id;
            this.setClass(selectedClass);


            var currentPreview = this.previewTab;
            if (currentPreview) {
                this.tab.remove(currentPreview);
            }

            // first update the brick config, otherwise the preview panel
            // will try to use an invalid brick config for the new class
            this.updateBrickConfig(selectedClass);

            this.addPreviewPanel();

            this.updateClassDefPanel();

            pimcore.layout.refresh();
        },

        setClass: function (classId) {
            this.data.source.selectedClass = classId;

        },


        getClass: function() {
            return this.data.source.selectedClass;
        },

        getSaveData: function() {
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
            return Ext.encode(saveData);
        },

        save: function () {
            var saveData = this.getSaveData();

            Ext.Ajax.request({
                url: "/plugin/ESBackendSearch/admin/filter",
                params: {
                    id: this.data.id,
                    data: saveData,
                    language: this.language
                },
                method: "post",
                success: function (response) {
                    //var rdata = Ext.decode(response.responseText);
                    /*if (rdata && rdata.success) {
                        pimcore.helpers.showNotification(t("success"), t("plugin_savedsearch_save_success"), "success");
                        this.resetChanges();
                    }
                    else {
                        pimcore.helpers.showNotification(t("error"), t("plugin_savedsearch_save_error"), "error",t(rdata.message));
                    }*/
                    console.log(response.responseText);
                }.bind(this)
            });

        }
    }

);
