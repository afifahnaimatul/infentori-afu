app.controller('l_stok_barang_dagang', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var control_link = "l_stok_barang_dagang";
    $scope.form = {};
    $scope.form.bulan = new Date();
    /**
     * Ambil list lokasi
     */
    // Data.get('acc/m_lokasi/getLokasi').then(function (response) {
    //     $scope.listLokasi = response.data.list;
    //    if ($scope.listLokasi.length > 0) {
    //        $scope.form.lokasi = $scope.listLokasi[0];
    //    }
    // });

    Data.get('m_kategori/index', {filter: {'inv_m_kategori.is_deleted': 0}}).then(function (response) {
        $scope.listKategori = response.data.list;
        angular.forEach($scope.listKategori, function(isi, key){
          if(isi.id == 3){
            $scope.form.kategori = isi;
          }
        });
    })

    $scope.cariBarang = function($query) {

        if ($query.length >= 3) {
            Data.get('l_penjualan_perbarang/listbarang', {
                'nama': $query
            }).then(function(response) {
                $scope.getBarang = response.data;
            });
        }
    };

    /**
     * Ambil laporan dari server
     */
    $scope.view = function (is_export, is_print) {

        var param = {
            is_export: is_export,
            is_print: is_print,
            lokasi: $scope.form.lokasi != undefined ? $scope.form.lokasi.id : null,
            lokasi_nama : $scope.form.lokasi != undefined ? $scope.form.lokasi.nama : null,
            barang: $scope.form.barang != undefined ? $scope.form.barang.id : null,
            bulan: moment($scope.form.bulan).format('YYYY-MM'),
            kategori : $scope.form.kategori != undefined ? $scope.form.kategori.id : null,
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
                window.open(response.data.base_url + "api/l_stok_barang_dagang/laporan?" + $.param(param), "_blank");
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
