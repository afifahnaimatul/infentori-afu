angular.module("app").controller("AppCtrl", ["$rootScope", "$scope", "UserService", "Data", "$location", function($rootScope, $scope, UserService, Data, $location) {
    $scope.logout = function() {
        UserService.delUser();
        Data.get("site/logout").then(function(response) {
            $location.path('/login');
        });
    };

    /**
     * Cek domain
     */
    var namaDomain = document.location.hostname;
    if(namaDomain == 'systems.larensi.com'){
    	$rootScope.logoHeader = 'logoLandaSystems.png';
    	$rootScope.logoLogin = 'logoLandaSystems.png';
    }else if(namaDomain == 'proptech.larensi.com'){
    	$rootScope.logoHeader = 'logoRain.png';
    	$rootScope.logoLogin = 'logoRain.png';
    }else if(namaDomain == 'baca.larensi.com'){
    	$rootScope.logoHeader = 'logoWajibBaca.png';
    	$rootScope.logoLogin = 'logoWajibBaca.png';
    }else{
    	$rootScope.logoHeader = 'logo-new.png';
    	$rootScope.logoLogin = 'logo-ukdc.png';
    }
}]);