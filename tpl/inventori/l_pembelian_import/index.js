app.controller('l_pembelian_import', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var control_link = "l_pembelian_import";
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
//            is_ppn : $scope.form.is_ppn
        };


        if (is_export == 0 && is_print == 0) {
            Data.get(control_link + '/laporan', param).then(function (response) {
                if (response.status_code == 200) {
                    $scope.data = response.data.data;
                    $scope.detail = response.data.detail;
                    $scope.totalPerBeli = response.data.totalPerBeli;
                    console.log($scope.totalPerBeli);
                    $scope.tampilkan = true;
                } else {
                    $scope.tampilkan = false;
                }
            });
        } else {
            Data.get('site/base_url').then(function (response) {
//                console.log(response)
                window.open(response.data.base_url + "api/l_pembelian_import/laporan?" + $.param(param), "_blank");
            });
        }
    };



    // $scope.view = function(form) {
    //     if ($scope.form.barang_id == undefined) {
    //         toaster.pop('error', "Terjadi Kesalahan", 'Anda Belum Memilih Barang');
    //         return false;
    //     }
    //     form.show_kartu = true
    //     Data.post('l_penjualan_perbarang/laporan', form).then(function(response) {
    //         if (response.status_code == 200) {
    //             $scope.data = response.data.model;
    //             $scope.tanggal_mulai = response.data.tgl_mulai;
    //             $scope.tanggal_selesai = response.data.tgl_selesai;
    //             $scope.barang = response.data.barang;
    //             $scope.cabang = response.data.cabang;
    //             $scope.harga_pokok = response.data.harga_pokok;
    //             $scope.disiapkan = response.data.disiapkan;
    //             $scope.is_view = true
    //         } else {
    //             toaster.pop('error', "Terjadi Kesalahan", setErrorMessage(response.errors));
    //         }
    //     });
    // };




    // $scope.form = {};
    // $scope.form.tanggal = {
    //     startDate: moment(),
    //     endDate: moment().add(1, 'day')
    // };
    // $scope.is_view = false;
    // Data.get('l_penjualan_perbarang/listbarang').then(function(response) {
    //     $scope.getBarang = response.data;
    //     console.log($scope.getBarang);
    // });
    // Data.get("l_penjualan_perbarang/listcabang").then(function(response) {
    //     $scope.getCabang = response.data;
    //     if (typeof $stateParams.cabang != "undefined" && $stateParams.cabang > 0) {} else {
    //         $scope.form.m_cabang_id = parseInt(response.data[0].id);
    //     }
    // });
    // $scope.view = function(form) {
    //     if ($scope.form.barang_id == undefined) {
    //         toaster.pop('error', "Terjadi Kesalahan", 'Anda Belum Memilih Barang');
    //         return false;
    //     }
    //     form.show_kartu = true
    //     Data.post('l_penjualan_perbarang/laporan', form).then(function(response) {
    //         if (response.status_code == 200) {
    //             $scope.data = response.data.model;
    //             $scope.tanggal_mulai = response.data.tgl_mulai;
    //             $scope.tanggal_selesai = response.data.tgl_selesai;
    //             $scope.barang = response.data.barang;
    //             $scope.cabang = response.data.cabang;
    //             $scope.harga_pokok = response.data.harga_pokok;
    //             $scope.disiapkan = response.data.disiapkan;
    //             $scope.is_view = true
    //         } else {
    //             toaster.pop('error', "Terjadi Kesalahan", setErrorMessage(response.errors));
    //         }
    //     });
    // };
    // if ((typeof $stateParams.produk != "undefined" && $stateParams.produk > 0) && typeof $stateParams.cabang != "undefined" && $stateParams.cabang > 0) {
    //     Data.get('l_penjualan_perbarang/listbarang?produk_id=' + $stateParams.produk).then(function(response) {
    //         $scope.form.barang_id = response.data[0];
    //     });
    //     $scope.form.barang_id = {
    //         id: $stateParams.produk
    //     };
    //     $scope.form.m_cabang_id = parseInt($stateParams.cabang);
    //     $scope.form.tanggal = {
    //         startDate: moment().subtract(30, 'day'),
    //         endDate: moment(),
    //     };
    //     $scope.view($scope.form);
    // };
    // $scope.exportData = function(clases) {
    //     var blob = new Blob([document.getElementById(clases).innerHTML], {
    //         type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8"
    //     });
    //     saveAs(blob, "Laporan-Barang.xls");
    // };
});
