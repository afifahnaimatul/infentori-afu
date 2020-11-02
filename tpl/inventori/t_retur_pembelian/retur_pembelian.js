app.controller('returPembelianCtrl', function ($scope, Data, $rootScope, $stateParams, $window, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_retur_pembelian";
    var master = 'Transaksi Retur Pembelian';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.master = master;

    Data.get('acc/m_akun/akunKas').then(function (data) {
        $scope.listAkun = data.data.list;
    });

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
        $scope.form.rusak = 0;

        $scope.listJurnal = [];

        Data.get(control_link + '/cariFP').then(function (response) {
            $scope.listPembelian = response.data;
        });
    };

    $scope.cariFP = function(cari){
      if(cari == undefined || cari.toString().length < 3)
        return;

      var params = {
        cari : cari
      };
      Data.get(control_link + '/cariFP', params).then(function (response) {
        $scope.listPembelian = response.data;
      });
    }

    /** update */
    $scope.update = function (form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_update = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Edit Data : " + form.kode_retur;
        $scope.form = form;
        $scope.getDetail(form);
        $scope.getJurnal(form);
        $scope.form.tanggal = new Date(form.tanggal * 1000);
        $scope.form.jatuh_tempo = new Date(form.jatuh_tempo * 1000);

    };
    /** view */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Lihat Data : " + form.kode_retur;
        $scope.form = form;
        $scope.getDetail(form);
        $scope.getJurnal(form);
        $scope.form.tanggal = new Date(form.tanggal * 1000);
        $scope.form.tanggal_retur = new Date(form.tanggal_retur * 1000);
        $scope.form.jatuh_tempo = new Date(form.jatuh_tempo * 1000);
    };

    /** save action */
    $scope.save = function (form) {
        form.type = 'save';
        console.log(form)
        var params = {
            form: form,
            detail: $scope.detPembelian,
            listJurnal: $scope.listJurnal,
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
        Data.get(control_link + "/getDetail?id=" + form.id + '&acc_m_lokasi_id=' + form.acc_m_lokasi_id.id).then(function (response) {
            $scope.detPembelian = response.data.detail;
        });
    };

    $scope.getJurnal = function (form) {
        Data.get(control_link + "/getJurnal?id=" + form.id + '&acc_m_lokasi_id=' + form.acc_m_lokasi_id.id).then(function (response) {
            $scope.listJurnal = response.data.list;

            $scope.totalDebit = 0;
            $scope.totalKredit = 0;
            angular.forEach($scope.listJurnal, function (val, key) {
                $scope.totalDebit += val.debit;
                $scope.totalKredit += val.kredit;
            })
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

    $scope.detPembelian = [{
            no: ""
        }];

    $scope.addDetail = function () {
        var val = $scope.detPembelian.length;
        var newDet = {
            no: ""
        };
        $scope.detPembelian.push(newDet);
    };

    $scope.removeRow = function (paramindex) {
        var comArr = eval($scope.detPembelian);
        if (comArr.length > 1) {
            $scope.detPembelian.splice(paramindex, 1);
            $scope.total();
        } else {
            alert("Something gone wrong");
        }
//        $scope.prepareJurnal();
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

                if ($scope.listBarang.length == 1) {

                    $scope.detPembelian[index].inv_m_barang_id = $scope.listBarang[0];
                    $scope.changeBarang($scope.detPembelian[index].inv_m_barang_id, index);

                    var satuan = $scope.listBarang[0].nama_satuan != null ? $scope.listBarang[0].nama_satuan : "";
                    $select.search = $scope.listBarang[0].kode + " - " + $scope.listBarang[0].nama + satuan + " (" + $scope.listBarang[0].stock + ")";
                    focusInput("jumlah");
                    $scope.nama_satuan = $scope.listBarang[0].nama_satuan;
                }
            });
        }
    };

    $scope.getPembelian = function (pembelian) {
      var params = {
        id : pembelian.id,
      }
        Data.get(control_link + "/getPembelian", params).then(function (response) {

            $scope.form = response.data.form;
            $scope.form.tanggal = new Date($scope.form.tanggal * 1000);
            $scope.form.inv_pembelian_id = pembelian;
            $scope.form.rusak = 0;
            $scope.form.tanggal_retur = new Date();
            $scope.detPembelian = response.data.detail;
            $scope.total();
        });
    };

    $scope.changeBarang = function (form, index, dari, $select) {
        console.log(';pj');
        angular.forEach($scope.detPembelian, function (val, key) {
            console.log('batu ok');
            if (dari != "ongkos_kirim") {
                console.log(';dan cow');
                if (form.stok == 0 && form.type != "jasa") {
                    // toaster.pop({
                    //     type: "error",
                    //     title: "Peringatan!",
                    //     body: "Stok " + form.nama + " Kosong",
                    //     timeout: 3000
                    // });
                    alert("Stok " + form.nama + " Kosong!");
                    form = undefined;
                    $scope.detPembelian[index].inv_m_barang_id = undefined;
                }
                if (form.type == "jasa") {
                    $scope.detPembelian[index].jumlah = 1;
                } else if (form.type == "barang" && form.type_barcode != "serial") {
                    $scope.detPembelian[index].jumlah = 1;
                } else {
                    $scope.detPembelian[index].jumlah = 0;
                }

                $scope.detPembelian[index].type = form.type;
                $scope.detPembelian[index].stok = form.stok;
                $scope.detPembelian[index].harga = form.harga_beli;
                $scope.detPembelian[index].subtotal = form.harga_beli;
                $scope.detPembelian[index].grand_total = form.harga_beli;
                $scope.detPembelian[index].deskripsi = form.nama;
                /*AKUN KONEK ACC*/
                // $scope.detPembelian[index].akun_persediaan_brg_id   = form.akun_persediaan_brg_id;
                // $scope.detPembelian[index].akun_penjualan_id        = form.akun_penjualan_id;
                // $scope.detPembelian[index].akun_hpp_id              = form.akun_hpp_id;
                /*AKUN KONEK ACC*/
                $scope.detPembelian[index].diskon = 0;
                $scope.detPembelian[index].diskon_persen = 0;
            }
            $scope.form.grand_total = 0;
            $scope.grand_total = 0;
            angular.forEach($scope.detPembelian, function (value, key) {
                value.subtotal = parseInt(value.harga) * value.jumlah - parseInt(value.diskon);
                $scope.form.grand_total += parseInt(value.subtotal);
                $scope.grand_total += parseInt(value.subtotal);
            });
            $scope.listBarang = undefined;
            $scope.form.ongkos_kirim = $scope.form.ongkos_kirim != undefined ? parseInt($scope.form.ongkos_kirim) : 0;
            $scope.form.grand_total = $scope.grand_total + $scope.form.ongkos_kirim;
            $scope.form.piutang = 0;
//            $scope.prepareJurnal();
            // }
        });
        $scope.total();
    };

    $scope.kalkulasi = function () {
        var total = 0;
        angular.forEach($scope.detPembelian, function (value, key) {
            total += parseFloat(value.subtotal);
        });
        $scope.form.grand_total = parseFloat($scope.form.ongkos_kirim) + parseFloat(total);
        $scope.form.piutang = parseFloat($scope.form.grand_total) - parseFloat($scope.form.cash);
    };

    $scope.total = function () {
        $scope.form.grand_total = 0;
        angular.forEach($scope.detPembelian, function (value, key) {
            if (parseFloat(value.jumlah_retur) > parseFloat(value.jumlah)) {

                // alert('Jumlah Barang Retur Melebihi Barang');
                Swal.fire({
                    type: "error",
                    title: "Peringatan!",
                    text: "Jumlah Barang Retur Melebihi Jumlah Barang",
                    timeout: 1000
                });
                value.jumlah_retur = 0;
            }
            value.jumlah_retur = parseFloat(value.jumlah_retur);
            value.subtotal = parseFloat(value.jumlah_retur) * parseFloat(value.harga_retur);
            $scope.form.grand_total += parseFloat(value.subtotal);
            console.log($scope.form.grand_total);
        });
        $scope.form.piutang = 0;
        // $scope.kalkulasi();
//        $scope.prepareJurnal();
        $scope.total_lama = $scope.form.grand_total;
        $scope.form.ppn   = $scope.form.grand_total * 10/100;
        $scope.form.ppnbm   = $scope.form.grand_total + $scope.form.ppn;
        console.log('asas');
        console.log($scope.form);
    };

    $scope.changeTunai = function (value) {
        $scope.is_tunai = !$scope.is_tunai;
        $scope.form.cash = $scope.form.grand_total;
        $scope.bayar();
    };
    $scope.bayarBlur = function () {
        if (isNaN($scope.form.cash)) {
            $scope.form.cash = 0;
            $scope.bayar();
        }
    };
    $scope.bayar = function () {
        $scope.total_lama = $scope.form.grand_total;
        $scope.form.cash = parseInt($scope.form.cash);
        if ($scope.form.cash == 0) {
            $scope.is_kas = 0;
            $scope.form.m_akun_id = undefined;
            $scope.form.piutang = $scope.form.grand_total;
        } else {
            $scope.is_kas = 1;
            if ($scope.form.cash > $scope.form.grand_total) {
                toaster.pop("error", "Terjadi Kesalahan", "Terbayar tidak boleh melebihi total");
                $scope.form.cash = 0;
                $scope.form.piutang = $scope.form.grand_total;
            } else {
                $scope.form.piutang = $scope.form.grand_total - $scope.form.cash;
            }
        }
//        $scope.prepareJurnal();
    };

    $scope.prepareJurnal = function () {

        $scope.totalDebit = 0;
        $scope.totalKredit = 0;

        var listJurnal = [];
        var subtotal = 0;
        var index = 0;

        if ($scope.form.grand_total > 0) {
            listJurnal[index] = {
                debit: $scope.form.grand_total,
                kredit: 0,
                type: 'debit',
                akun: $scope.form.acc_m_akun_id,
                keterangan: 'Retur Pembelian'
            };
            index++;
        }

        angular.forEach($scope.detPembelian, function (val, key) {
            if (val.inv_m_barang_id.akun_pembelian_id.id != undefined && val.jumlah_retur > 0) {
//                console.log(val.subtotal_edit)
                console.log(listJurnal[val.inv_m_barang_id.akun_pembelian_id.id])

                var exists = false;
                var key_exists = 0;
                angular.forEach(listJurnal, function (vals, keys) {
                    if (val.inv_m_barang_id.akun_pembelian_id.id == vals.akun.id) {
                        exists = true;
                        key_exists = keys;
                    }
                });

                //debit
                if (exists) {
                    listJurnal[key_exists].kredit += val.subtotal;
                } else {
                    listJurnal[index] = {
                        debit: 0,
                        kredit: val.subtotal,
                        type: 'kredit',
                        akun: val.inv_m_barang_id.akun_pembelian_id,
                        keterangan: 'Retur Pembelian'
                    };

                    index++;
                }

                subtotal += val.subtotal_edit;
                $scope.totalDebit += val.subtotal;
                $scope.totalKredit += val.subtotal;
            }
        })

        $scope.listJurnal = listJurnal;
    }

    $scope.cariCustomer = function ($query) {
        if ($query.length >= 3) {
            Data.get('t_penjualan/customer', {'nama': $query}).then(function (response) {
                $scope.listCustomer = response.data;
            });
        }
    };

    $scope.getminmax = function (min, max, bc) {
        var bar = bc.length;

        if (bar >= min && bar <= max) {
            console.log("data sesuai")
        } else {
            toaster.pop("error", "Terjadi Kesalahan", "Panjang barcode harus sesuai");
        }
    };

    $scope.kodeBR = function () {
        Data.get(control_link + "/kodeBarang").then(function (data) {
            $scope.form.kode = data.data.kode;
        });
    };

    if ($stateParams.form != null) {
        var tableStateRef;
        $scope.create();
        $scope.getPembelian($stateParams.form);

        // Data.get(control_link + "/getPenjualan", $stateParams.form).then(function (response) {
        //     $scope.detPenjualan         = response.data.detail;
        //     $scope.form                 = response.data.form;
        //     $scope.form.rusak           = 0;
        //     $scope.form.tanggal         = new Date($scope.form.tanggal);
        //     $scope.form.tanggal_retur   = new Date();
        //     $scope.total();
        // });
    }
});
