/*
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['panel.js', 'widgets.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.UserEditorWidget = Y.Base.create('userEditorWidget', SYS.AppWidget, [
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
        onInitAppWidget: function(err, appInstance){
            var userId = this.get('userId');
            this.set('waiting', true);

            appInstance.groupList(function(err, result){
                if (!err){
                    this.set('groupList', result.groupList);
                }
                appInstance.user(userId, function(err, result){
                    this.set('waiting', false);
                    if (!err){
                        this.set('user', result.user);
                    }
                    this.onLoadUser();
                }, this);
            }, this);
        },
        onLoadUser: function(){
            var user = this.get('user'),
                tp = this.template;

            this.set('model', user);

            this.userGroupListWidget = new NS.UserGroupListWidget({
                boundingBox: tp.gel('usergroups'),
                groupList: this.get('groupList'),
                userGroups: user.get('groups')
            });

            this.fire('renderEditor');
        },
        onSubmitFormAction: function(){
            this.set('waiting', true);

            var model = this.get('model');

            this.get('appInstance').userSave(model, function(err, result){
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
            userId: {
                value: 0
            },
            user: {
                value: null
            }
        }
    });

    NS.UserEditorDialog = Y.Base.create('userEditorDialog', SYS.Dialog, [], {
        initializer: function(){
            this.publish('editorSaved', {
                defaultFn: this._defEditorSaved
            });
            Y.after(this._syncUIUserEditorDialog, this, 'syncUI');
        },
        _syncUIUserEditorDialog: function(){
            var tp = this.template;

            var widget = new NS.UserEditorWidget({
                boundingBox: tp.gel('widget'),
                userId: this.get('userId'),
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
            userId: {
                value: 0
            },
            width: {
                value: 600
            }
        }
    });


    NS.UserGroupListWidget = Y.Base.create('userGroupListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance){
            this.renderGroupList();
        },
        renderGroupList: function(){
            var userGroups = this.get('userGroups'),
                groupList = this.get('groupList');

            var tp = this.template, lstSelect = "", lstGroup = "";

            groupList.each(function(group){
                var attrs = group.toJSON(),
                    isFind = false;

                for (var i = 0; i < userGroups.length; i++){
                    if ((attrs.id | 0) === (userGroups[i] | 0)){
                        isFind = true;
                        break;
                    }
                }

                if (isFind){
                    lstGroup += tp.replace('grouprow', attrs);
                } else {
                    lstSelect += tp.replace('option', attrs);
                }
            });

            tp.gel('select').innerHTML = tp.replace('select', {
                'rows': lstSelect
            });

            tp.gel('list').innerHTML = tp.replace('grouplist', {
                'rows': lstGroup
            });
        },
        onClick: function(e){
            var groupId = e.target.getData('id') | 0;
            if (groupId === 0){
                return;
            }

            switch (e.dataClick) {
                case 'group-remove':
                    return true;
            }
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'groupwidget,grouplist,grouprow,select,option'
            },
            userGroups: {
                value: null
            },
            groupList: {
                value: null
            }
        }
    });
};

