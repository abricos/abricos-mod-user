var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['panel.js', 'widgets.js']},
        {name: '{C#MODNAME}', files: ['permlist.js', 'lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.GroupEditorWidget = Y.Base.create('groupEditorWidget', SYS.AppWidget, [
        SYS.Form,
        SYS.FormAction
    ], {
        initializer: function(){
            this.publish('editorCancel', {
                defaultFn: this._defEditorCancel
            });
            this.publish('editorSaved', {
                defaultFn: this._defEditorSaved
            });
            this.publish('renderEditor', {
                defaultFn: this._defRenderEditor
            });
        },
        _defRenderEditor: function(){
        },
        buildTData: function(){
            var groupId = this.get('groupId') | 0;
            return {
                'status': groupId > 0 ? 'edit-isedit' : 'edit-isnew'
            };
        },
        onInitAppWidget: function(err, appInstance, options){
            if (this.get('groupList')){
                this.onLoadGroupList();
            } else {
                this.set('waiting', true);
                this.get('appInstance').groupList(function(err, result){
                    this.set('waiting', false);
                    if (!err){
                        this.set('groupList', result.groupList);
                    }
                    this.onLoadGroupList();
                }, this);
            }
        },
        onLoadGroupList: function(){
            var groupId = this.get('groupId'),
                groupList = this.get('groupList'),
                group;

            if (groupId === 0){
                group = new NS.Admin.Group();
            } else {
                group = groupList.getById(groupId);
            }
            this.set('model', group);

            this.listWidget = new NS.PermissionListWidget({
                boundingBox: this.template.gel('permlist'),
                permissionList: group.get('permission')
            });
            this.fire('renderEditor');
        },
        onSubmitFormAction: function(){
            this.set('waiting', true);

            this.listWidget.fillPermissionList();

            var model = this.get('model');

            this.get('appInstance').groupSave(model, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.fire('editorSaved');
                }
            }, this);
        },
        onClick: function(e){
            switch (e.dataClick) {
                case 'cancel':
                    this.fire('editorCancel');
                    return true;
            }
        },
        _defEditorSaved: function(){
        },
        _defEditorCancel: function(){
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'widget'
            },
            groupId: {
                value: 0
            },
            groupList: {
                value: null
            }
        }
    });

    NS.GroupEditorDialog = Y.Base.create('groupEditorDialog', SYS.Dialog, [], {
        initializer: function(){
            this.publish('editorSaved', {
                defaultFn: this._defEditorSaved
            });
            Y.after(this._syncUIGroupEditorDialog, this, 'syncUI');
        },
        _syncUIGroupEditorDialog: function(){
            var tp = this.template;

            var widget = new NS.GroupEditorWidget({
                boundingBox: tp.gel('widget'),
                groupId: this.get('groupId'),
                groupList: this.get('groupList'),
                render: false
            });
            var instance = this;
            widget.on('editorCancel', function(){
                instance.hide();
            });
            widget.on('editorSaved', function(){
                instance.fire('editorSaved');
                instance.hide();
            });
            widget.on('renderEditor', function(){
                instance.centered();
            });
            widget.render();
        },
        _defEditorSaved: function(){
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'dialog'
            },
            groupId: {
                value: 0
            },
            groupList: {
                value: null
            },
            width: {
                value: 600
            }
        }
    });

};

