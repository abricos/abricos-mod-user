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

    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    NS.Login = Y.Base.create('login', Y.Model, [ ], {
    }, {
        ATTRS: {
            /**
             * User Name or Email
             */
            username: {
                value: ''
            },
            password: {
                value: ''
            },
            autologin: {
                value: ''
            }
        }

    });

    NS.RegisterData = Y.Base.create('registerData', Y.Model, [ ], {
    }, {
        ATTRS: {
            username: {
                value: ''
            },
            password: {
                value: ''
            },
            email: {
                value: ''
            }
        }
    });

    NS.Activate = Y.Base.create('activate', Y.Model, [ ], {
    }, {
        ATTRS: {
            userid: {
                value: 0
            },
            code: {
                value: 0
            }
        }
    });

};