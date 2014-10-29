/*
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['tabview'],
    mod: [
        {name: 'sys', files: ['data.js', 'form.js', 'container.js', 'widgets.js']},
        {name: 'user', files: ['guest.js']}
    ]
};
if (Brick.componentExists('antibot', 'bot')){
    var rm = Component.requires.mod;
    rm[rm.length] = {name: 'antibot', files: ['bot.js']};
}
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this;

    NS.GroupListWidget = Y.Base.create('managerWidget', NS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance, options){
            this.reloadGroupList();
        },
        reloadGroupList: function(){
            this.set('waiting', true);

            this.get('appInstance').adminGroupList(function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('groupList', result.admingrouplist);
                }
                this.renderGroupList();
            }, this);
        },
        renderGroupList: function(){
            var groupList = this.get('groupList');
            if (!groupList){
                return;
            }
            var tp = this.template, lst = "";

            groupList.each(function(group){
                var attrs = group.toJSON();

                lst += tp.replace('row', [
                    attrs
                ]);
            });

            tp.gel('list').innerHTML = tp.replace('list', {
                'rows': lst
            });
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'widget,list,row'
            },
            groupList: {
                value: null
            }
        }
    });
};

