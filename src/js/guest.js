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

    NS.LoginFormWidget = Y.Base.create('loginFormWidget', SYS.AppWidget, [
        Y.FormValidator,
        SYS.Form,
        SYS.FormAction
    ], {
        onSubmitFormAction: function(){
            var model = this.get('model'),
                err;

            if (this.hasErrors()){
                err = "empty";
            }

            if (err){
                err = this.language.get('login.error.' + err);
                Brick.mod.widget.notice.show(err);
                return;
            }

            this.set('waiting', true);

            NS.appInstance.login(model, function(err, result){
                if (!err){
                    Brick.Page.reload();
                } else {
                    this.set('waiting', false);
                }
            }, this);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            showMessages: {
                value: false
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

    NS.RegisterFormWidget = Y.Base.create('registerFormWidget', SYS.AppWidget, [
        Y.FormValidator,
        SYS.Form,
        SYS.FormAction
    ], {
        onSubmitFormAction: function(){
            var model = this.get('model'),
                attrs = model.getAttrs(),
                err;

            if (this.hasErrors()){
                err = "empty";
            } else if (attrs.password != attrs.passwordconfirm){
                err = "password";
            } else if (!attrs.termsofuse){
                err = "termsofuse";
            }

            if (err){
                err = this.language.get('register.error.' + err);
                Brick.mod.widget.notice.show(err);
                return;
            }

            model.set('passwordconfirm', '');

            this.set('waiting', true);

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
            if (e.dataClick === 'termofuse'){
                this.showTermsOfUse();
                return true;
            }
            return false;
        },
        showTermsOfUse: function(){
            if (this._termsDialog){
                return;
            }
            this._termsDialog = new NS.TermsOfUseDialog();
            var instance = this;
            setTimeout(function(){ // hack bug
                instance._termsDialog = null;
            }, 500);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            showMessages: {
                value: false
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
                    text = result.termsOfUse;
                }
                instance.setTermsOfUseText(text);
            }, this);
        },
        setTermsOfUseText: function(text){
            var tp = this.template;
            tp.setHTML('text', text);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'termsofuse'}
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
            component: {value: COMPONENT},
            templateBlockName: {value: 'regactivate,erroract'},
            userId: {value: 0},
            userEMail: {value: ''},
            userPassword: {value: ''}
        }
    });

    NS.PasswordRecoveryFormWidget = Y.Base.create('passwordRecoveryFormWidget', SYS.AppWidget, [
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
                new NS.PasswordRecoveryResultDialog();
            }, this);
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            useExistingWidget: {
                value: true
            },
            model: {
                value: new NS.PasswordRecovery()
            }
        }
    });

    NS.PasswordRecoveryResultDialog = Y.Base.create('passwordRecoveryResultDialog', SYS.Dialog, [
        SYS.WidgetWaiting
    ], {
        onClick: function(e){
            if (e.dataClick === 'ok'){
                this.close();
                return true;
            }
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'passrecokdialog'}
        }
    });

};