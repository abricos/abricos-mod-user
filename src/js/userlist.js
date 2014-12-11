/*
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    yui: ['aui-pagination'],
    mod: [
        {name: 'sys', files: ['form.js']},
        {name: '{C#MODNAME}', files: ['usereditor.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,
        COMPONENT = this,
        SYS = Brick.mod.sys;

    NS.UserListWidget = Y.Base.create('managerWidget', SYS.AppWidget, [], {
        onInitAppWidget: function(err, appInstance, options){
            this.set('waiting', true);

            this.get('appInstance').groupList(function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('groupList', result.groupList);
                }
                this.reloadUserList();
            }, this);
        },
        reloadUserList: function(){
            this.set('waiting', true);
            var listConfig = this.get('listConfig');

            this.get('appInstance').userList(listConfig, function(err, result){
                this.set('waiting', false);
                if (!err){
                    this.set('userList', result.userList);
                }
                this.renderUserList();
            }, this);
        },
        renderUserList: function(){
            var userList = this.get('userList');
            var groupList = this.get('groupList');
            if (!userList || !groupList){
                return;
            }
            var tp = this.template, lst = "";

            userList.each(function(user){
                var attrs = user.toJSON();

                var aGroup = [];
                for (var i = 0; i < attrs.groups.length; i++){
                    var gid = attrs.groups[i];
                    var group = groupList.getById(gid);
                    if (!group){
                        continue;
                    }
                    aGroup[aGroup.length] = group.get('title');
                }
                lst += tp.replace('row', [
                    {
                        joindate: Brick.dateExt.convert(attrs.joindate),
                        lastvisit: Brick.dateExt.convert(attrs.lastvisit),
                        groups: aGroup.join(', ')
                    },
                    attrs
                ]);
            });

            tp.gel('list').innerHTML = tp.replace('list', {
                'rows': lst
            });

            /*
             var listConfig = userList.get('listConfig').getAttrs(),
             pageCount = listConfig.total / listConfig.limit;

             new Y.Pagination({
             after: {
             changeRequest: function(event) {

             console.log(
             'page:', event.state.page,
             'getOffsetPageNumber:', this.getOffsetPageNumber()
             );
             }
             },
             boundingBox: tp.gel('pagination'),
             offset: 1,
             circular: false,
             page: listConfig.page,
             total: 10,
             strings: {
             next: '»',
             prev: '«'
             }
             }).render();
             /**/
        },
        onClick: function(e){
            switch (e.dataClick) {
                case 'filter-set':
                    this._setFilterFromInput();
                    return true;
                case 'filter-clear':
                    this.clearUserFilter();
                    return true;
                case 'reload':
                    this.reloadUserList();
                    return true;
            }

            var userId = e.target.getData('id') | 0;
            if (userId === 0){
                return;
            }

            switch (e.dataClick) {
                case 'user-edit':
                    this.showUserEditorDialog(userId);
                    return true;
            }
        },
        showUserEditorDialog: function(userId){
            var dialog = new NS.UserEditorDialog({userId: userId});

            dialog.on('editorSaved', function(){
                this.reloadUserList();
            }, this);
        },
        _setFilterFromInput: function(){
            var filter = this.template.gel('filter').value;
            this.setUserFilter(filter);
        },
        setUserFilter: function(filter){
            filter = filter || "";
            this.template.gel('filter').value = filter;
            var listConfig = this.get('listConfig');
            listConfig.set('filter', filter);
            this.reloadUserList();
        },
        clearUserFilter: function(){
            this.setUserFilter("");
        }
    }, {
        ATTRS: {
            component: {
                value: COMPONENT
            },
            templateBlockName: {
                value: 'widget,list,row'
            },
            listConfig: {
                value: new NS.UserListConfig()
            },
            groupList: {
                value: null
            },
            userList: {
                value: null
            }
        }
    });

};

