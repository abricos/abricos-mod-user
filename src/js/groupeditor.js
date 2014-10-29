/*
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['panel.js', 'form.js', 'widgets.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.GroupEditorDialog = Y.Base.create('groupEditorDialog', SYS.Dialog, [
        SYS.Template,
        SYS.WidgetClick,
        SYS.WidgetWaiting
    ], {
        initializer: function(){
            this.set('waiting', true);

            var instance = this;
            NS.initApp(function(err, appInstance){
                instance._onLoadManager();
            });
        },
        _onLoadManager: function(){
            this.set('waiting', false);

            NS.appInstance.adminGroupList(function(err, result){
                if (!err){
                    text = result.termsofuse;
                }
            }, this);
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'dialog'
            },
            groupId: {
                value: 0
            }

        }
    });

};

