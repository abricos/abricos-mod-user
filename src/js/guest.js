/*!
 * Module for Abricos Platform (http://abricos.org)
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['node', 'widget'],
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        BOUNDING_BOX = 'boundingBox',
        L = Y.Lang;

    var Login = function(){
        Login.superclass.constructor.apply(this, arguments);
    };
    Y.extend(Login, Y.Base, {
        authorize: function(){
            console.log('authorize()');
            this.set('login', 'myyy-super-login');
        },
        _authorize: function(){
            console.log('_authorize()');
            this.authorize();
        }
    }, {
        NAME: 'login',
        ATTRS: {
            login: {
                value: '',
                setter: function(val){
                    console.log('setter='+val);
                    return val;
                }
            },
            password: {
                value: ''
            }
        }
    });
    NS.Login = Login;

    var LoginForm = function(){
        LoginForm.superclass.constructor.apply(this, arguments);
    };
    LoginForm.NAME = 'loginForm';
    LoginForm.ATTRS = {
        boundingBox: {
            setter: Y.one
        }
    };
    Y.extend(LoginForm, Y.Base, {
        // Prototype methods for your new class

        initializer: function(){

            var bbox = this.get('boundingBox');

            // LoginForm.prototype.authorize.call(this);

            this.authorize();

            console.log('login=' + this.get('login'));

            /*
             var host = config.host;

             var inputNode = host.one('[data-input="login"]');
             this.set('inputNode', inputNode);

             var inputNode = host.one('[data-input="login"]');
             this.set('inputNode', inputNode);
             /**/

        }
    });

    NS.LoginForm = LoginForm;

    NS.LoginFormWidget = Y.Base.create('loginForm', Y.Widget, [
        NS.Login,
        NS.LoginForm
    ]);


}
;