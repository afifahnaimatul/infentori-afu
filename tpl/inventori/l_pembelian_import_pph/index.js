app.controller('l_pembelian_import_pph', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var control_link = "l_pembelian_import_pph";
    $scope.form = {};
    $scope.form.bulan_awal = new Date(moment().subtract(1, 'M'));
    $scope.form.bulan_akhir = new Date(moment());
    $scope.form.is_ppn = 1;
    /**
     * Ambil list lokasi
     */
    Data.get('acc/m_lokasi/getLokasi').then(function (response) {
        $scope.listLokasi = response.data.list;
//        if ($scope.listLokasi.length > 0) {
//            $scope.form.lokasi = $scope.listLokasi[0];
//        }
    });

    Data.get('m_kategori/index', {is_deleted: 0}).then(function (response) {
        $scope.listKategori = response.data.list;
    })

    /**
     * Ambil laporan dari server
     */
    $scope.view = function (is_export, is_print) {

        var param = {
            is_export: is_export,
            is_print: is_print,
            bulan_awal: moment($scope.form.bulan_awal).format('YYYY-MM'),
            bulan_akhir: moment($scope.form.bulan_akhir).format('YYYY-MM'),
//            is_ppn: $scope.form.is_ppn
        };


        if (is_export == 0 && is_print == 0) {
            Data.get(control_link + '/laporan', param).then(function (response) {
                console.log(response)
                if (response.status_code == 200) {
                    $scope.data = response.data.data;
                    $scope.detail = response.data.detail;
                    $scope.tampilkan = true;
                } else {
                    $scope.tampilkan = false;
                }
            });
        } else {
            Data.get('site/base_url').then(function (response) {
//                console.log(response)
                window.open(response.data.base_url + "api/l_pembelian_import_pph/laporan?" + $.param(param), "_blank");
            });
        }
    };
});
