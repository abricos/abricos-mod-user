'use strict';

var DEFAULT_OPTIONS = {
    id: 'module-user',
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
        var data = {
            do: 'userCurrent'
        };
        logger.debug('Get current user');
        this.api.post('user', data, function(err, result){
            if (err){
                return callback(err, null);
            }

            if (!result.userCurrent){
                err = new Error('Server error: did not return a userCurrent');
                return callback(err, null);
            }

            instance._cacheUserCurrent = result.userCurrent;
            return callback(null, result.userCurrent);
        });
    },
    auth: function(authData, callback){
        this.cacheClear();

        var logger = this.logger();

        var instance = this;
        var data = {
            do: 'auth',
            authData: authData
        };
        logger.debug('Authorization');
        this.api.post('user', data, function(err, result){
            if (err){
                return callback(err, null);
            }

            if (result.err && result.err > 0){
                var message = 'Unknown authorization error';
                switch (result.err) {
                    case 1:
                        message = 'Error in the username';
                        break;
                    case 2:
                        message = 'Invalid user name or password';
                        break;
                    case 3:
                        message = 'Do not fill in the required fields';
                        break;
                    case 4:
                        message = 'APIModule is blocked';
                        break;
                    case 5:
                        message = 'It is necessary to pass the activation of the user (confirmation email)';
                        break;
                }

                err = new Error(message);
                err.code = result.err;
                return callback(err, null);
            }

            if (!result.userCurrent){
                err = new Error('Server error: did not return a userCurrent');
                return callback(err, null);
            }
            instance._cacheUserCurrent = result.userCurrent;

            return callback(null, result.userCurrent);
        });
    },

    register: function(registerData, callback){
        var logger = this.logger(),
            data = {
                do: 'register',
                register: registerData
            };

        logger.debug('Registration');
        this.api.post('user', data, function(err, result){
            if (err){
                return callback(err, null);
            }

            if (result.err && result.err > 0){
                var message = 'Unknown registration error';
                switch (result.err) {
                    case 1:
                        message = '';
                        break;
                }

                err = new Error(message);
                err.code = result.err;
                return callback(err, null);
            }

            return callback(null, result.register);
        });
    },

    activate: function(activateData, callback){
        var logger = this.logger(),
            data = {
                do: 'activate',
                activate: activateData
            };

        logger.debug('Activating a new user');
        this.api.post('user', data, function(err, result){
            if (err){
                return callback(err, null);
            }

            if (result.err && result.err > 0){
                var message = 'Unknown activation error';
                switch (result.err) {
                    case 1:
                        message = 'User not found';
                        break;
                    case 2:
                        message = 'User is already activated';
                        break;
                    case 3:
                        message = 'Bad activation code';
                        break;
                }

                err = new Error(message);
                err.code = result.err;
                return callback(err, null);
            }

            return callback(null, result.activate);
        });
    }

};

module.exports = APIModule;