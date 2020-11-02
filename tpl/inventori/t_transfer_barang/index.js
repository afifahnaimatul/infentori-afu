app.controller('transferbarangCtrl', function ($scope, Data, $rootScope, $stateParams, $window, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_transfer_barang";
    var master = $stateParams.transfer ? 'Transaksi Transfer Barang' : 'Transaksi Terima Barang';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.master = master;
    $scope.transfer = $stateParams.transfer;

    $scope.callServer = function callServer(tableState) {
        tableStateRef = tableState;
        $scope.isLoading = true;
        var offset = tableState.pagination.start || 0;
        var limit = tableState.pagination.number || 10;
        /** set offset and limit */
        var param = {
            offset: offset,
            limit: limit
        };
        /** set sort and order */
        if (tableState.sort.predicate) {
            param['sort'] = tableState.sort.predicate;
            param['order'] = tableState.sort.reverse;
        }
        /** set filter */
        if (tableState.search.predicateObject) {
            param['filter'] = tableState.search.predicateObject;
            param.filter.transfer = $scope.transfer ? 'transfer' : 'terima';
        } else {
            param['filter'] = {
                transfer: $scope.transfer ? 'transfer' : 'terima',
            };
        }



        Data.get(control_link + '/index', param).then(function (response) {
            $scope.displayed = response.data.list;
            tableState.pagination.numberOfPages = Math.ceil(response.data.totalItems / limit);
        });
        $scope.isLoading = false;
    };

    /** create */
    $scope.create = function () {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_create = true;
        $scope.is_disable = false;
        $scope.formtitle = master + " | Form Tambah Data";
        $scope.form = {};
        $scope.form.tanggal_kirim = new Date();
    };
    /** update */
    $scope.update = function (form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_update = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Edit Data : " + form.kode_retur;
        $scope.form = form;
        $scope.getDetail(form);
        $scope.form.tanggal_kirim = new Date(form.tanggal_kirim);
        if (!$scope.transfer)
            $scope.form.tanggal_terima = new Date();
    };
    /** view */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Lihat Data : " + form.kode_retur;
        $scope.form = form;
        $scope.getDetail(form);
        $scope.form.tanggal_kirim = new Date(form.tanggal_kirim);
    };

    /** save action */
    $scope.save = function (status, form) {
        form.status = status;
        console.log(form)
        var params = {
            form: form,
            detail: $scope.detPenjualan
        };
        Data.post(control_link + '/save', params).then(function (result) {
            console.log(result)
            if (result.status_code == 200) {
                $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
                $scope.cancel();
            } else {
                $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
            }
        });
    };

    $scope.getDetail = function (form) {
        console.log(form);
        Data.get(control_link + "/getDetail?id=" + form.id + '&acc_m_lokasi_id=' + form.lokasi_asal.id).then(function (response) {
            $scope.detPenjualan = response.data.detail;
            $scope.total();
        });
    };

    /** cancel action */
    $scope.cancel = function () {
        if (tableStateRef == undefined) {
            $stateParams = undefined;
            $window.location.reload();
        } else {
            if (!$scope.is_view) {
                $scope.callServer(tableStateRef);
            }
            $scope.is_edit = false;
            $scope.is_view = false;
        }
    };
    $scope.trash = function (row) {
        Swal.fire({
            title: "Peringatan ! ",
            text: "Apakah Anda Yakin Ingin Menghapus Data Ini",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Iya, di Hapus",
            cancelButtonText: "Tidak",
        }).then((result) => {
            if (result.value) {
                row.is_deleted = 1;
                Data.post(control_link + '/trash', row).then(function (result) {
                    Swal.fire({
                        title: "Terhapus",
                        text: "Data Berhasil Di Hapus.",
                        type: "success"
                    }).then(function () {
                        $scope.cancel();
                    });
                });
            }
        });
    };
    $scope.restore = function (row) {
        Swal.fire({
            title: "Peringatan ! ",
            text: "Apakah Anda Yakin Ingin Merestore Data Ini",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Iya, di Restore",
            cancelButtonText: "Tidak",
        }).then((result) => {
            if (result.value) {
                row.is_deleted = 0;
                Data.post(control_link + '/trash', row).then(function (result) {
                    Swal.fire({
                        title: "Restore",
                        text: "Data Berhasil Di Restore.",
                        type: "success"
                    }).then(function () {
                        $scope.cancel();
                    });
                });
            }
        });
    };
    $scope.delete = function (row) {
        Swal.fire({
            title: "Peringatan ! ",
            text: "Apakah Anda Yakin Ingin Menghapus Permanen Data Ini",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Iya, di Hapus",
            cancelButtonText: "Tidak",
        }).then((result) => {
            if (result.value) {
                row.is_deleted = 1;
                Data.post(control_link + '/delete', row).then(function (result) {
                    Swal.fire({
                        title: "Terhapus",
                        text: "Data Berhasil Di Hapus Permanen.",
                        type: "success"
                    }).then(function () {
                        $scope.cancel();
                    });
                });
            }
        });
    };

    $scope.detPenjualan = [{
            no: ""
        }];

    $scope.addDetail = function () {
        var val = $scope.detPenjualan.length;
        var newDet = {
            no: ""
        };
        $scope.detPenjualan.push(newDet);
    };

    $scope.removeRow = function (paramindex) {
        var comArr = eval($scope.detPenjualan);
        if (comArr.length > 1) {
            $scope.detPenjualan.splice(paramindex, 1);
            $scope.total();
        } else {
            alert("Something gone wrong");
        }
        // $scope.prepareJurnal();
    };

    /** CRUD - END **/
    Data.get('acc/m_lokasi/index').then(function (response) {
        $scope.listLokasi = response.data.list;
    });

    $scope.cariBarang = function (val, index, $select, is_dijual) {
        if (val.length > 2) {

            var dataa = {
                acc_m_lokasi_id: $scope.form.lokasi_asal.id,
                val: val
            };

            Data.get("t_transfer_barang/cariBarang", dataa).then(function (response) {
                $scope.listBarang = response.data.list;
            });
        }
    };

    $scope.getPenjualan = function (penjualan) {
        Data.get(control_link + "/getPenjualan", penjualan).then(function (response) {
            console.log(response.data.form);
            $scope.form = response.data.form;
            $scope.form.acc_m_lokasi_id_retur = response.data.form.acc_m_lokasi_id;
            $scope.form.tanggal = new Date($scope.form.tanggal * 1000);
            $scope.form.inv_penjualan_id = penjualan;
            $scope.form.rusak = 0;
            $scope.form.tanggal_retur = new Date();
            $scope.detPenjualan = response.data.detail;
            $scope.detRetur = response.data.detail_retur;
            $scope.total();
        });
    };

    $scope.changeBarang = function (form, index, dari, $select) {
        angular.forEach($scope.detPenjualan, function (val, key) {
            if (dari != "ongkos_kirim") {
                if (form.stok == 0 && form.type != "jasa") {
                    alert("Stok " + form.nama + " Kosong!");
                    form = undefined;
                    $scope.detPenjualan[index].inv_m_barang_id = undefined;
                }
                if (form.type == "jasa") {
                    $scope.detPenjualan[index].qty = 1;
                } else if (form.type == "barang" && form.type_barcode != "serial") {
                    $scope.detPenjualan[index].qty = 1;
                } else {
                    $scope.detPenjualan[index].qty = 0;
                }

                $scope.detPenjualan[index].type = form.type;
                $scope.detPenjualan[index].harga_pokok = form.harga_pokok;
                $scope.detPenjualan[index].stok = form.stok;
                $scope.detPenjualan[index].satuan = form.satuan;
                $scope.detPenjualan[index].harga = form.harga_jual;
                $scope.detPenjualan[index].subtotal = form.harga_jual;
                $scope.detPenjualan[index].grand_total = form.harga_jual;
                $scope.detPenjualan[index].deskripsi = form.nama;
                /*AKUN KONEK ACC*/
                // $scope.detPenjualan[index].akun_persediaan_brg_id   = form.akun_persediaan_brg_id;
                // $scope.detPenjualan[index].akun_penjualan_id        = form.akun_penjualan_id;
                // $scope.detPenjualan[index].akun_hpp_id              = form.akun_hpp_id;
                /*AKUN KONEK ACC*/
                $scope.detPenjualan[index].diskon = 0;
                $scope.detPenjualan[index].diskon_persen = 0;
            }
            $scope.form.grand_total = 0;
            $scope.grand_total = 0;
            angular.forEach($scope.detPenjualan, function (value, key) {
                value.subtotal = parseInt(value.harga) * value.qty - parseInt(value.diskon);
                $scope.form.grand_total += parseInt(value.subtotal);
                $scope.grand_total += parseInt(value.subtotal);
            });
            $scope.listBarang = undefined;
            $scope.form.grand_total = $scope.grand_total;
            // $scope.prepareJurnal();
            // }
        });
        $scope.total();
    };

    $scope.kalkulasi = function () {
        var total = 0;
        angular.forEach($scope.detPenjualan, function (value, key) {
            total += parseInt(value.subtotal);
        });
        $scope.form.grand_total = parseInt($scope.form.ongkos_kirim) + parseInt(total);
        $scope.form.piutang = parseInt($scope.form.grand_total) - parseInt($scope.form.cash);
    };
    $scope.total = function () {
        $scope.form.grand_total = 0;
        angular.forEach($scope.detPenjualan, function (value, key) {
            if (parseInt(value.qty) > parseInt(value.stok)) {

                // alert('Jumlah Barang Retur Melebihi Barang');
                Swal.fire({
                    type: "error",
                    title: "Peringatan!",
                    text: "Jumlah Transfer Melebihi Jumlah Barang",
                    timeout: 1000
                });
                value.qty = 0;
            }
            value.qty = parseInt(value.qty);
            value.subtotal = parseInt(value.qty) * parseInt(value.harga);
            $scope.form.grand_total += parseInt(value.subtotal);
            console.log($scope.form.grand_total);
        });
        // $scope.kalkulasi();
        // $scope.prepareJurnal();
        $scope.total_lama = $scope.form.grand_total;
    };
});
