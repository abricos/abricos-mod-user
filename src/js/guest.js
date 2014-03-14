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
        {name: 'widget', files: ['notice.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,

        COMPONENT = this,

        SYS = Brick.mod.sys;

    var LoginForm = function(){
    };
    LoginForm.NAME = 'loginForm';
    LoginForm.prototype = {
        initializer: function(){
            this.set('fieldsClass', NS.Login);

            var instance = this;
            NS.initApp(function(){
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

            NS.appInstance.login(fields, function(err, result){
                if (err){

                    var errorText = this.template.replace('error', {
                        msg: err.msg
                    });

                    Brick.mod.widget.notice.show(errorText);
                    // TODO: show notice
                    // this.language.get('error');
                    // ...
                    instance.set('waiting', false);
                }else{
                    // reload page
                }
            }, this);

            e.halt();
        }
    };
    NS.LoginForm = LoginForm;

    NS.LoginFormWidget = Y.Base.create('loginFormWidget', Y.Widget, [
        SYS.Template,
        SYS.Language,
        SYS.Form,
        SYS.FormAction,
        SYS.WidgetWaiting,
        NS.LoginForm
    ], {

    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            }
        }
    });

};