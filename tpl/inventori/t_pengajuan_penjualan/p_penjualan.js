app.controller('ppenjualanCtrl', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_po_penjualan";
    var master = 'Transaksi Pengajuan Penjualan';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.master = master;

    // Untuk filter tabset
    $scope.status_po = 'pending';

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
        }

        if (param['filter'] == undefined) {
            param['filter'] = {};
        }

        param['filter']['inv_po_penjualan.status'] = $scope.status_po;

        Data.get(control_link + '/index', param).then(function (response) {
            $scope.displayed = response.data.list;
            tableState.pagination.numberOfPages = Math.ceil(response.data.totalItems / limit);
        });
        $scope.isLoading = false;
    };

    $scope.filterStatus = function (status) {
        $scope.status_po = status;
        $scope.callServer(tableStateRef);
    }

    /** create */
    $scope.create = function () {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_create = true;
        $scope.is_disable = false;
        $scope.formtitle = master + " | Form Tambah Data";
        $scope.form = {};

        $scope.form.type_barcode = 'non serial';
        $scope.form.konsinyasi = 0;
        $scope.form.type = "barang";
        $scope.form.harga_pokok = "average";
        $scope.form.tanggal = new Date();

        $scope.detPenjualan = [{
                no: ""
            }];
    };
    /** update */
    $scope.update = function (form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_update = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Edit Data : " + form.kode;
        $scope.form = form;
        $scope.getDetail(form);
        $scope.form.tanggal = new Date(form.tanggal * 1000);
        $scope.form.jatuh_tempo = new Date(form.jatuh_tempo * 1000);
    };
    /** view */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Lihat Data : " + form.kode;
        $scope.form = form;
        $scope.getDetail(form);
        $scope.form.tanggal = new Date(form.tanggal * 1000);
        $scope.form.jatuh_tempo = new Date(form.jatuh_tempo * 1000);
    };

    /** save action */
    $scope.save = function (form) {
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
        Data.get(control_link + "/getDetail?id=" + form.id + '&acc_m_lokasi_id=' + form.acc_m_lokasi_id.id).then(function (response) {
            $scope.detPenjualan = response.data.detail;
            $scope.total();
        });
    };

    /** cancel action */
    $scope.cancel = function () {
        $scope.status_po = 'pending';
        if (!$scope.is_view) {
            $scope.callServer(tableStateRef);
        }
        $scope.is_edit = false;
        $scope.is_view = false;
        console.log('cancel');
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

    Data.get('m_kategori/index').then(function (response) {
        $scope.listKategori = response.data.list;
    });

    Data.get('m_satuan/index').then(function (response) {
        $scope.listSatuan = response.data.list;
    });


    $scope.cariBarang = function (val, index, $select) {
        if (val.length > 2) {

            var dataa = {
                acc_m_lokasi_id: $scope.form.acc_m_lokasi_id.id,
                val: val
            };

            Data.get("m_barang/getBarang", dataa).then(function (response) {
                $scope.listBarang = response.data.list;

//                if ($scope.listBarang.length == 1) {
//
//                    $scope.detPenjualan[index].inv_m_barang_id = $scope.listBarang[0];
//                    $scope.changeBarang($scope.detPenjualan[index].inv_m_barang_id, index);
//
//                    var satuan = $scope.listBarang[0].nama_satuan != null ? $scope.listBarang[0].nama_satuan : "";
//                    $select.search = $scope.listBarang[0].kode + " - " + $scope.listBarang[0].nama + satuan + " (" + $scope.listBarang[0].stock + ")";
//                    focusInput("jumlah");
//                    $scope.nama_satuan = $scope.listBarang[0].nama_satuan;
//                }
            });
        }
    };

    $scope.changeBarang = function (form, index, dari, $select) {
        console.log(';pj');
        angular.forEach($scope.detPenjualan, function (val, key) {
            console.log('batu ok');
            if (dari != "ongkos_kirim") {
                console.log(';dan cow');
                if (form.stok == 0 && form.type != "jasa") {

                    alert("Stok " + form.nama + " Kosong!");
                    form = undefined;
                    $scope.detPenjualan[index].inv_m_barang_id = undefined;
                }
                if (form.type == "jasa") {
                    $scope.detPenjualan[index].jumlah = 1;
                } else if (form.type == "barang" && form.type_barcode != "serial") {
                    $scope.detPenjualan[index].jumlah = 1;
                } else {
                    $scope.detPenjualan[index].jumlah = 0;
                }

                $scope.detPenjualan[index].type = form.type;
                $scope.detPenjualan[index].stok = form.stok;
                $scope.detPenjualan[index].harga = form.harga_jual;
                $scope.detPenjualan[index].subtotal = form.harga_jual;
                $scope.detPenjualan[index].grand_total = form.harga_jual;
                $scope.detPenjualan[index].deskripsi = form.nama;

                $scope.detPenjualan[index].diskon = 0;
                $scope.detPenjualan[index].diskon_persen = 0;
            }
            $scope.form.grand_total = 0;
            $scope.grand_total = 0;
            angular.forEach($scope.detPenjualan, function (value, key) {
                value.subtotal = parseInt(value.harga) * value.jumlah - parseInt(value.diskon);
                $scope.form.grand_total += parseInt(value.subtotal);
                $scope.grand_total += parseInt(value.subtotal);
            });
            $scope.listBarang = undefined;
            $scope.form.ongkos_kirim = $scope.form.ongkos_kirim != undefined ? parseInt($scope.form.ongkos_kirim) : 0;
            $scope.form.grand_total = $scope.grand_total + $scope.form.ongkos_kirim;
            $scope.form.cash = $scope.form.grand_total;
            $scope.form.piutang = 0;
            // $scope.prepareJurnal();
            // }
        });
        console.log($scope.detPenjualan)
        $scope.total();
    };

    $scope.total = function (jenis_diskon) {
        $scope.form.grand_total = 0;
        angular.forEach($scope.detPenjualan, function (value, key) {
            if (value.type == "barang" && parseInt(value.jumlah) > parseInt(value.stok)) {
                alert('Jumlah Penjualan Melebihi Batas Stok');
                value.jumlah = 0;
            }

            value.subtotal = parseInt(value.jumlah) * parseInt(value.harga);
            $scope.form.grand_total += parseInt(value.subtotal);
        });

        // $scope.prepareJurnal();
        $scope.total_lama = $scope.form.grand_total;
    };

    $scope.cariCustomer = function ($query) {
//        if ($query.length >= 3) {
        Data.get('t_penjualan/customer', {'nama': $query}).then(function (response) {
            $scope.listCustomer = response.data;
        });
//        }
    };

    // $scope.getDetail = function(form){
    //   Data.get(control_link + "/getDetail", form).then(function (response) {
    //     $scope.detPenjualan = response.data.detail;
    //     $scope.total;
    //   });
    // };

    $scope.approval = function (form, setujui) {
        var konfirmasi = (setujui == 'approved') ? "Apakah Anda ingin Menyetujui Pengajuan ini?" : "Apakah Anda ingin Menolak Pengajuan ini?";
        var status = (setujui == 'approved') ? 'disetujui' : 'ditolak';
        Swal.fire({
            title: "Peringatan!",
            text: konfirmasi,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: (setujui == 'approved') ? "Setujui" : "Ya, Tolak",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.value) {
                form.setujui = setujui;
                Data.post(control_link + '/approval', form).then(function (response) {
                    if (response.status_code == 200) {
                        $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
                        $scope.cancel();
                    } else {
                        $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
                    }
                });
                $scope.cancel();
            }
        });
    };
});
