var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: '{C#MODNAME}', files: ['groupeditor.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.GroupListWidget = Y.Base.create('groupListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance, options){
            this.reloadGroupList();
        },
        reloadGroupList: function(){
            this.set('waiting', true);

            this.get('appInstance').groupList(function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('groupList', result.groupList);
                }
                this.renderGroupList();
            }, this);
        },
        renderGroupList: function(){
            var groupList = this.get('groupList');
            if (!groupList){
                return;
            }
            var tp = this.template, lst = "";

            groupList.each(function(group){
                var attrs = group.toJSON();

                lst += tp.replace('row', [
                    attrs
                ]);
            });

            tp.gel('list').innerHTML = tp.replace('list', {
                'rows': lst
            });
        },
        onClick: function(e){

            switch (e.dataClick) {
                case 'add-group':
                    this.showGroupEditorDialog();
                    return true;
            }

            var groupId = e.target.getData('id') | 0;
            if (groupId === 0){
                return;
            }

            switch (e.dataClick) {
                case 'group-edit':
                    this.showGroupEditorDialog(groupId);
                    return true;
            }
        },
        showGroupEditorDialog: function(groupId){
            var dialog = new NS.GroupEditorDialog({groupId: groupId | 0});

            dialog.on('editorSaved', function(){
                this.reloadGroupList();
            }, this);
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'widget,list,row'
            },
            groupList: {
                value: null
            }
        }
    });
};

