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

    SYS.Application.build(COMPONENT, {}, {
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
    }, [], {
        ATTRS: {
            userCurrent: {},
            termsOfUse: {},
            groupList: {}
        },
        REQS: {
            login: {args: ['login']},
            logout: {},
            register: {
                args: ['register'],
                response: function(d){
                    return d;
                }
            },
            termsOfUse: {
                cache: function(){
                    return this.get('termsOfUse');
                },
                response: function(d){
                    return d;
                },
                onResponse: function(termsOfUse){
                    if (!termsOfUse){
                        return;
                    }
                    this.set('termsOfUse', termsOfUse);
                }
            },
            activate: {args: ['activate']},
            userCurrent: {
                cache: function(){
                    return this.get('userCurrent');
                },
                response: function(d){
                    return new NS.UserCurrent(d);
                },
                onResponse: function(user){
                    if (!user){
                        return;
                    }
                    this.set('userCurrent', user);
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
                cache: function(){
                    return this.get('groupList');
                },
                response: function(d){
                    return new NS.Admin.GroupList({items: d.list});
                },
                onResponse: function(groupList){
                    if (!groupList){
                        return;
                    }
                    this.set('groupList', groupList);
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
        },
        URLS: {
            ws: "#app={C#MODNAMEURI}/wspace/ws/",
            user: {
                list: function(){
                    return this.getURL('ws') + 'userlist/UserListWidget/'
                }
            },
            group: {
                list: function(){
                    return this.getURL('ws') + 'grouplist/GroupListWidget/'
                }
            }
        }
    });

};