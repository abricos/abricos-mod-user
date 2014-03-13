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
        L = Y.Lang,

        SYS = Brick.mod.sys;

    var AppBase = function(){
    };
    AppBase.prototype = {
        login: function(login, callback){
            this.ajax({
                'do': 'login',
                'savedata': login.toJSON()
            }, function(err, r){
                console.log(arguments);
                console.log(this);

            });
        }
    };
    NS.AppBase = AppBase;

    var App = Y.Base.create('userApp', Y.Base, [
        SYS.AJAX,
        NS.AppBase
    ]);
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