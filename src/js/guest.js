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
                instance.set('waiting', false);
                if (err){
                    var errorText = this.template.replace('error', {
                        msg: err.msg
                    });

                    Brick.mod.widget.notice.show(errorText);
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
            },
            templateBlockName: {
                value: 'error'
            }
        }
    });

    var RegisterForm = function(){ };
    RegisterForm.NAME = 'registerForm';
    RegisterForm.prototype = {
        initializer: function(){
            this.set('fieldsClass', NS.RegisterData);

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
            var fields = this.get('fields'),
                instance = this;

            NS.appInstance.register(fields, function(err, result){
                instance.set('waiting', false);
                if (err){
                    var errorText = this.template.replace('errorreg', {
                        msg: err.msg
                    });

                    Brick.mod.widget.notice.show(errorText);
                }else{
                    // reload page
                }
            }, this);

            e.halt();
        },
        _clickRegisterForm: function(e){
            if (e.dataClick !== 'termofuse'){ return; }
            e.halt();

            new NS.termsOfUseDialog();

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

    NS.TermsOfUseDialog = Y.Base.create('termsOfUseDialog', SYS.Dialog, [], {
        /*
        onClick: function(e){
            switch (e.dataClick){
                case 'btest':
                    this.increment();
                    return true;
            }
        },
        /**/
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'termsofuse'
            }
            /*,
            width: {
                value: 400
            }
            /**/
        }
    });
};