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

    NS.UserEditorWidget = Y.Base.create('userEditorWidget', SYS.AppWidget, [], {
        initializer: function(){
            this.publish('editorCancel');
            this.publish('editorSaved');
            this.publish('renderEditor');
        },
        onInitAppWidget: function(err, appInstance){
            var userId = this.get('userId') | 0;
            this.set('waiting', true);

            appInstance.groupList(function(err, result){
                if (!err){
                    this.set('groupList', result.groupList);
                }
                if (userId === 0){
                    var user = new NS.Admin.User({
                        groups: [2]
                    });
                    this.set('user', user);
                    this.onLoadUser();
                } else {
                    appInstance.user(userId, function(err, result){
                        if (!err){
                            this.set('user', result.user);
                            this.onLoadUser();
                        }
                    }, this);
                }
            }, this);
        },
        onLoadUser: function(){
            this.set('waiting', false);

            var user = this.get('user'),
                tp = this.template;

            tp.setValue({
                username: user.get('username'),
                email: user.get('email')
            });

            this.userGroupListWidget = new NS.UserGroupListWidget({
                boundingBox: tp.gel('usergroups'),
                groupList: this.get('groupList'),
                userGroups: user.get('groups')
            });

            if (user.get('id') > 0){
                if (!user.get('emailconfirm')){
                    tp.show('activate');
                }
            } else {
                tp.one('username').set('disabled', '');
                this._showPasswordForm();
                tp.one('username').focus();
            }

            this.fire('renderEditor');
        },
        _showPasswordForm: function(){
            var tp = this.template;
            tp.toggleView(true, 'password', 'passwordchange');
            tp.one('password').focus();
        },
        activateCustom: function(){
            this.set('waiting', true);
            this.get('appInstance').userActivateCustom(this.get('userId'), function(err, result){
                this.set('waiting', false);

                if (!err){
                    this.set('isUserChange', true);
                    Y.one(this.template.gel('activate')).addClass('hide');
                }
                this.onLoadUser();
            }, this);
        },
        activateEMailSendAgain: function(){
            this.set('waiting', true);
            this.get('appInstance').userActivateSendEMail(this.get('userId'), function(err, result){
                this.set('waiting', false);
            }, this);
        },
        save: function(){
            this.set('waiting', true);

            var tp = this.template,
                userGroups = this.userGroupListWidget.get('userGroups'),
                user = this.get('user');

            user.set('groups', userGroups);
            user.set('username', tp.getValue('username'));
            user.set('email', tp.getValue('email'));
            user.set('password', tp.getValue('password'));

            this.get('appInstance').userSave(user, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.fire('editorSaved');
                }
            }, this);
        },
        cancel: function(){
            this.fire('editorCancel', this.get('isUserChange'));
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget'},
            userId: {value: 0},
            user: {value: null},
            isUserChange: {value: false}
        },
        CLICKS: {
            passwordChange: '_showPasswordForm',
            activateCustom: 'activateCustom',
            activateSendemail: 'activateSendemail',
            save: 'save',
            cancel: 'cancel'
        }
    });

    NS.UserEditorDialog = Y.Base.create('userEditorDialog', SYS.Dialog, [], {
        initializer: function(){
            this.publish('editorSaved');
            this.publish('editorCancel');
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
            widget.on('editorCancel', function(evt, isUserChange){
                instance.fire('editorCancel', isUserChange);
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
            component: {value: COMPONENT},
            templateBlockName: {value: 'dialog'},
            userId: {value: 0},
            width: {value: 600}
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
                var attrs = group.toJSON();

                if (this.userGroupExists(attrs.id)){
                    lstGroup += tp.replace('grouprow', attrs);
                } else {
                    lstSelect += tp.replace('option', attrs);
                }
            }, this);

            tp.gel('select').innerHTML = tp.replace('select', {
                'rows': lstSelect
            });

            tp.gel('list').innerHTML = tp.replace('grouplist', {
                'rows': lstGroup
            });
        },
        userGroupExists: function(groupId){
            var userGroups = this.get('userGroups');

            for (var i = 0; i < userGroups.length; i++){
                if ((groupId | 0) === (userGroups[i] | 0)){
                    return true;
                }
            }
            return false;
        },
        addUserGroup: function(groupId){
            if (this.userGroupExists(groupId)){
                return;
            }
            var userGroups = this.get('userGroups');
            userGroups[userGroups.length] = groupId;
            this.set('userGroups', userGroups);
            this.renderGroupList();
        },
        removeUserGroup: function(groupId){
            var userGroups = this.get('userGroups'),
                arr = [];

            for (var i = 0; i < userGroups.length; i++){
                if ((groupId | 0) !== (userGroups[i] | 0)){
                    arr[arr.length] = userGroups[i];
                }
            }
            this.set('userGroups', arr);
            this.renderGroupList();
        },
        _addUserGroupFromSelect: function(){
            var groupId = this.template.gel('select.id').value;
            this.addUserGroup(groupId);
        },
        onClick: function(e){
            switch (e.dataClick) {
                case 'group-add':
                    this._addUserGroupFromSelect();
                    return true;
                case 'group-remove':
                    this.removeUserGroup(e.target.getData('id'));
                    return true;
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {
                value: 'groupwidget,grouplist,grouprow,select,option'
            },
            userGroups: {value: null},
            groupList: {value: null}
        }
    });
};

