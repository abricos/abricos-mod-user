'use strict';

var Abricos = require('abricos-rest');
var helper = Abricos.helper;
var should = require('should');

describe('User Module API', function(){

    var api,
        userModule;

    beforeEach(function(done){
        api = new Abricos.API();
        userModule = api.getModule('user');

        done();
    });

    describe('Guest', function(){

        it('Guest user info', function(done){
            userModule.userCurrent(function(err, userCurrnet){

                should.not.exist(err);
                should.exist(userCurrnet);

                userCurrnet.should.have.property('id', 0);
                userCurrnet.should.have.property('username', 'Guest');
                userCurrnet.should.have.property('session');
                userCurrnet.should.have.property('permission');

                done();
            });
        });
    });

    describe('Registration', function(){

        var registerData = {
            username: 'user' + helper.randomInt(),
            password: 'pass' + helper.randomInt(),
            email: 'user' + helper.randomInt() + '@example.com'
        };
        var newUserInfo;

        describe('Process', function(){

            it('should be registered new user', function(done){
                userModule.register(registerData, function(err, registerInfo){
                    should.not.exist(err);
                    should.exist(registerInfo);
                    registerInfo.should.have.property('userid');

                    newUserInfo = registerInfo;

                    registerInfo.should.have.property('emailInfo');

                    var emailInfo = registerInfo.emailInfo;

                    should.not.exist(emailInfo.error, 'run SMTPeshka for email testing');

                    done();
                });
            });

            var activateEmail;

            it('should be get message from SMTPeshka', function(done){
                var messageId = newUserInfo.emailInfo.messageId;
                api.smtpeshka.email(messageId, function(err, email){
                    should.not.exist(err);
                    should.exist(email);

                    email.should.have.property('html');
                    email.should.have.property('messageId', messageId);

                    activateEmail = email;
                    done();
                });
            });

            var activationCode;

            it('should be activate code in HTML email', function(done){
                var $ = api.jsDOM.load(activateEmail.html);
                var code = $('#activate-code').html();
                should.exist(code);
                activationCode = code;
                done();
            });

            it('should be activated error, code 1 (`User not found`)', function(done){
                var activateData = {
                    userid: 84681354
                };
                userModule.activate(activateData, function(err, result){
                    should.exist(err);
                    err.should.have.property('code', 1);
                    should.not.exist(result);
                    done();
                });
            });

            it('should be activated error, code 3 (`Bad activation code`)', function(done){
                var activateData = {
                    userid: newUserInfo.userid
                };
                userModule.activate(activateData, function(err, result){
                    should.exist(err);
                    err.should.have.property('code', 3);
                    should.not.exist(result);
                    done();
                });
            });

            it('should be activated user', function(done){
                var activateData = {
                    userid: newUserInfo.userid,
                    code: activationCode
                };
                userModule.activate(activateData, function(err, result){
                    should.not.exist(err);
                    should.exist(result);
                    done();
                });
            });

            it('should be activated error, code 2 (`User is already activated`)', function(done){
                var activateData = {
                    userid: newUserInfo.userid,
                    code: activationCode
                };
                userModule.activate(activateData, function(err, result){
                    should.exist(err);
                    err.should.have.property('code', 2);
                    should.not.exist(result);
                    done();
                });
            });
        });

        describe('Errors', function(){

            it('Username already registered, error code 1', function(done){

                userModule.register(registerData, function(err, registerInfo){
                    should.exist(err);
                    should.not.exist(registerInfo);

                    done();
                });
            });

        });

    });

    describe('Authorization', function(){

        describe('Authorization errors', function(){

            it('Error in the username, error code `BAD_USERNAME`', function(done){
                var authData = {
                    username: '#)GD*@)a;sdfj asdf;j',
                    password: 'asdf'
                };
                userModule.auth(authData, function(err, result){
                    should.exist(err);
                    err.should.have.property('code', 'BAD_USERNAME');
                    should.not.exist(result);
                    done();
                });
            });

            it('Invalid user name or password, error code `INVALID_USERNAME_PASSWORD`', function(done){
                var authData = {
                    username: 'user',
                    password: 'mypassword'
                };
                userModule.auth(authData, function(err, result){
                    should.exist(err);
                    err.should.have.property('code', 'INVALID_USERNAME_PASSWORD');
                    should.not.exist(result);
                    done();
                });
            });

            it('Do not fill in the required fields, error code `EMPTY_PARAMS`', function(done){
                var authData = {};
                userModule.auth(authData, function(err, result){
                    should.exist(err);
                    err.should.have.property('code', 'EMPTY_PARAMS');
                    should.not.exist(result);
                    done();
                });
            });

            it('should be authorization error code `USER_BLOCKED`');
            it('should be authorization error code `NOT_ACTIVATE`');

        });

        describe.only('Authorization process', function(){

            it('should admin authorization', function(done){
                var authData = {
                    username: 'admin',
                    password: 'admin',
                    autologin: true
                };

                userModule.auth(authData, function(err, result){
                    should.not.exist(err);

                    should.exist(result);

                    done();
                });
            });

            it('should logout', function(done){
                userModule.logout(function(err, result){
                    should.not.exist(err);

                    should.exist(result);

                    done();
                });
            });
        });

    });
});
