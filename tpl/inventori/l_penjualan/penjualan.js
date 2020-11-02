app.controller('l_penjualanCtrl', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var control_link = 'l_penjualan';
    $scope.form = {};
    $scope.tampilkan = false;

    Data.get('acc/m_lokasi/getLokasi').then(function (response) {
        $scope.listLokasi = response.data.list;
//        if ($scope.listLokasi.length > 0) {
//            $scope.form.lokasi = $scope.listLokasi[0];
//        }
    });

    // Data.get('m_kategori/index', {filter: {
    //   'inv_m_kategori.is_dijual'  : 'ya',
    //   'inv_m_kategori.is_deleted' : 0
    // }}).then(function (response) {
    //     $scope.listKategori = response.data.list;
    // })

    $scope.view = function (is_export, is_print) {

        $scope.bulan = moment($scope.form.bulan).format('YYYY-MM-DD');

        var param = {
            kategori: $scope.form.kategori != undefined ? $scope.form.kategori : "",
            lokasi: $scope.form.lokasi != undefined ? $scope.form.lokasi.id : null,
            lokasi_nama: $scope.form.lokasi != undefined ? $scope.form.lokasi.nama : null,
            bulan: $scope.bulan,
            is_export: is_export,
            is_print: is_print
        };

        if (is_print == 1 || is_export == 1) {
            Data.get('site/base_url').then(function (response) {
                window.open(response.data.base_url + "api/l_penjualan/getPenjualan?" + $.param(param), "_blank");
            });
        } else {
            Data.get(control_link + '/getPenjualan', param).then(function (response) {
                if (response.status_code == 200) {
                    $scope.penjualanJadi      = response.data.jadi;
                    $scope.penjualanDagang    = response.data.dagangan;
                    $scope.qt_jadi            = response.data.qt_jadi;
                    $scope.qt_dagangan        = response.data.qt_dagangan;
                    $scope.qt_total           = response.data.qt_total;
                    $scope.dpp_jadi           = response.data.dpp_jadi;
                    $scope.dpp_dagangan       = response.data.dpp_dagangan;
                    $scope.dpp_total          = response.data.dpp_total;
                    $scope.kategori           = response.data.kategori;

                    $scope.tampilkan = true;
                } else {
                    $scope.tampilkan = false;
                }
            });
        }
        console.log($scope.penjualan);
    };
});
