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

    NS.GroupEditorWidget = Y.Base.create('groupEditorWidget', SYS.AppWidget, [], {
        buildTData: function(){
            var groupId = this.get('groupId') | 0;
            return {
                'status': groupId > 0 ? 'edit-isedit' : 'edit-isnew'
            };
        },
        onInitAppWidget: function(err, appInstance){
            this.publish('editorCancel');
            this.publish('editorSaved');
            this.publish('renderEditor');

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

            var tp = this.template;
            tp.setValue('title', group.get('title'));

            this.listWidget = new NS.PermissionListWidget({
                boundingBox: tp.gel('permlist'),
                permissionList: group.get('permission')
            });
            this.fire('renderEditor');
        },
        save: function(){
            this.set('waiting', true);

            var tp = this.template,
                d = {
                    id: this.get('groupId'),
                    title: tp.getValue('title'),
                    permission: this.listWidget.toJSON()
                };

            this.get('appInstance').groupSave(d, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.fire('editorSaved');
                }
            }, this);
        },
        cancel: function(){
            this.fire('editorCancel');
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            groupId: {value: 0},
            groupList: {value: null}
        },
        CLICKS: {
            save: 'save',
            cancel: 'cancel'
        }
    });

    NS.GroupEditorDialog = Y.Base.create('groupEditorDialog', SYS.Dialog, [], {
        initializer: function(){
            this.publish('editorSaved');
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

