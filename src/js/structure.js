/*!
 * Module for Abricos Platform (http://abricos.org)
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['structure.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    var Login = function(){
        Login.superclass.constructor.apply(this, arguments);
    };
    Login.NAME = 'login';
    Login.ATTRS = {
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
    };
    Y.extend(Login, SYS.Structure);
    NS.Login = Login;

    var RegisterData = function(){
        RegisterData.superclass.constructor.apply(this, arguments);
    };
    RegisterData.NAME = 'registerData';
    RegisterData.ATTRS = {
        username: {
            value: ''
        },
        password: {
            value: ''
        },
        email: {
            value: ''
        }
    };
    Y.extend(RegisterData, SYS.Structure);
    NS.RegisterData = RegisterData;
};