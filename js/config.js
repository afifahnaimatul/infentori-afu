// config
var app = angular.module("app").config(["$controllerProvider", "$compileProvider", "$filterProvider", "$provide", "$qProvider", "$httpProvider",
    function($controllerProvider, $compileProvider, $filterProvider, $provide, $qProvider, $httpProvider) {
        $qProvider.errorOnUnhandledRejections(false);
        app.controller = $controllerProvider.register;
        app.directive = $compileProvider.directive;
        app.filter = $filterProvider.register;
        app.factory = $provide.factory;
        app.service = $provide.service;
        app.constant = $provide.constant;
        app.value = $provide.value;
        $httpProvider.defaults.headers.common['Cache-Control'] = 'no-cache';
        $httpProvider.defaults.cache = false;
        // $httpProvider.interceptors.push('preventTemplateCache');
    }
]);