app.controller('l_rekap_pembelian_pertahun', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var control_link = "l_rekap_pembelian_pertahun";
    $scope.form = {};
    $scope.form.tahun = new Date();
    $scope.form.is_ppn = 1;
    /**
     * Ambil list lokasi
     */
    Data.get('acc/m_lokasi/getLokasi').then(function (response) {
        $scope.listLokasi = response.data.list;
    });

    /**
     * Ambil laporan dari server
     */
    $scope.view = function (is_export, is_print) {

        var param = {
            is_export: is_export,
            is_print: is_print,
            lokasi: $scope.form.lokasi != undefined ? $scope.form.lokasi.id : null,
            lokasi_nama: $scope.form.lokasi != undefined ? $scope.form.lokasi.nama : null,
            tahun: moment($scope.form.bulan).format('YYYY'),
            is_ppn : $scope.form.is_ppn,
        };


        if (is_export == 0 && is_print == 0) {
            Data.get(control_link + '/laporan', param).then(function (response) {
                if (response.status_code == 200) {
                    $scope.data   = response.data.data;
                    $scope.detail = response.data.detail;
                    $scope.tampilkan = true;
                } else {
                    $scope.tampilkan = false;
                }
            });
        } else {
            Data.get('site/base_url').then(function (response) {
//                console.log(response)
                window.open(response.data.base_url + "api/l_rekap_pembelian_pertahun/laporan?" + $.param(param), "_blank");
            });
        }
    };
});
