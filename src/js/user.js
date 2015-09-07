var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['form.js']},
        {name: '{C#MODNAME}', files: ['lib.js']}
    ]
};
Component.entryPoint = function(NS){

    var Y = Brick.YUI,

        COMPONENT = this,

        SYS = Brick.mod.sys;

    NS.UserFormWidget = Y.Base.create('userFormWidget', Y.Widget, [
        SYS.Form,
        SYS.WidgetClick
    ], {
        initializer: function(){
            var instance = this;
            NS.initApp(function(){
                instance._onLoadManager();
            });
        },
        _onLoadManager: function(){
            // ...
        },
        onClick: function(e){
            if (!NS.appInstance){
                return;
            }
            switch (e.dataClick) {
                case "user-logout":
                    this.userLogout();
                    return true;
            }
        },
        userLogout: function(){
            this.set('waiting', true);
            NS.appInstance.logout(function(err, result){
                Brick.Page.reload();
            }, this);
        }
    });

};