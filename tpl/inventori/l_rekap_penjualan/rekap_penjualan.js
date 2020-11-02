app.controller('l_rekappenjualanCtrl', function ($scope, Data, $rootScope) {
   var control_link = 'l_rekap_penjualan';
   $scope.form = {};
   $scope.tampilkan = false;

   Data.get('acc/m_lokasi/getLokasi').then(function (response) {
        $scope.listLokasi = response.data.list;
    });

    $scope.view = function (is_export, is_print) {
        $scope.bulan_awal = moment($scope.form.bulan_awal).format('YYYY-MM-DD');
        $scope.bulan_akhir = moment($scope.form.bulan_akhir).format('YYYY-MM-DD');

        if ($scope.form.bulan_awal == undefined || $scope.form.bulan_akhir == undefined) {
            $rootScope.alert("Terjadi Kesalahan","Bulan Awal dan Bulan Akhir Harus Diisi!", "error");
            return false;
        }

        var param = {
            bulan_awal: $scope.bulan_awal,
            bulan_akhir: $scope.bulan_akhir,
            is_export: is_export,
            is_print: is_print,
            lokasi : $scope.form.lokasi != undefined ? $scope.form.lokasi.id : null,
            lokasi_nama : $scope.form.lokasi != undefined ? $scope.form.lokasi.nama : null,
        };

        if (is_print == 1 || is_export == 1) {
            Data.get('site/base_url').then(function (response) {
                window.open(response.data.base_url + "api/l_rekap_penjualan/getRekapPenjualan?" + $.param(param), "_blank");
            });
        } else {
            Data.get(control_link + '/getRekapPenjualan', param).then(function (response) {
                if (response.status_code == 200) {
                    $scope.allTotal       = response.data.allTotal;
                    $scope.rekapPenjualan = response.data.list;
                    $scope.bulan          = response.data.bulan;
                    $scope.data           = response.data.data;
                    $scope.totalPerBulan  = response.data.totalPerbulan;
                    console.log(response.data);
                    console.log($scope.totalPerbulan);
                    $scope.tampilkan = true;
                }
                else {
                    $scope.tampilkan = false;
                }
            });
        }
        console.log(param);
   };
});
