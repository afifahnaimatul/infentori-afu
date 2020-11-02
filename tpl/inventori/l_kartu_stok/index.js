app.controller('l_kartu_stok', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var control_link = "l_kartu_stok";
    $scope.form = {};
    $scope.form.tanggal = {
        endDate: moment().add(1, 'M'),
        startDate: moment()
    };
    /**
     * Ambil list lokasi
     */
    Data.get('acc/m_lokasi/getLokasi').then(function (response) {
        $scope.listLokasi = response.data.list;
        if ($scope.listLokasi.length > 0) {
            $scope.form.m_lokasi_id = $scope.listLokasi[0];
        }
    });
     Data.get('l_kartu_stok/listbarang').then(function (response) {
        $scope.getBarang = response.data;
//        if ($scope.getBarang.length > 0) {
//            $scope.form.barang_id = $scope.getBarang[0];
//        }
        console.log($scope.getBarang);
    });

    $scope.cariBarang = function($query) {

        if ($query.length >= 3) {
            Data.get('l_kartu_stok/listbarang', {
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

        if ($scope.form.barang_id == undefined) {
            $rootScope.alert("Terjadi Kesalahan","Anda belum memilih Barang", "error");
            return false;
        }
        $scope.mulai = moment($scope.form.tanggal.startDate).format('DD-MM-YYYY');
        $scope.selesai = moment($scope.form.tanggal.endDate).format('DD-MM-YYYY');
        console.log($scope.form.barang_id);
        var param = {
            export: is_export,
            print: is_print,
            acc_m_lokasi_id: $scope.form.m_lokasi_id.id,
            barang_id: $scope.form.barang_id.id,
            startDate: moment($scope.form.tanggal.startDate).format('YYYY-MM-DD'),
            endDate: moment($scope.form.tanggal.endDate).format('YYYY-MM-DD'),
            show_kartu : true,
        };


        if (is_export == 0 && is_print == 0) {
            Data.get(control_link + '/laporan', param).then(function (response) {
                if (response.status_code == 200) {
                    $scope.data = response.data.model;
                    $scope.tanggal_mulai = response.data.tgl_mulai;
                    $scope.tanggal_selesai = response.data.tgl_selesai;
                    $scope.barang = response.data.barang;
                    $scope.lokasi = response.data.lokasi;
                    $scope.harga_pokok = response.data.harga_pokok;
                    $scope.disiapkan = response.data.disiapkan;
                    $scope.tampilkan = true;
                } else {
                    $scope.tampilkan = false;
                }
            });
        } else {
            Data.get('site/base_url').then(function (response) {
//                console.log(response)
                window.open(response.data.base_url + "api/l_kartu_stok/laporan?" + $.param(param), "_blank");
            });
        }
    };

    $scope.cariBarangAll = function () {
        var params = {
            // acc_m_lokasi_id: $scope.form.acc_m_lokasi_id.id,
        };

        Data.get('l_kartu_stok/listbarang', params).then(function(response) {
            $scope.getBarang = response.data;
        });
    };
    $scope.cariBarangAll();

    // $scope.view = function(form) {
    //     if ($scope.form.barang_id == undefined) {
    //         toaster.pop('error', "Terjadi Kesalahan", 'Anda Belum Memilih Barang');
    //         return false;
    //     }
    //     form.show_kartu = true
    //     Data.post('l_kartu_stok/laporan', form).then(function(response) {
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
    // Data.get('l_kartu_stok/listbarang').then(function(response) {
    //     $scope.getBarang = response.data;
    //     console.log($scope.getBarang);
    // });
    // Data.get("l_kartu_stok/listcabang").then(function(response) {
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
    //     Data.post('l_kartu_stok/laporan', form).then(function(response) {
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
    //     Data.get('l_kartu_stok/listbarang?produk_id=' + $stateParams.produk).then(function(response) {
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
