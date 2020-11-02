angular.module('app').controller('masterCtrl', function($scope, Data, $state, UserService, $location) {
    var user = UserService.getUser();
    var control_link = "acc/m_format_kode";
    if (user === null) {
        $location.path('/login');
    }
    Data.get(control_link + '/index').then(function(response) {
        $scope.form = response.data.list;
        $scope.form.tanggal = new Date($scope.form.tanggal)
        $scope.base_url = response.data.base_url;
    });
    $scope.save = function() {
        Data.post(control_link + '/save', $scope.form).then(function(result) {
            if (result.status_code == 200) {
                $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
            } else {
                $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
            }
        });
    };
});