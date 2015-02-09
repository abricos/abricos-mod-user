'use strict';

var Abricos = require('abricos-rest');
var helper = Abricos.helper;
var should = require('should');

describe('User Test API functions', function(){

    var userModule;

    beforeEach(function(done){
        var api = new Abricos.API();
        userModule = api.getModule('user');

        done();
    });

    describe('User Module API ', function(){

        var testAPI;

        it('testAPI instance', function(done){
            testAPI = userModule.testAPI();
            should.exist(testAPI);

            done();
        });

        it('registration()', function(done){
            testAPI.registration(function(err, user){
                should.not.exist(err);
                should.exist(user);
                done();
            });
        });

    });
});
