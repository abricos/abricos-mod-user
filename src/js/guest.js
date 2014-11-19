/*!
 * Module for Abricos Platform (http://abricos.org)
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['aui-form-validator'],
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

    NS.LoginFormWidget = Y.Base.create('loginFormWidget', NS.AppWidget, [
        Y.FormValidator,
        SYS.Form,
        SYS.FormAction
    ], {
        onSubmitFormAction: function(){
            this.set('waiting', true);
            var model = this.get('model');

            NS.appInstance.login(model, function(err, result){
                this.set('waiting', false);
                if (!err){
                    Brick.Page.reload();
                }
            }, this);
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            useExistingWidget: {
                value: true
            },
            updateUIFromModel: {
                value: false
            },
            model: {
                value: new NS.Login()
            }
        }
    });

    NS.RegisterFormWidget = Y.Base.create('registerFormWidget', NS.AppWidget, [
        SYS.Form,
        SYS.FormAction
    ], {
        onSubmitFormAction: function(){
            this.set('waiting', true);
            var model = this.get('model');

            NS.appInstance.register(model, function(err, result){
                this.set('waiting', false);
                if (err){
                    return;
                }
                new NS.RegisterActivateDialog({
                    userId: result.register.userid,
                    userEMail: model.get('email'),
                    userPassword: model.get('password')
                });

            }, this);
        },
        onClick: function(e){
            if (e.dataClick !== 'termofuse'){
                return;
            }

            new NS.TermsOfUseDialog();
            return true;
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            useExistingWidget: {
                value: true
            },
            model: {
                value: new NS.RegisterData()
            }
        }
    });

    NS.TermsOfUseDialog = Y.Base.create('termsOfUseDialog', SYS.Dialog, [
        SYS.WidgetWaiting
    ], {
        initializer: function(){
            var instance = this;
            NS.initApp(function(err, appInstance){
                instance._onLoadManager();
            });
        },
        _onLoadManager: function(){
            var instance = this;
            NS.appInstance.termsOfUse(function(err, result){
                var text = "error";
                if (!err){
                    text = result.termsofuse;
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

    NS.RegisterActivateDialog = Y.Base.create('registerActivateDialog', SYS.Dialog, [
        SYS.WidgetWaiting
    ], {
        initializer: function(){
            Y.after(this._syncRegisterActivateDialog, this, 'syncUI');
        },
        _syncRegisterActivateDialog: function(){
            var instance = this;
            NS.initApp(function(){
                instance._onLoadManager();
            });
        },
        _onLoadManager: function(){
            var elEmail = this.gel('email');
            elEmail.setHTML(this.get('userEMail'));
        },
        onClick: function(e){
            if (e.dataClick === 'activate'){
                this.registerActivate();
                return true;
            }
        },
        registerActivate: function(){
            this.set('waiting', true);

            var activate = new NS.Activate({
                userid: this.get('userId'),
                code: this.gel('code').get('value'),
                email: this.get('userEMail'),
                password: this.get('userPassword')
            });
            NS.appInstance.activate(activate, function(err, result){
                if (!err){
                    Brick.Page.reload();
                }
            }, this);
        }
    }, {
        ATTRS: {
            userId: {
                value: 0
            },
            userEMail: {
                value: ''
            },
            userPassword: {
                value: ''
            },
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'regactivate,erroract'
            }
        }
    });

    NS.PasswordRecoveryFormWidget = Y.Base.create('passwordRecoveryFormWidget', NS.AppWidget, [
        SYS.Form,
        SYS.FormAction
    ], {
        onSubmitFormAction: function(){
            this.set('waiting', true);
            var model = this.get('model');

            NS.appInstance.passwordRecovery(model, function(err, result){
                this.set('waiting', false);
                if (err){
                    return;
                }
                /*
                new NS.RegisterActivateDialog({
                    userId: result.register.userid,
                    userEMail: model.get('email'),
                    userPassword: model.get('password')
                });
                /**/

            }, this);
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            useExistingWidget: {
                value: true
            },
            model: {
                value: new NS.PasswordRecovery()
            }
        }
    });
};