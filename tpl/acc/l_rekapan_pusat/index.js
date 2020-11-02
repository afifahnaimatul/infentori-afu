app.controller('l_rekapan_pusat', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var control_link = "l_rekapan_pusat";
    $scope.form = {};
    $scope.form.tanggal = new Date();
    /**
     * Ambil list lokasi
     */
    Data.get('acc/m_lokasi/getLokasi').then(function (response) {
        $scope.listLokasi = response.data.list;
        if ($scope.listLokasi.length > 0) {
            $scope.form.m_lokasi_id = $scope.listLokasi[0];
        }
    });

    Data.get(control_link + '/akunRincian').then(function (data) {
        $scope.listAkun = data.data.list;
    });

    /**
     * Ambil laporan dari server
     */
    $scope.view = function (is_export, is_print) {
        var param = {
            export: is_export,
            print: is_print,
            m_lokasi_id: $scope.form.m_lokasi_id.id,
//            m_akun_id: $scope.form.m_akun_id.id,
            nama_lokasi: $scope.form.m_lokasi_id.nama,
//            nama_akun: $scope.form.m_akun_id.nama,
            tanggal: moment($scope.form.tanggal).format('YYYY'),
        };
        if (is_export == 0 && is_print == 0) {
            Data.get(control_link + '/laporan', param).then(function (response) {
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
                window.open(response.data.base_url + "api/l_rekapan_pusat/laporan?" + $.param(param), "_blank");
            });
        }
    };
});