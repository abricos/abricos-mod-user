/*!
 * Module for Abricos Platform (http://abricos.org)
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['base'],
    mod: [
        {name: '{C#MODNAME}', files: ['structure.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        L = Y.Lang;

    NS.lif = function(f){
        return L.isFunction(f) ? f : function(){
        };
    };
    NS.life = function(f, p1, p2, p3, p4, p5, p6, p7){
        f = NS.lif(f);
        f(p1, p2, p3, p4, p5, p6, p7);
    };

    var Manager = function(callback){
        this.init(callback);
    };
    Manager.prototype = {
        init: function(callback){
            NS.manager = this;
            NS.life(callback);
        },
        ajax: function(d, callback){
            d = d || {};
            d['tm'] = Math.round((new Date().getTime()) / 1000);

            Brick.ajax('{C#MODNAME}', {
                'data': d,
                'event': function(request){
                    var d = L.isValue(request) && L.isValue(request.data) ? request.data : null,
                        result = L.isValue(d) ? (d.result ? d.result : null) : null;

                    NS.life(callback, result);
                }
            });
        },
        login: function(login, callback){
            this.ajax({
                'do': 'login',
                'savedata': login.toJSON()
            }, function(r){
                NS.life(callback);
            });
        }
    };
    NS.Manager = Manager;

    NS.manager = null;
    NS.initManager = function(callback){
        if (L.isValue(NS.manager)){
            return callback(NS.manager);
        }
        NS.manager = new NS.Manager(callback);
    };
};