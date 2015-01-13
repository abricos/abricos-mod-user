'use strict';

var Abricos = require('abricos-rest');
var helper = Abricos.helper;
var should = require('should');

describe('Abricos.API', function(){

    var api,
        userModule;

    beforeEach(function(done){
        api = new Abricos.API();
        userModule = api.getModule('user');

        done();
    });

    describe('User Module API: ', function(){

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

        describe('Registration:', function(){

            var registerData = {
                username: 'user' + helper.randomInt(),
                password: 'pass' + helper.randomInt(),
                email: 'user' + helper.randomInt() + '@example.com'
            };

            describe('Process', function(){

                it('New user registration', function(done){
                    api.config.set('log.console.level', 'debug');

                    userModule.register(registerData, function(err, registerInfo){
                        api.config.set('log.console.level', 'info');

                        should.not.exist(err);
                        should.exist(registerInfo);
                        registerInfo.should.have.property('userid');

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

                it('Error in the username, error code 1', function(done){
                    var authData = {
                        username: '#)GD*@)a;sdfj asdf;j',
                        password: 'asdf'
                    };
                    userModule.auth(authData, function(err, result){
                        should.exist(err);
                        err.should.have.property('code', 1);
                        should.not.exist(result);
                        done();
                    });
                });

                it('Invalid user name or password, error code 2', function(done){
                    var authData = {
                        username: 'user',
                        password: 'mypassword'
                    };
                    userModule.auth(authData, function(err, result){
                        should.exist(err);
                        err.should.have.property('code', 2);
                        should.not.exist(result);
                        done();
                    });
                });

                it('Do not fill in the required fields, error code 3', function(done){
                    var authData = {};
                    userModule.auth(authData, function(err, result){
                        should.exist(err);
                        err.should.have.property('code', 3);
                        should.not.exist(result);
                        done();
                    });
                });

                // TODO: test error code 3 and 4

            });

            it('Admin user authorization', function(done){
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
        });
    });
});
