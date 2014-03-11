/*!
 * Module for Abricos Platform (http://abricos.org)
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['widget'],
    mod: [
        {name: 'sys', files: ['form.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        SYS = Brick.mod.sys;

    var LoginForm = function(){
    };
    LoginForm.NAME = 'loginForm';
    LoginForm.prototype = {
        initializer: function(){
            this.set('fieldsClass', NS.Login);

            var instance = this;
            NS.initManager(function(){
                instance._onLoadManager();
            });
        },
        _onLoadManager: function(){
            this.after('submitForm', this._submitLoginForm);
        },
        _submitLoginForm: function(e){

            this.set('waiting', true);
            var fields = this.get('fields'),
                instance = this;

            NS.manager.login(fields, function(err, result){
                instance.set('waiting', false);
            });

            e.halt();
        }
    };
    NS.LoginForm = LoginForm;

    NS.LoginFormWidget = Y.Base.create('loginFormWidget', Y.Widget, [
        SYS.Form,
        SYS.FormAction,
        SYS.WidgetWaiting,
        NS.LoginForm
    ], {

    });

};