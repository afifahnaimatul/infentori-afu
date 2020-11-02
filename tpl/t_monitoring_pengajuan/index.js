app.controller('monitoringPengajuanCtrl', function($scope, Data, $rootScope, $uibModal) {
    $scope.displayed = [];
    $scope.form = {};
    $scope.form.periode = {
        startDate : new Date(),
        endDate: new Date()
    };
    Data.get('acc/m_lokasi/getLokasi').then(function (response) {
        $scope.listLokasi = response.data.list;
        if ($scope.listLokasi.length > 0) {
            $scope.form.m_lokasi_id = $scope.listLokasi[0];
            $scope.tampilkan();
        }
    });
    $scope.dateOptions = {
        locale: {
            format: "DD-MM-YYYY"
        },
    };
    $scope.getSaldo = function(lokasi_id){
        Data.get("site/getSaldoAkun", {m_lokasi_id: lokasi_id}).then(function(response) {
            $scope.form.saldo_bak = response.data.total;
        });
    };
    $scope.tampilkan = function() {
        var param = {
            startDate: moment($scope.form.periode.startDate).format('YYYY-MM-DD'),
            endDate: moment($scope.form.periode.endDate).format('YYYY-MM-DD'),
            m_lokasi_id: $scope.form.m_lokasi_id.id
        };
        Data.get('site/monitoringPengajuan', param).then(function(response) {
            $scope.displayed = response.data.list;
            $scope.form.total_pengajuan = response.data.data.total;
            $scope.form.total_budget = response.data.data.total_budget;
            $scope.form.total_nonbudget = response.data.data.total_nonbudget;
            $scope.form.lokasi = $scope.form.m_lokasi_id.nama;
            $scope.getSaldo(param.m_lokasi_id);
        });
    };
    /** view */
    $scope.view = function(form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_create = false;
        $scope.formtitle = master + " | Lihat Data : " + form.nama;
        $scope.form = form;
    };
});