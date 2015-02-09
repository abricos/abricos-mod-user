'use strict';

var helper = require('abricos-rest').helper;
var async = require('async');

function TestAPI(userModule){
    this.userModule = userModule;

    this.user = {
        id: 0,
        username: 'user' + helper.randomInt(),
        password: 'pass' + helper.randomInt(),
        email: 'user' + helper.randomInt() + '@example.com'
    };

};
TestAPI.prototype = {
    registration: function(callback){
        var userModule = this.userModule;
        var api = userModule.api;
        var user = this.user;

        var registerInfo;
        var activationCode;
        var userInfo;

        var stack = [
            function(done){
                userModule.registration(user, function(err, result){
                    if (err){
                        var err = new Error('User registration error: ' + err.message);
                        return done(err);
                    }

                    if (result.emailInfo.error){
                        var err = new Error('User registration error: SMTPeshka is not running for email testing');
                        return done(err);
                    }

                    registerInfo = result;
                    done();
                });
            },

            function(done){
                var messageId = registerInfo.emailInfo.messageId;

                api.smtpeshka.email(messageId, function(err, email){
                    if (err){
                        var err = new Error('User registration error: could not get the activation email from SMTPeshka');
                        return done(err);
                    }

                    var $ = api.jsDOM.load(email.html);
                    var code = $('#activate-code').html();
                    activationCode = code;
                    done();
                });
            },

            function(done){
                var activateData = {
                    userid: registerInfo.userid,
                    code: activationCode
                };
                userModule.activation(activateData, function(err, result){
                    if (err){
                        var err = new Error('User activation error: ' + err.message);
                        return done(err);
                    }
                    done();
                });
            },

            function(done){
                var authData = {
                    username: user.username,
                    password: user.password,
                    autologin: true
                };
                userModule.auth(authData, function(err, result){
                    if (err){
                        var err = new Error('User authorization error: ' + err.message);
                        return done(err);
                    }
                    userInfo = result;

                    done();
                });
            }

        ];

        var instance = this;
        async.series(stack, function(err){
            if (err){
                return callback ? callback(err) : null;
            }
            instance.user = userInfo;

            return callback ? callback(null, userInfo) : null;
        });
    }
};

module.exports = TestAPI;