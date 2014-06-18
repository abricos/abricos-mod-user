/*!
 * Module for Abricos Platform (http://abricos.org)
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['panel.js', 'form.js']},
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
    LoginForm.ATTRS = {
        model: {
            value: new NS.Login()
        }
    };

    LoginForm.prototype = {
        initializer: function(){
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
            var model = this.get('model'),
                instance = this;

            NS.appInstance.login(model, function(err, result){
                instance.set('waiting', false);
                if (err){
                    var errorText = this.template.replace('error', {
                        msg: err.msg
                    });
                    Brick.mod.widget.notice.show(errorText);
                } else {
                    Brick.Page.reload();
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
            },
            templateBlockName: {
                value: 'error'
            }
        }
    });

    var RegisterForm = function(){
    };
    RegisterForm.NAME = 'registerForm';
    RegisterForm.ATTRS = {
        model: {
            value: new NS.RegisterData()
        }
    };
    RegisterForm.prototype = {
        initializer: function(){
            var instance = this;
            NS.initApp(function(){
                instance._onLoadManager();
            });
        },
        _onLoadManager: function(){
            this.after('submitForm', this._submitRegisterForm);
            this.after('click', this._clickRegisterForm);
        },
        _submitRegisterForm: function(e){
            this.set('waiting', true);
            var model = this.get('model'),
                instance = this;

            NS.appInstance.register(model, function(err, result){
                instance.set('waiting', false);
                if (err){
                    var errorText = this.template.replace('errorreg', {
                        msg: err.msg
                    });

                    Brick.mod.widget.notice.show(errorText);
                } else {
                    Brick.Page.reload();
                }
            }, this);

            e.halt();
        },
        _clickRegisterForm: function(e){
            if (e.dataClick !== 'termofuse'){
                return;
            }
            e.halt();

            new NS.TermsOfUseDialog();
        }
    };
    NS.RegisterForm = RegisterForm;

    NS.RegisterFormWidget = Y.Base.create('registerFormWidget', Y.Widget, [
        SYS.Template,
        SYS.Language,
        SYS.Form,
        SYS.FormAction,
        SYS.WidgetClick,
        SYS.WidgetWaiting,
        NS.RegisterForm
    ], {
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'errorreg'
            }
        }
    });

    NS.TermsOfUseDialog = Y.Base.create('termsOfUseDialog', SYS.Dialog, [
        SYS.WidgetWaiting
    ], {
        initializer: function(){
            var instance = this;
            NS.initApp(function(){
                instance._onLoadManager();
            });
        },
        _onLoadManager: function(){
            var instance = this;
            NS.appInstance.termsOfUse(function(err, result){
                var text = "error";
                if (!err){
                    text = result.text;
                }
                instance.setTermsOfUseText(text);
            }, this);
        },
        setTermsOfUseText: function(text){
            var node = this.gel('text');
            if (node){
                node.setHTML(text);
            }
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'termsofuse'
            }
        }
    });
};