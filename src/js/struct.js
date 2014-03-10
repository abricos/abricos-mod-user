/*!
 * Module for Abricos Platform (http://abricos.org)
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['attribute']
};
Component.entryPoint = function(NS){
    var Y = Brick.YUI;

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
    Y.extend(Login, Y.Base);
    NS.Login = Login;
};