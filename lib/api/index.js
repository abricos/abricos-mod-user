'use strict';

var DEFAULT_OPTIONS = {
    id: 'module-user',
    apiVersion: '1',
    log: {
        console: {
            label: '<%= ^.log.console.label %>.user'
        }
    }
};

function APIModule(api, options){
    var config = api.config;

    this.config = config.instance([DEFAULT_OPTIONS, options]);

    this.api = api;
    this._cacheUserCurrent = null;

    this.name = 'user';
};
APIModule.prototype = {
    testAPI: function(){
        var TestAPI = require('./TestAPI');
        return new TestAPI(this);
    },
    logger: function(){
        return this.config.logger();
    },
    cacheClear: function(){
        this.current = null;
    },

    userCurrent: function(callback){
        var logger = this.logger();

        if (this._cacheUserCurrent){
            logger.debug('APIModule current is taken from the cache');
            return callback(null, this.current);
        }
        var instance = this;

        logger.debug('Get current user');
        this.api.get('user', 'current', null, function(err, result){
            if (err){
                return callback(err, null);
            }

            instance._cacheUserCurrent = result;
            return callback(null, result);
        });
    },

    auth: function(authData, callback){
        this.cacheClear();

        var logger = this.logger();

        var instance = this;
        logger.debug('Authorization');
        this.api.post('user', 'auth', authData, function(err, result){
            if (err){
                return callback(err, result);
            }
            instance._cacheUserCurrent = result;

            return callback(null, result);
        });
    },

    logout: function(callback){
        this.cacheClear();

        var logger = this.logger();

        var instance = this;
        logger.debug('Logout');
        this.api.get('user', 'logout', null, function(err, result){
            if (err){
                return callback(err, result);
            }
            instance._cacheUserCurrent = result;

            return callback(null, result);
        });
    },

    registration: function(registerData, callback){
        var logger = this.logger();

        logger.debug('Registration');
        this.api.post('user', 'registration', registerData, function(err, result){
            return callback(err, result);
        });
    },

    activation: function(activateData, callback){
        var logger = this.logger();

        logger.debug('Activating a new user');
        this.api.post('user', 'activation', activateData, function(err, result){
            return callback(err, result);
        });
    },

    termsOfUse: function(callback){
        var logger = this.logger();

        logger.debug('Get Terms of Use');
        this.api.get('user', 'termsofuse', null, function(err, result){
            return callback(err, result);
        });
    }

};

module.exports = APIModule;