/*!
 * Module for Abricos Platform (http://abricos.org)
 * Copyright 2008-2014 Alexander Kuzmin <roosit@abricos.org>
 * Licensed under the MIT license
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['widget'],
    mod: [
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        BOUNDING_BOX = 'boundingBox';

    var Form = function(){
    };
    Form.ATTRS = {
        boundingBox: {
            setter: Y.one
        },
        fieldsClass: {
            value: Y.Attribute
        },
        fields: {
            setter: '_fieldsSetterForm',
            getter: '_fieldsGetterForm'
        }
    };
    Form.NAME = 'form';
    Form.prototype = {
        initializer: function(){
            Y.after(this._bindUIForm, this, 'bindUI');
        },
        _bindUIForm: function(){
            this._syncUIFromFieldsForm();
            this._bindFieldsUIForm();
        },
        _bindFieldsUIForm: function(){
            var instance = this,
                fields = this.get('fields'),
                attrs = fields.getAttrs();

            Y.Object.each(attrs, function(v, n){
                fields.after(n + 'Change', instance._syncFieldUIForm, instance);
            }, instance);
        },
        _syncFieldUIForm: function(e){
            this._syncUIFromFieldsForm();
        },
        _syncFieldsFromUIForm: function(){
            var boundingBox = this.get(BOUNDING_BOX),
                fields = this.get('fields');

            boundingBox.all('.form-control').each(function(fieldNode){
                var name = fieldNode.get('name'),
                    value = fieldNode.get('value');

                if (fields.attrAdded(name)){
                    fields.set(name, value);
                }
            }, this);
        },
        _syncUIFromFieldsForm: function(){
            var boundingBox = this.get(BOUNDING_BOX),
                fields = this.get('fields');

            boundingBox.all('.form-control').each(function(fieldNode){
                var name = fieldNode.get('name');

                if (fields.attrAdded(name)){
                    fieldNode.set('value', fields.get(name));
                }
            }, this);
        },
        getNodeByFieldName: function(name){
            var boundingBox = this.get(BOUNDING_BOX),
                findNode = null;

            boundingBox.all('.form-control').each(function(node){
                if (node.get('name') === name){
                    findNode = node;
                }
            }, this);


            return findNode;
        },
        _fieldsSetterForm: function(val){
            var fieldsClass = this.get('fieldsClass');
            return new fieldsClass(val);
        },
        _fieldsGetterForm: function(val){
            return val;
        }

    };
    NS.Form = Form;

    var FormAction = function(){
    };
    FormAction.prototype = {
        initializer: function(){
            Y.after(this._bindUIFormAction, this, 'bindUI');

        },
        _bindUIFormAction: function(){
            var boundingBox = this.get(BOUNDING_BOX);
            boundingBox.on({
                reset: Y.bind(this._onResetFormAction, this),
                submit: Y.bind(this._onSubmitFormAction, this)
            });

            this.publish({
                resetForm: this._defResetFormAction,
                submitForm: this._defSubmitFormAction
            });
        },
        _onResetFormAction: function(){
            this.fire('resetForm');
        },
        _onSubmitFormAction: function(){
            this.fire('submitForm');
        },
        _defResetFormAction: function(e){
        },
        _defSubmitFormAction: function(e){
        }
    };
    NS.FormAction = FormAction;


    var LoginForm = function(){
    };
    LoginForm.NAME = 'loginForm';
    LoginForm.prototype = {
        initializer: function(){
            this.set('fieldsClass', NS.Login);

            this.after('submitForm', this._afterSubmitForm);

            // Y.after(this._bindUILoginForm, this, 'bindUI');
        },
        _afterSubmitForm: function(){
            console.log('_afterSubmitForm');
            return event.halt();
        },
        _bindUILoginForm: function(){
            var boundingBox = this.get(BOUNDING_BOX);

            boundingBox.on({
                submit: Y.bind(this._onFormSubmit, this)
            });
        },
        _onFormSubmit: function(event){
            this._fillFieldsFromInputNodes();

            var f = this.get('fields');
            console.log('fill fields = ');
            console.log(f.getAttrs());

            // this.authorize();
            return event.halt();
        }
    };
    NS.LoginForm = LoginForm;

    NS.LoginFormWidget = Y.Base.create('loginFormWidget', Y.Widget, [
        NS.Form,
        NS.FormAction,
        NS.LoginForm
    ], {

    });

};