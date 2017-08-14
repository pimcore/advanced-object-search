
pimcore.registerNS("pimcore.plugin.esbackendsearch.searchConfig.conditionAbstractPanel");
pimcore.plugin.esbackendsearch.searchConfig.conditionAbstractPanel = Class.create({

    fieldCondition: null,
    fieldConditionPanel: null,
    mainPanelLayout: "hbox",
    classId: null,

    initialize: function(classId, mainPanelLayout) {
        this.classId = classId;
        if(mainPanelLayout) {
            this.mainPanelLayout = mainPanelLayout;
        }

    },

    getTopBar: function (name, index, parent) {
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

                pimcore.layout.refresh();
            }.bind(this, index, parent)
        },"->",{
                iconCls: "pimcore_icon_delete",
                handler: function (index, parent) {
                    parent.conditionsContainerInner.remove(Ext.getCmp(index));
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
    }
});
