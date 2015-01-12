/*!
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['application.js', 'form.js']},
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

    SYS.Application.build(COMPONENT, {
        auth: {args: ['authData']},
        logout: {},
        register: {
            args: ['register'],
            response: function(d){
                return d;
            }
        },
        termsOfUse: {
            cache: 'termsOfUse',
            response: function(d){
                return d;
            }
        },
        activate: {args: ['activate']},
        userCurrent: {
            cache: 'userCurrent',
            response: function(d){
                return new NS.UserCurrent(d);
            }
        },
        user: {
            args: ['userid'],
            response: function(d){
                return new NS.Admin.User(d);
            }
        },
        userSave: {
            args: ['userData'],
            response: function(d){
                return new NS.Admin.User(d);
            }
        },
        userActivateCustom: {
            args: ['userid']
        },
        userActivateSendEMail: {
            args: ['userid']
        },
        userList: {
            args: ['userListConfig'],
            response: function(d){
                return new NS.Admin.UserList({
                    listConfig: new NS.UserListConfig(d.config),
                    items: d.list
                })
            }
        },
        groupList: {
            cache: 'groupList',
            response: function(d){
                return new NS.Admin.GroupList({items: d.list});
            }
        },
        userOptionList: {
            args: ['module'],
            // cache: 'userOptionList',
            response: function(d){
                return new NS.UserOptionList({
                    items: d.list
                });
            }
        },
        passwordRecovery: {
            args: ['passwordRecovery']
        }
    }, {
        initializer: function(){
            this.initCallbackFire();
        },
        userOptionSave: function(modName, option, callback, context){
            var sd;
            if (Y.Lang.isArray(option)){
                sd = [];
                for (var i = 0; i < option.length; i++){
                    sd[sd.length] = option[i].toJSON();
                }
            } else {
                sd = option.toJSON();
            }

            this.ajaxa({
                'do': 'userOptionSave',
                'module': modName,
                'savedata': sd
            }, callback, context);
        },
        groupSave: function(model, callback, context){
            this.ajaxa({
                'do': 'groupsave',
                'groupdata': model.toJSON()
            }, callback, context);
        }
    });

};