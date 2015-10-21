var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['lib.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.PermissionListWidget = Y.Base.create('permissionListWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance, options){
            this.reloadModuleList();
        },
        reloadModuleList: function(){
            this.set('waiting', true);

            this.get('appInstance').getApp('sys').moduleList(function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('moduleList', result.moduleList);
                }
                this.renderModuleList();
            }, this);
        },
        renderModuleList: function(){
            var moduleList = this.get('moduleList'),
                perms = this.get('permissionList');

            if (!moduleList || !perms){
                return;
            }
            var tp = this.template, lst = "";

            moduleList.each(function(module){
                var m = module.toJSON(),
                    isFirst = true,
                    p = perms[m.name] || {},
                    checked;

                for (var action in m.roles){
                    checked = p[action] | 0 === 1 ? 'checked' : '';

                    lst += tp.replace('row', {
                        modtitle: isFirst ? m.title : '',
                        modname: m.name,
                        action: action,
                        title: m.roles[action],
                        checked: checked
                    });
                    isFirst = false;
                }
            });

            tp.gel('list').innerHTML = tp.replace('list', {
                'rows': lst
            });
        },
        toJSON: function(){
            var moduleList = this.get('moduleList'),
                perms = {};

            var tp = this.template,
                idPrefix = tp.gelid('row.chk');

            moduleList.each(function(module){
                var m = module.toJSON(),
                    p = {}, elChkId, elChk;

                for (var action in m.roles){
                    elChkId = idPrefix + '_' + m.name + '_' + action;
                    elChk = Y.Node.one(document.getElementById(elChkId));
                    p[action] = elChk.get('checked') ? 1 : 0;
                }
                perms[m.name] = p;
            });
            return perms;
        }
    }, {
        ATTRS: {
            component: {value: COMPONENT},
            templateBlockName: {value: 'widget,list,row'},
            moduleList: {value: null},
            permissionList: {value: null}
        }
    });
};

