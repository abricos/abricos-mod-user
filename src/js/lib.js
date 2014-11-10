/*!
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['base'],
    mod: [
        {name: 'sys', files: ['application.js', 'widget.js', 'form.js']},
        {name: 'widget', files: ['notice.js']},
        {name: '{C#MODNAME}', files: ['model.js']}
    ]
};
Component.entryPoint = function(NS){

    NS.roles = new Brick.AppRoles('{C#MODNAME}', {
        isAdmin: 50,
        isRegistration: 10
    });

    var Y = Brick.YUI,

        COMPONENT = this,

        WAITING = 'waiting',
        BOUNDING_BOX = 'boundingBox',

        SYS = Brick.mod.sys;

    NS.URL = {
        ws: "#app={C#MODNAMEURI}/wspace/ws/",
        user: {
            list: function(){
                return NS.URL.ws + 'userlist/UserListWidget/'
            }
        },
        group: {
            list: function(){
                return NS.URL.ws + 'grouplist/GroupListWidget/'
            }
        }
    };
    NS.AppWidget = Y.Base.create('appWidget', Y.Widget, [
        SYS.Language,
        SYS.Template,
        SYS.WidgetClick,
        SYS.WidgetWaiting
    ], {
        initializer: function(){
            this._appWidgetArguments = Y.Array(arguments);

            Y.after(this._syncUIAppWidget, this, 'syncUI');
        },
        _syncUIAppWidget: function(){
            if (!this.get('useExistingWidget')){
                var args = this._appWidgetArguments,
                    tData = {};

                if (Y.Lang.isFunction(this.buildTData)){
                    tData = this.buildTData.apply(this, args);
                }

                var bBox = this.get(BOUNDING_BOX),
                    defTName = this.template.cfg.defTName;

                bBox.setHTML(this.template.replace(defTName, tData));
            }
            this.set(WAITING, true);

            var instance = this;
            NS.initApp({
                initCallback: function(err, appInstance){
                    instance._initAppWidget(err, appInstance);
                }
            });
        },
        _initAppWidget: function(err, appInstance){
            this.set('appInstance', appInstance);
            this.set(WAITING, false);
            var args = this._appWidgetArguments
            this.onInitAppWidget.apply(this, [err, appInstance, {
                arguments: args
            }]);
        },
        onInitAppWidget: function(){
        }
    }, {
        ATTRS: {
            render: {
                value: true
            },
            appInstance: {
                values: null
            },
            useExistingWidget: {
                value: false
            }
        }
    });


    var AppBase = function(){
    };
    AppBase.ATTRS = {
        initCallback: {
            value: function(){
            }
        }
    };
    AppBase.prototype = {
        initializer: function(){
            this._cacheGroupList = null;
            this._cacheUserCurrent = null;
            this._cacheUserOptionList = {};

            this.get('initCallback')(null, this);
        },
        onAJAXError: function(err){
            Brick.mod.widget.notice.show(err.msg);
        },
        _treatAJAXResult: function(data){
            data = data || {};
            var ret = {};

            if (data.userCurrent){
                var userCurrent = new NS.UserCurrent(data.userCurrent);
                this._cacheUserCurrent = userCurrent;
                ret.userCurrent = userCurrent;
            }
            if (data.userOptionList){
                var userOptionList = new NS.UserOptionList(data.userOptionList);
                this._cacheUserOptionList[data.module] = userOptionList;
                ret.userOptionList = userOptionList;
            }
            if (data.termsofuse){
                ret.termsofuse = data.termsofuse;
            }
            if (data.register){
                ret.register = data.register;
            }
            if (data.users){
                var d = data.users;
                var userList = new NS.Admin.UserList({
                    listConfig: new NS.UserListConfig(d.config),
                    items: d.list
                });
                ret.userList = userList;
            }
            if (data.groups){
                var d = data.groups;
                var groupList = new NS.Admin.GroupList({
                    items: d.list
                });
                this._cacheGroupList = groupList;
                ret.groupList = groupList;
            }

            return ret;
        },
        _defaultAJAXCallback: function(err, res, details){
            var tRes = this._treatAJAXResult(res.data);

            details.callback.apply(details.context, [err, tRes]);
        },

        userCurrent: function(callback, context){
            if (this._cacheUserCurrent){
                return callback.apply(context, [null, {
                    userCurrent: this._cacheUserCurrent
                }]);
            }
            this.ajax({'do': 'userCurrent'}, this._defaultAJAXCallback, {
                arguments: {callback: callback, context: context}
            });
        },

        userOptionList: function(modName, callback, context){
            if (this._cacheUserOptionList[modName]){
                return callback.apply(context, [null, {
                    userOptionList: this._cacheUserOptionList[modName]
                }]);
            }
            this.ajax({
                'do': 'userOptionList',
                'module': modName
            }, this._defaultAJAXCallback, {
                arguments: {callback: callback, context: context}
            });
        },

        login: function(login, callback, context){
            this.ajax({
                'do': 'login',
                'savedata': login.toJSON()
            }, this._defaultAJAXCallback, {
                arguments: {callback: callback, context: context}
            });
        },
        register: function(regData, callback, context){
            this.ajax({
                'do': 'register',
                'savedata': regData.toJSON()
            }, this._defaultAJAXCallback, {
                arguments: {callback: callback, context: context}
            });
        },
        activate: function(act, callback, context){
            this.ajax({
                'do': 'activate',
                'savedata': act.toJSON()
            }, this._defaultAJAXCallback, {
                arguments: {callback: callback, context: context}
            });
        },
        termsOfUse: function(callback, context){
            var instance = this;
            instance.ajax({
                'do': 'termsofuse'
            }, this._defaultAJAXCallback, {
                arguments: {callback: callback, context: context}
            });
        },
        logout: function(callback, context){
            var instance = this;
            instance.ajax({
                'do': 'logout'
            }, this._defaultAJAXCallback, {
                arguments: {callback: callback, context: context}
            });
        },
        userList: function(listConfig, callback, context){
            this.ajax({
                'do': 'userlist',
                'userlistconfig': listConfig.toJSON()
            }, this._defaultAJAXCallback, {
                arguments: {callback: callback, context: context}
            });
        },
        groupList: function(callback, context){
            if (this._cacheGroupList){
                return callback.apply(context, [null, {
                    groupList: this._cacheGroupList
                }]);
            }
            this.ajax({
                'do': 'grouplist'
            }, this._defaultAJAXCallback, {
                arguments: {callback: callback, context: context}
            });
        },
        groupSave: function(model, callback, context){
            this.ajax({
                'do': 'groupsave',
                'groupdata': model.toJSON()
            }, this._defaultAJAXCallback, {
                arguments: {callback: callback, context: context}
            });
        }
    };
    NS.AppBase = AppBase;

    NS.App = Y.Base.create('userApp', Y.Base, [
        SYS.AJAX,
        SYS.Language,
        NS.AppBase
    ], {
        initializer: function(){
            NS.appInstance = this;
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            initCallback: {
                value: null
            },
            moduleName: {
                value: '{C#MODNAME}'
            }
        }
    });

    NS.appInstance = null;
    NS.initApp = function(options){
        if (Y.Lang.isFunction(options)){
            options = {
                initCallback: options
            }
        }
        options = Y.merge({
            initCallback: function(){
            }
        }, options || {});

        if (NS.appInstance){
            return options.initCallback(null, NS.appInstance);
        }
        new NS.App(options);
    };

};