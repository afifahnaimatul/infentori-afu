app.controller('poPembelianCtrl', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_po_pembelian";
    var master = 'Transaksi PO Pembelian';
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

        param['filter']['inv_po_pembelian.status'] = $scope.status_po;

        Data.get(control_link + '/index', param).then(function (response) {
            $scope.displayed = response.data.list;
            tableState.pagination.numberOfPages = Math.ceil(response.data.totalItems / limit);
        });

        $scope.isLoading = false;
    };

    $scope.filterStatus = function (status) {
        $scope.status_po = status;
        $scope.callServer(tableStateRef);
    };

    /** create */
    $scope.create = function () {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_create = true;
        $scope.is_disable = false;
        $scope.formtitle = master + " | Form Tambah Data";
        $scope.form = {};
        $scope.detPembelian = [{}];

        $scope.form.type_barcode = 'non serial';
        $scope.form.konsinyasi = 0;
        $scope.form.type = "barang";
        $scope.form.harga_pokok = "average";
        // $scope.form.m_akun_id = $scope.listAkunHarta[0].id;
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
        $scope.form.sub_total = $scope.detPembelian.jumlah * $scope.detPembelian.harga;
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
            detail: $scope.detPembelian
        };

        console.log(params);

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

    /** approve action */
    $scope.approve = function (form) {
        var param = {
            id: form.id,
            status: 'approved'
        };

        Data.post(control_link + '/approve', param).then(function (result) {
            if (result.status_code == 200) {
                Swal.fire({
                    title: "Approved",
                    text: "Data Berhasil di Approve",
                    type: "success"
                }).then(function () {
                    $scope.callServer(tableStateRef);
                    $scope.is_edit = false;
                });
            } else {
                $rootScope.alert("Terjadi kesalahan", result.errors, "error");
            }
        });
        console.log(form);
    };

    /** cancel action */
    $scope.cancel = function () {
        $scope.status_po = 'pending';
        if (!$scope.is_view) {
            $scope.callServer(tableStateRef);
        }
        $scope.is_edit = false;
        $scope.is_view = false;
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

    /** CRUD - END **/
    Data.get('acc/m_lokasi/index').then(function (response) {
        $scope.listLokasi = response.data.list;
    });

    Data.get('m_kategori/index', {filter: {"is_deleted": "0"}}).then(function (response) {
        $scope.listKategori = response.data.list;
    });

    Data.get('m_satuan/index', {filter: {"is_deleted": "0"}}).then(function (response) {
        $scope.listSatuan = response.data.list;
    });

    Data.get('site/getAkunPerTipe').then(function (response) {
        $scope.listAkunHarta = response.data.akunHarta;
        $scope.listAkunKewajiban = response.data.akunKewajiban;
        $scope.listAkunModal = response.data.akunModal;
        $scope.listAkunPendapatan = response.data.akunPendapatan;
        $scope.listAkunBeban = response.data.akunBeban;
    });

    $scope.getSupplier = function (nama) {
        if (nama.toString().length > 3) {
            var params = {
                nama: nama
            };
        } else {
            var params = {};
        }

        Data.get('/acc/m_supplier/getSupplier', params).then(function (response) {
            $scope.listSupplier = response.data.list;
        });
    };
    $scope.getSupplier('');

    $scope.getminmax = function (min, max, bc) {
        var bar = bc.length;

        if (bar >= min && bar <= max) {
            console.log("data sesuai")
        } else {
            toaster.pop("error", "Terjadi Kesalahan", "Panjang barcode harus sesuai");
        }
    };

    $scope.getDetail = function (form) {
        Data.get(control_link + "/getDetail?id=" + form.id + '&acc_m_lokasi_id=' + form.acc_m_lokasi_id.id).then(function (response) {
            $scope.detPembelian = response.data.detail;
            $scope.total();
        });
    };

    // Detail Pembelian
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
        var confirm = `Apakah Anda ingin menghapus item ini?`;
        Swal.fire({
            title: "Peringatan !",
            text: "Apakah Anda ingin menghapus item ini?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Hapus item",
            cancelButtonText: "Batal",
        }).then((result) => {
            if (result.value) {
                $scope.detPembelian.splice(paramindex, 1);
                $scope.total();
                // $scope.prepareJurnal();
            }
        });
    };
    // Detail Pembelian -  END

    // Perhitungan Total Pesanan
    $scope.changeBarang = function (form, index, $select) {
        angular.forEach($scope.detPembelian, function (val, key) {
            if (val.inv_m_barang_id.kode === form.kode && index !== key) {
                alert("Data produk sudah ada, silahkan pilih produk yang lain");
                $select.selected = undefined;
                form = undefined;
            } else {
                if (form.type_barcode == "serial") {
                    $scope.detPembelian[index].jumlah = 0;
                } else {
                    $scope.detPembelian[index].jumlah = 1;
                }
                $scope.detPembelian[index].type = form.type;
                $scope.detPembelian[index].harga = form.harga_beli * 1;
                $scope.detPembelian[index].subtotal = form.harga_beli;
                $scope.detPembelian[index].grand_total = form.harga_beli;
                /*AKUN KONEK ACC*/
                // $scope.detPembelian[index].akun_persediaan_brg_id = form.akun_persediaan_brg_id;
                // $scope.detPembelian[index].akun_pembelian_id = form.akun_pembelian_id;
                // $scope.detPembelian[index].akun_penjualan_id = form.akun_penjualan_id;
                /*AKUN KONEK ACC - END */
                $scope.detPembelian[index].diskon = 0;
                $scope.detPembelian[index].diskon_persen = 0;
                $scope.form.grand_total = 0;
                angular.forEach($scope.detPembelian, function (value, key) {
                    if (value.inv_m_barang_id !== undefined) {
                        value.subtotal = parseInt(value.harga) - parseInt(value.diskon);
                        $scope.form.grand_total += parseInt(value.subtotal);
                    }
                });
                $scope.form.cash = $scope.form.grand_total;
                $scope.form.hutang = 0;
                $scope.form.biaya_lain = 0;
                $scope.form.potongan = 0;
                $scope.form.diskon = 0;
                $scope.listBarang = undefined;
                // $scope.prepareJurnal();
            }
        });
        $scope.total();
    };
    $scope.total = function (jenis_diskon) {
        $scope.form.grand_total = 0;
        angular.forEach($scope.detPembelian, function (value, key) {
            if (value.inv_m_barang_id !== undefined) {
                /*DISKON*/
                value.subtotal = parseInt(value.jumlah) * parseInt(value.harga);
                $scope.form.grand_total += parseInt(value.subtotal);
            } else {
                value.harga = 0;
                value.jumlah = 0;
            }
        });

        $scope.form.cash = $scope.form.grand_total;
        $scope.form.hutang = 0;
        // $scope.prepareJurnal();
    };
    $scope.bayar = function () {
        if ($scope.form.cash == 0) {
            $scope.is_kas = 0;
            $scope.form.m_akun_id = '';
            $scope.form.hutang = $scope.form.grand_total;
        } else {
            $scope.is_kas = 1;
            if ($scope.form.cash > $scope.form.grand_total) {
                toaster.pop("error", "Terjadi Kesalahan", "Terbayar tidak boleh melebihi total");
                $scope.form.cash = 0;
                $scope.form.hutang = $scope.form.grand_total;
            } else {
                $scope.form.hutang = $scope.form.grand_total - $scope.form.cash;
            }
        }
        // $scope.prepareJurnal();
    };
    $scope.changeTunai = function (value) {
        $scope.is_tunai = !$scope.is_tunai;
        $scope.form.cash = $scope.form.grand_total;
        $scope.bayar();
    };
    // Perhitungan Total Pesanan - END

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
});
