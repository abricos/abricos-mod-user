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
            value: '',
            setter: function(val){
                return val;
            }
        },
        password: {
            value: ''
        }
    };
    Y.extend(Login, SYS.Structure);
    NS.Login = Login;

};