
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfigPanel");
pimcore.plugin.esbackendsearch.searchConfigPanel = Class.create(pimcore.element.abstract, {
    initialize: function(data, parent) {
        this.parent = parent;
        this.data = data;


        this.tab = new Ext.TabPanel({
            activeTab: 0,
            // title: "hugo",
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
            // drop down menu for adding new conditions
            var addMenu = [];

            //var itemTypes = Object.keys(pimcore.plugin.savedsearch.conditions);

            // get all keys that start with "item" and add them to the menu
            /*for(var i=0; i<itemTypes.length; i++) {
                if(itemTypes[i].indexOf("item") == 0) {
                    addMenu.push({
                        iconCls: "pimcore_icon_add",

                        handler: this.addCondition.bind(this, itemTypes[i]),
                        // true => returns pretty name
                        text: pimcore.plugin.savedsearch.conditions[itemTypes[i]](null, null,true)
                    });
                }
            }*/

            addMenu.push({
                iconCls: "pimcore_icon_add",

                handler: function(type, data) {
                    var itemClass = new pimcore.plugin.esbackendsearch.searchConfig.conditionPanel();
                    var item = itemClass.getConditionPanel(this, data);
                    this.conditionsContainerInner.add(item);
                    item.updateLayout();
                    this.conditionsContainerInner.updateLayout();

                    this.currentIndex++;
                }.bind(this),
                // true => returns pretty name
                text: "condition"
            });


            this.conditionsContainerInner = new Ext.Panel({
                region: "center",
                autoScroll: true,
                forceLayout: true,
                viewConfig: {
                    forceFit: true
                },
                tbar: [{
                    iconCls: "pimcore_icon_add",
                    menu: addMenu
                }],
                border: false,
                items: []
            });

            this.conditionsContainer = new Ext.Panel({
                title: t("conditions"),
                layout: "border",
                items: [this.conditionsContainerInner]

            });

            return this.conditionsContainer;
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

        addCondition: function (type, data) {
            var item = pimcore.plugin.savedsearch.conditions[type](this, data);

            // add logic for brackets
            /*item.on("afterrender", function (el) {
                el.getEl().applyStyles({position: "relative", "min-height": "40px"});
                var leftBracket = el.getEl().insertHtml("beforeEnd", '<div class="pimcore_targeting_bracket pimcore_targeting_bracket_left">(</div>', true);
                var rightBracket = el.getEl().insertHtml("beforeEnd", '<div class="pimcore_targeting_bracket pimcore_targeting_bracket_right">)</div>', true);

                if(data["bracketLeft"]){
                    leftBracket.addCls("pimcore_targeting_bracket_active");
                }
                if(data["bracketRight"]){
                    rightBracket.addCls("pimcore_targeting_bracket_active");
                }

                leftBracket.on("click", function (ev, el) {
                    Ext.get(el).toggleCls("pimcore_targeting_bracket_active");
                });

                rightBracket.on("click", function (ev, el) {
                    Ext.get(el).toggleCls("pimcore_targeting_bracket_active");
                });
            });*/

            this.conditionsContainerInner.add(item);
            item.updateLayout();
            this.conditionsContainerInner.updateLayout();

            this.currentIndex++;

            this.recalculateButtonStatus();
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

            var conditionsData = [];
            var condition, tb, operator;
            var conditions = this.conditionsContainerInner.items.getRange();
            for (var i=0; i<conditions.length; i++) {
                var condition = conditions[i].panelInstance.getFilterValues();
                /*
                condition = conditions[i].getForm().getFieldValues();

                // get the operator (AND, OR, AND_NOT)
                var tb = conditions[i].getDockedItems()[0];
                if (tb.getComponent("toggle_or").pressed) {
                    operator = "or";
                } else if (tb.getComponent("toggle_and_not").pressed) {
                    operator = "and_not";
                } else {
                    operator = "and";
                }
                condition["operator"] = operator;

                // get the brackets
                var foo = conditions[i];
                condition["bracketLeft"] = Ext.get(conditions[i].getEl().query(".pimcore_targeting_bracket_left")[0]).hasCls("pimcore_targeting_bracket_active");
                condition["bracketRight"] = Ext.get(conditions[i].getEl().query(".pimcore_targeting_bracket_right")[0]).hasCls("pimcore_targeting_bracket_active");
*/
                conditionsData.push(condition);
            }
            saveData["conditions"] = conditionsData;
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

        },

        recalculateButtonStatus: function () {
            var conditions = this.conditionsContainerInner.items.getRange();
            var tb;
            for (var i=0; i<conditions.length; i++) {
                var tb = conditions[i].getDockedItems()[0];
                if(i==0) {
                    // tb.getComponent("toggle_and").hide();
                    // tb.getComponent("toggle_or").hide();
                    // tb.getComponent("toggle_and_not").hide();
                } else {
                    // tb.getComponent("toggle_and").show();
                    // tb.getComponent("toggle_or").show();
                }
            }
        }
    }

);


/* CONDITION TYPES */

pimcore.registerNS("pimcore.plugin.savedsearch.conditions");

pimcore.plugin.savedsearch.conditions = {
    itemCondition: function (panel, data, getName) {
        var niceName = "Condition";

        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var textField = new Ext.form.TextField(
            {

                fieldLabel: "Condition",
                name: "condition",
                value: data.condition,
                width: 400
                // cls: "pimcore_droptarget_input"
            }
        );


        textField.on("render", function (el) {

            // add drop zone
            new Ext.dd.DropZone(el.getEl(), {
                reference: this,
                dropAllowed: true,
                ddGroup: "columnconfigelement",
                getTargetFromEvent: function(e) {
                    return this.getEl();
                },

                onNodeOver : function(target, dd, e, data) {
                    return Ext.dd.DropZone.prototype.dropAllowed;
                },

                onNodeDrop : function(target, dd, e, data) {
                    this.setValue(data.node.attributes.key + this.getValue());
                    return true;
                }.bind(this)
            });

        });



        var item =  new Ext.form.FormPanel({
            id: myId,
            forceLayout: true,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px 30px 10px 30px; min-height:40px;",
            tbar: this.getTopBar(niceName, myId, panel, data),



            items: [textField,
            {
                xtype: "hidden",
                name: "type",
                value: "condition"
            }]
        });

        return item;
    },


    getTopBar: function (name, index, parent, data) {

        var toggleGroup = "g_" + index + parent.data.id;
        if(!data["operator"]) {
            data.operator = "and";
        }

        return [{
            xtype: "tbtext",
            text: "<b>" + name + "</b>"
        },"-",{
            iconCls: "pimcore_icon_up",
            handler: function (blockId, parent) {

                var container = parent.conditionsContainer;
                var containerInner = parent.conditionsContainerInner;
                var blockElement = Ext.getCmp(blockId);
                var index = this.detectBlockIndex(blockElement, container);
                var tmpContainer = pimcore.viewport;

                var newIndex = index-1;
                if(newIndex < 0) {
                    newIndex = 0;
                }

                // move this node temorary to an other so ext recognizes a change
                containerInner.remove(blockElement, false);
                tmpContainer.add(blockElement);
                containerInner.updateLayout();
                tmpContainer.updateLayout();

                // move the element to the right position
                tmpContainer.remove(blockElement,false);
                containerInner.insert(newIndex, blockElement);
                containerInner.updateLayout();
                tmpContainer.updateLayout();

                parent.recalculateButtonStatus();

                pimcore.layout.refresh();
            }.bind(this, index, parent)
        },{
            iconCls: "pimcore_icon_down",
            handler: function (blockId, parent) {

                var container = parent.conditionsContainer;
                var containerInner = parent.conditionsContainerInner;
                var blockElement = Ext.getCmp(blockId);
                var index = this.detectBlockIndex(blockElement, container);
                var tmpContainer = pimcore.viewport;

                // move this node temorary to an other so ext recognizes a change
                containerInner.remove(blockElement, false);
                tmpContainer.add(blockElement);
                containerInner.updateLayout();
                tmpContainer.updateLayout();

                // move the element to the right position
                tmpContainer.remove(blockElement,false);
                containerInner.insert(index+1, blockElement);
                container.updateLayout();
                tmpContainer.updateLayout();

                parent.recalculateButtonStatus();

                pimcore.layout.refresh();
            }.bind(this, index, parent)
        },"-", {
            text: t("AND"),
            toggleGroup: toggleGroup,
            enableToggle: true,
            itemId: "toggle_and",
            pressed: (data.operator == "and") ? true : false
        },{
            text: t("OR"),
            toggleGroup: toggleGroup,
            enableToggle: true,
            itemId: "toggle_or",
            pressed: (data.operator == "or") ? true : false
        },

            {
                text: t("AND_NOT"),
                hidden: true,
                toggleGroup: toggleGroup,
                enableToggle: true,
                itemId: "toggle_and_not",
                pressed: (data.operator == "and_not") ? true : false
            },
            "->",{
                iconCls: "pimcore_icon_delete",
                handler: function (index, parent) {
                    parent.conditionsContainerInner.remove(Ext.getCmp(index));
                    parent.recalculateButtonStatus();
                }.bind(window, index, parent)
            }];
    },

    detectBlockIndex: function (blockElement, container) {
        // detect index
        var index;

        for(var s=0; s < container.items.items.length; s++) {
            if(container.items.items[s].key == blockElement.key) {
                index = s;
                break;
            }
        }
        return index;
    }




};