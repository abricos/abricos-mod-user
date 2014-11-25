/*!
 * Module for Abricos Platform (http://abricos.org)
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['model', 'model-list']
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI;

    NS.User = Y.Base.create('user', Y.Model, [], {}, {
        ATTRS: {
            username: {value: ''},
            firstname: {value: ''},
            lastname: {value: ''},
            avatar: {value: ''}
        }
    });

    NS.UserCurrent = Y.Base.create('userCurrent', NS.User, [], {
        isRoleEnable: function(mName, action){
            var perms = this.get('permission');
            if (!perms[mName]){
                return false;
            }

            return perms[mName][action] | 0 === 1;
        }
    }, {
        ATTRS: {
            permission: {value: []}
        }
    });

    NS.Login = Y.Base.create('login', Y.Model, [], {}, {
        ATTRS: {
            username: {value: ''},
            password: {value: ''},
            autologin: {value: ''}
        }
    });

    NS.RegisterData = Y.Base.create('registerData', Y.Model, [], {}, {
        ATTRS: {
            username: {value: ''},
            password: {value: ''},
            email: {value: ''}
        }
    });

    NS.Activate = Y.Base.create('activate', Y.Model, [], {}, {
        ATTRS: {
            userid: {value: 0},
            code: {value: 0},
            email: {value: ''},
            password: {value: ''}
        }
    });

    NS.PasswordRecovery = Y.Base.create('passwordRecovery', Y.Model, [], {}, {
        ATTRS: {
            email: {value: ''}
        }
    });

    NS.ListConfig = Y.Base.create('listConfig', Y.Model, [], {}, {
        ATTRS: {
            page: {value: 0}
        }
    });

    NS.UserListConfig = Y.Base.create('userListConfig', NS.ListConfig, [], {}, {
        ATTRS: {
            filter: {value: ''}
        }
    });

    NS.UserList = Y.Base.create('userList', Y.ModelList, [], {
        model: NS.User
    }, {
        ATTRS: {
            listConfig: {
                value: new NS.UserListConfig()
            }
        }
    });

    NS.UserOption = Y.Base.create('userOption', Y.Model, [], {}, {
        ATTRS: {
            value: {value: ''}
        }
    });

    NS.UserOptionList = Y.Base.create('userOptionList', Y.ModelList, [], {
        model: NS.UserOption
    });

    // --------------- Admin ------------------

    NS.Admin = NS.Admin || {};

    NS.Admin.User = Y.Base.create('user', NS.User, [], {}, {
        ATTRS: {
            email: {value: ''},
            groups: {value: []}
        }
    });

    NS.Admin.UserList = Y.Base.create('userList', NS.UserList, [], {
        model: NS.Admin.User
    });

    NS.Admin.Group = Y.Base.create('group', Y.Model, [], {}, {
        ATTRS: {
            title: {value: ''},
            sysname: {value: ''},
            permission: {value: {}}
        }
    });

    NS.Admin.GroupList = Y.Base.create('groupList', Y.ModelList, [], {
        model: NS.Admin.Group
    });


};