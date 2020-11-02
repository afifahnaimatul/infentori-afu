app.controller('l_rekapan_aliran_piutang', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var control_link = "l_rekapan_aliran_piutang";
    $scope.form = {};
    $scope.form.tanggal = new Date();

    /**
     * Ambil list lokasi
     */
    Data.get('acc/m_lokasi/getLokasi').then(function (response) {
        $scope.listLokasi = response.data.list;
        // if ($scope.listLokasi.length > 0) {
        //     $scope.form.m_lokasi_id = $scope.listLokasi[0];
        // }
    });

    /**
     * Ambil laporan dari server
     */
    $scope.view = function (is_export, is_print) {
        var param = {
            export: is_export,
            print: is_print,
            tanggal: moment($scope.form.tanggal).format('YYYY'),
        };

        if ($scope.form.m_lokasi_id != undefined) {
            param.m_lokasi_id = $scope.form.m_lokasi_id.id;
            param.nama_lokasi = $scope.form.m_lokasi_id.nama;
        }

        if (is_export == 0 && is_print == 0) {
            Data.get(control_link + '/laporan', param).then(function (response) {
                if (response.status_code == 200) {
                  $scope.data           = response.data.data;
                  $scope.detail_tambah  = response.data.detail_tambah;
                  $scope.total_tambah   = response.data.total_tambah;
                  $scope.detail_kurang  = response.data.detail_kurang;
                  $scope.total_kurang   = response.data.total_kurang;
                  $scope.saldo_akhir    = response.data.saldo_akhir;
                  $scope.saldo_awal     = response.data.saldo_awal;
                  $scope.tampilkan      = true;
                } else {
                    $scope.tampilkan = false;
                }
            });
        } else {
            Data.get('site/base_url').then(function (response) {
                window.open(response.data.base_url + "api/l_rekapan_aliran_piutang/laporan?" + $.param(param), "_blank");
            });
        }
    }
});
