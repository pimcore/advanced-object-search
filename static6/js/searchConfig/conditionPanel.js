
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.conditionPanel");
pimcore.plugin.esbackendsearch.searchConfig.conditionPanel = Class.create({

    fieldCondition: null,
    fieldConditionPanel: null,

    getConditionPanel: function(panel, data) {
        var niceName = t("plugin_esbackendsearch_condition");

        if(typeof data == "undefined") {
            data = {};
        }

        var fieldStore = new Ext.data.JsonStore({
            autoDestroy: true,
            autoLoad: true,
            proxy: {
                type: 'ajax',
                url: '/plugin/ESBackendSearch/admin/get-fields',
                reader: {
                    rootProperty: 'data',
                    idProperty: 'fieldName'
                },
                extraParams: {class_id: 3 }
            },
            fields: ['fieldName','fieldLabel', 'fieldType', 'context'],
            listeners: {
                load: function (store) {
                    // if(this.fieldsCombobox) {
                    //     this.fieldsCombobox.setValue(this.data);
                    // }
                }.bind(this)
            }
        });

        var fieldSelection = Ext.create('Ext.form.ComboBox',
            {

                fieldLabel: t("plugin_esbackendsearch_field"),
                name: "condition",
                store: fieldStore,
                // value: data.condition,
                queryMode: 'local',
                width: 400,
                valueField: 'fieldName',
                displayField: 'fieldLabel',
                listeners: {
                    change: function( item, newValue, oldValue, eOpts ) {
                        var record = item.getStore().findRecord('fieldName', newValue);
                        var data = record.data;

                        this.fieldConditionPanel.removeAll();
                        if(pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel[data.fieldType]) {
                            this.fieldCondition = new pimcore.plugin.esbackendsearch.searchConfig.fieldConditionPanel[data.fieldType](data);
                            this.fieldConditionPanel.add(this.fieldCondition.getConditionPanel());
                        } else {
                            console.log("ERROR - no implementation for field condition panel for " + data.fieldType);
                        }

                    }.bind(this)
                }
            }
        );

        this.fieldConditionPanel = Ext.create('Ext.panel.Panel', {
            flex: 1,
            style: "padding-left: 20px"
        });

        var myId = Ext.id();
        return Ext.create('Ext.panel.Panel', {
            id: myId,
            bodyStyle: "padding: 15px 10px; ",
            tbar: this.getTopBar(niceName, myId, panel, data),
            layout: 'hbox',
            scrollable: true,
            border: 1,
            panelInstance: this,
            items: [
                fieldSelection, this.fieldConditionPanel
            ]
        });

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

                var container = parent.conditionsContainerInner;
                var blockElement = Ext.getCmp(blockId);
                var index = this.detectBlockIndex(blockElement, container);

                var newIndex = index-1;
                if(newIndex < 0) {
                    newIndex = 0;
                }

                container.remove(blockElement, false);
                container.insert(newIndex, blockElement);

                parent.recalculateButtonStatus();

                pimcore.layout.refresh();
            }.bind(this, index, parent)
        },{
            iconCls: "pimcore_icon_down",
            handler: function (blockId, parent) {
                var container = parent.conditionsContainerInner;
                var blockElement = Ext.getCmp(blockId);
                var index = this.detectBlockIndex(blockElement, container);

                container.remove(blockElement, false);
                container.insert(index+1, blockElement);

                parent.recalculateButtonStatus();

                pimcore.layout.refresh();
            }.bind(this, index, parent)
        },"-", /*{
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
            },*/
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
            if(container.items.items[s].getId() == blockElement.getId()) {
                index = s;
                break;
            }
        }
        return index;
    },


    getFilterValues: function() {
        if(this.fieldCondition) {
            return this.fieldCondition.getFilterValues();
        }
        return null;
    }

});
