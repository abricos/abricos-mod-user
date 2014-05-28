/*!
 * Module for Abricos Platform (http://abricos.org)
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['base'],
    mod: [
        {name: 'sys', files: ['application.js']},
        {name: '{C#MODNAME}', files: ['structure.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,

        COMPONENT = this,

        SYS = Brick.mod.sys;

    var AppBase = function(){
    };
    AppBase.prototype = {
        login: function(login, callback, context){
            var instance = this;
            instance.ajax({
                'do': 'login',
                'savedata': login.toJSON()
            }, instance._onLogin, {
                context: instance,
                arguments: {callback: callback, context: context }
            });
        },
        _onLogin: function(err, res, details){
            var callback = details.callback,
                context = details.context;

            if (!err){
                var errorCode = res.data.err || 0;
                if (errorCode > 0){
                    var phId = 'ajax.login.error.' + errorCode;

                    err = {
                        code: errorCode,
                        msg: this.language.get(phId)
                    };
                }
            }

            if (callback){
                if (err){
                    callback.apply(context, [err]);
                } else {
                    callback.apply(context, [null, res.data]);
                }
            }
        },
        register: function(regData, callback, context){
            var instance = this;
            instance.ajax({
                'do': 'register',
                'savedata': regData.toJSON()
            }, instance._onRegister, {
                context: instance,
                arguments: {callback: callback, context: context}
            });
        },
        _onRegister: function(err, res, details){
            var callback = details.callback,
                context = details.context;

            if (!err){
                var errorCode = res.data.err || 0;
                if (errorCode > 0){
                    var phId = 'ajax.register.error.' + errorCode;

                    err = {
                        code: errorCode,
                        msg: this.language.get(phId)
                    };
                }
            }

            if (callback){
                callback.apply(context, err ? [err] : [null, res.data]);
            }
        },
        termsOfUse: function(callback, context){
            var instance = this;
            instance.ajax({'do': 'termsofuse'}, this._onTermsOfUse, {
                context: instance,
                arguments: {callback: callback, context: context}
            });
        },
        _onTermsOfUse: function(err, res, details){
            if (details.callback){
                details.callback.apply(details.context, err ? [err] : [null, res.data]);
            }
        },
        logout: function(callback, context){
            var instance = this;
            instance.ajax({'do': 'logout'}, this._onLogout, {
                context: instance,
                arguments: {callback: callback, context: context}
            });
        },
        _onLogout: function(err, res, details){
            if (details.callback){
                details.callback.apply(details.context, err ? [err] : [null, res.data]);
            }
        }

    };
    NS.AppBase = AppBase;

    var App = Y.Base.create('userApp', Y.Base, [
        SYS.AJAX,
        SYS.Language,
        NS.AppBase
    ], {
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            }
        }
    });
    NS.App = App;

    NS.appInstance = null;
    NS.initApp = function(callback, config){
        callback || (callback = function(){
        });

        if (NS.appInstance){
            return callback(null, NS.appInstance);
        }
        NS.appInstance = new NS.App({
            moduleName: '{C#MODNAME}'
        });
        callback(null, NS.appInstance);
    };

};