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
    Login.NAME = 'login';
    Login.ATTRS = {
        login: {
            value: '',
            setter: function(val){
                // console.log('setter=' + val);
                return val;
            }
        },
        password: {
            value: ''
        }
    };
    Y.extend(Login, Y.Base, {
        authorize: function(){
            console.log('authorize()');
            console.log(this.get('login'));
        }
    });
    NS.Login = Login;


    var FormFieldProvider = function(){
    };
    FormFieldProvider.ATTRS = {
        boundingBox: {
            setter: Y.one
        }
    };
    FormFieldProvider.NAME = 'formFieldProvider';
    FormFieldProvider.prototype = {
        _fillAttributesFromFields: function(){
            var boundingBox = this.get(BOUNDING_BOX);

            boundingBox.all('.form-control').each(function(fieldNode){
                var name = fieldNode.get('name'),
                    value = fieldNode.get('value');

                if (this.attrAdded(name)){
                    this.set(name, value);
                }
            }, this);
        }
    };
    NS.FormFieldProvider = FormFieldProvider;


    var LoginForm = function(){
    };
    LoginForm.NAME = 'loginForm';
    LoginForm.prototype = {
        initializer: function(){
            Y.after(this._bindUIFormFieldProvider, this, 'bindUI');
        },
        _bindUIFormFieldProvider: function(){

            var boundingBox = this.get(BOUNDING_BOX);

            boundingBox.on({
                submit: Y.bind(this._onFormSubmit, this)
            });
        },
        _onFormSubmit: function(event){

            this._fillAttributesFromFields();

            this.authorize();
            return event.halt();
        }
    };
    NS.LoginForm = LoginForm;


    NS.LoginFormWidget = Y.Base.create('loginFormWidget', Y.Widget, [
        NS.Login,
        NS.FormFieldProvider,
        NS.LoginForm
    ], {

    });

};