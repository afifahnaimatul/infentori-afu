app.controller('pembelianCtrl', function ($scope, Data, $rootScope, $stateParams, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_pembelian";
    var master = $stateParams.is_import ? 'Transaksi Pembelian Import' : 'Transaksi Pembelian';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.master = master;
    $scope.is_import = $stateParams.is_import;

    Data.get('acc/m_akun/akunDetail').then(function (data) {
        $scope.listAkun = data.data.list;
    });

    $scope.date_open = function ($event, dt) {
        $event.preventDefault();
        $event.stopPropagation();

        dt.opened = true;
    };

    $scope.setFaktur = function (param, max) {
        if ($scope.form['form' + param] != undefined) {
            if ($scope.form['form' + param].length == max) {
//                console.log("ok")
                setTimeout(function () {
                    $('#form' + (param + 1)).focus()
                }, 1)
            }
        }
    }

    // Filter Bulan custom
    $scope.filter = {};
    $scope.filterBulan = function () {
        $scope.callServer(tableStateRef);
    }

    $scope.reset_bulan = function () {
        $scope.filter = undefined;
        $scope.callServer(tableStateRef);
    }
    // Filter Bulan custom - END

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

        if ($scope.filter != undefined && $scope.filter.bulan != undefined) {
            param.bulan = $scope.filter.bulan
        }

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
        param['filter']['is_import'] = $stateParams.is_import;

        Data.get(control_link + '/index', param).then(function (response) {
            $scope.displayed = response.data.list;
            tableState.pagination.numberOfPages = Math.ceil(response.data.totalItems / limit);
            $scope.totalDpp = response.data.totalDpp;
        });
        $scope.isLoading = false;
    };

    $scope.changeSupplier = function (supplier) {
//        console.log("ok")
//        console.log("supplier", supplier);
        $scope.form.is_ppn = supplier.is_ppn;
        $scope.form.jenis_ppn = $scope.form.jenis_ppn != undefined ? $scope.form.jenis_ppn : 10;
        $scope.total();
    }

    /** create */
    $scope.create = function () {
        $scope.getFakturPajak();
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_create = true;
        $scope.is_disable = false;
        $scope.formtitle = master + " | Form Tambah Data";
        $scope.form = {};
        $scope.detPembelian = [{
            no: ""
        }];
        $scope.listJurnal = [];
        $scope.form.type_barcode = 'non serial';
        $scope.form.konsinyasi = 0;
        $scope.form.ongkos_kapal_rp = 0;
        $scope.form.ongkos_kapal_usd = 0;
        $scope.form.type = "barang";
        $scope.form.harga_pokok = "average";
        $scope.form.is_import = $stateParams.is_import;
        $scope.form.tanggal = new Date();
        $scope.form.jatuh_tempo = new Date();
        $scope.getAll();
        $scope.initDetailPajak();
        $scope.getFP();
        $scope.form.acc_m_lokasi_id = $scope.listLokasi.length > 0 ? $scope.listLokasi[0] : [];
        $scope.form.jenis_pembelian = 'barang';
        $scope.totalPajakPelabuhan = 0;
//        $scope.getPelabuhan();

        $scope.cariBarangAll();

        Data.get('t_pembelian/getLastData').then(function (response) {
//            console.log(response)
//            $scope.form.acc_m_lokasi_id = response.data.depo;
            $scope.form.tanggal = new Date(response.data.tanggal);
//            if ($scope.form.acc_m_lokasi_id != undefined) {
//                $scope.getKode($scope.form.acc_m_lokasi_id)
//            }
        });
//        console.log("import gak", form);
        // $scope.form.m_akun_id = $scope.listAkunHarta[0].id;
    };
    /** update */
    $scope.update = function (form) {
        $scope.getFakturPajak();
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_update = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Edit Data : " + form.kode;
        $scope.form = form;
        $scope.getDetail(form);
        $scope.form.tanggal = new Date(form.tanggal);
        $scope.form.tanggal_ntpn = new Date(form.tanggal_ntpn);
        $scope.form.jatuh_tempo = new Date(form.jatuh_tempo * 1000);
        $scope.getFP();
        $scope.getDetailPajak(form);
        $scope.getDetailBiaya(form);
//        $scope.getPelabuhan();
//        $scope.getJurnal(form)

        $scope.changeSupplier($scope.form.acc_m_kontak_id)
    };
    /** view */
    $scope.view = function (form) {
//        console.log(form);
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.is_create = false;
        $scope.formtitle = master + " | Lihat Data : " + form.kode;
        $scope.form = form;
        $scope.getDetail(form);
        $scope.form.tanggal = new Date(form.tanggal);
        $scope.form.tanggal_ntpn = new Date(form.tanggal_ntpn);
        $scope.form.jatuh_tempo = new Date(form.jatuh_tempo * 1000);
        $scope.getDetailPajak(form);
        $scope.getDetailBiaya(form);
        $scope.getJurnal(form);

        $scope.changeSupplier($scope.form.acc_m_kontak_id)
    };

    /** save action */
    $scope.save = function (form, status) {
        form.type = 'save';
        form.status = status;
        if (($scope.form.form1 != undefined && $scope.form.form2 != undefined && $scope.form.form3 != undefined && $scope.form.form4 != undefined) || !form.is_import) {
            form.pib = $scope.form.form1 + "-" + $scope.form.form2 + "-" + $scope.form.form3 + "-" + $scope.form.form4;

            var params = {
                form: form,
                detail: $scope.detPembelian,
                detailFP: $scope.listDetailPajak,
                detailFP2: $scope.listDetailPajakNonPPN,
                detailBiaya: $scope.listBiayaTambahan,
                listJurnal: $scope.listJurnal
            };

            Data.post(control_link + '/save', params).then(function (result) {
                if (result.status_code == 200) {
                    $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
                    $scope.cancel();
                } else {
                    $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
                }
            });
        } else {
            $rootScope.alert("Terjadi Kesalahan", 'Nomor PIB wajib diisi', "error");
        }

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

    $scope.unpost = function (form) {
//        console.log(form)
        Swal.fire({
            title: "Peringatan ! ",
            text: "Apakah Anda Yakin Ingin Unpost Data Ini ? ",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Iya, Unpost",
            cancelButtonText: "Tidak",
        }).then((result) => {
            if (result.value) {
                Data.post(control_link + '/unpost', form).then(function (response) {
                    if (response.status_code == 200) {
                        $rootScope.alert("Tersimpan", "Data Berhasil Di Simpan", "success");
                        $scope.is_view = false;
                        $scope.cancel();
                    } else {
                        $rootScope.alert("Terjadi kesalahan", response.errors, "error");
                    }
                })
            }
        });

    }
    $scope.fill = function (form) {
        var params = {
            id: form.id,
            acc_m_lokasi_id: form.acc_m_lokasi_id
        };

        Data.get(control_link + '/getPengajuanPembelian', params).then(function (response) {
            $scope.pengajuan = response.data;
            $scope.form = $scope.pengajuan;
            $scope.form.tanggal = new Date($scope.form.tanggal * 1000);
            $scope.form.potongan = 0;
            $scope.form.biaya_lain = 0;
            $scope.form.diskon = 0;
            $scope.form.cash = 0;
            $scope.form.hutang = $scope.form.grand_total;
//            console.log($scope.form);
        });

        Data.get(control_link + '/getPengajuanDetail', params).then(function (response) {
            $scope.pengajuanDetail = response.data;
            $scope.detPembelian = $scope.pengajuanDetail;
            angular.forEach($scope.detPembelian, function (val, key) {
                val.diskon_persen = 0;
            });
        });
//        console.log('form', $scope.form);
//        console.log('detPembelian', $scope.detPembelian);
    };

    /** CRUD - END **/
    Data.get('acc/m_lokasi/index').then(function (response) {
        $scope.listLokasi = response.data.list;
    });

    Data.get('m_kategori/index', {filter: {"inv_m_kategori.is_deleted": "0"}}).then(function (response) {
        $scope.listKategori = response.data.list;
    });

    Data.get('m_satuan/index', {filter: {"is_deleted": "0"}}).then(function (response) {
        $scope.listSatuan = response.data.list;
    });

    $scope.getAll = function () {
        Data.get('t_po_pembelian/getAll').then(function (response) {
            $scope.poPembelian = response.data;
        });
    }


    Data.get('site/getAkunPerTipe').then(function (response) {
        $scope.listAkunHarta = response.data.akunHarta;
        $scope.listAkunKewajiban = response.data.akunKewajiban;
        $scope.listAkunModal = response.data.akunModal;
        $scope.listAkunPendapatan = response.data.akunPendapatan;
        $scope.listAkunBeban = response.data.akunBeban;
    });

    $scope.getSupplier = function (nama) {
        if (nama.toString().length > 2) {
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

    $scope.getPelabuhan = function (nama) {
        if (nama.toString().length > 3) {
            var params = {
                nama: nama
            };
        } else {
            var params = {};
        }

        Data.get('/m_pelabuhan/getKontak', params).then(function (response) {
            $scope.listSupplier = response.data.list;
            $scope.listSupplier2 = response.data.list;
        });
    };

    $scope.getSupplier('');
    $scope.getPelabuhan('');

    $scope.getminmax = function (min, max, bc) {
        var bar = bc.length;

        if (bar >= min && bar <= max) {
//            console.log("data sesuai")
        } else {
            toaster.pop("error", "Terjadi Kesalahan", "Panjang barcode harus sesuai");
        }
    };

    $scope.getDetail = function (form) {
        Data.get(control_link + "/getDetail?id=" + form.id + '&acc_m_lokasi_id=' + form.acc_m_lokasi_id.id).then(function (response) {
            $scope.detPembelian = response.data.detail;
            if ($scope.detPembelian.length > 0) {
                $scope.total();
//                $scope.kurs();
            }
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

    // Detail Pembelian

    $scope.checkJenis = function () {
        if ($scope.form.jenis_pembelian == 'barang') {
            $scope.addDetail()
        } else {
            $scope.modalBarangJasa()
        }
    }

    $scope.checkJenis2 = function (val) {
        if ($scope.form.jenis_pembelian == 'barang') {
            $scope.getSupplier(val)
        } else {
            $scope.getPelabuhan(val)
        }
    }

    $scope.resetDetail = function () {
        $scope.detPembelian = [];
        if ($scope.form.jenis_pembelian == 'barang') {
            $scope.addDetail()
        } else {
        }
    }

    $scope.detPembelian = [{
        no: ""
    }];
    $scope.addDetail = function (newdet = {no: ''}) {
        var val = $scope.detPembelian.length;
        var newDet = newdet;
        $scope.detPembelian.push(newDet);
        $scope.total()
    };
    $scope.removeRow = function (paramindex) {
        var r = confirm('Apakah Anda ingin menghapus item ini?');
        if (r) {
            $scope.detPembelian.splice(paramindex, 1);
            $scope.total();
            $scope.prepareJurnal();
        }
    };
    // Detail Pembelian -  END

    //Copy Value
    $scope.copyValue = function (target, value, detail = false) {

        var r = confirm("Samakan Nilai ?");
        if (r) {
            if (detail) {
                detail.subtotal_edit = detail.subtotal;
            } else {
                target = value;
            }
            $scope.total();
        }
    }
    //Copy Value - END

    // Perhitungan Total Pesanan
    $scope.changeBarang = function (form, index, $select) {
        angular.forEach($scope.detPembelian, function (val, key) {

            if (form.type_barcode == "serial") {
                $scope.detPembelian[index].jumlah = 0;
            } else {
                $scope.detPembelian[index].jumlah = 0;
            }
            $scope.detPembelian[index].type = form.type;
            $scope.detPembelian[index].harga = form.harga_beli * 1;
            $scope.detPembelian[index].subtotal = form.harga_beli;
            $scope.detPembelian[index].grand_total = form.harga_beli;
            $scope.detPembelian[index].nama_satuan = form.nama_satuan;

            $scope.detPembelian[index].diskon = 0;
            $scope.detPembelian[index].diskon_persen = 0;
            $scope.form.grand_total = 0;

            angular.forEach($scope.detPembelian, function (value, key) {
                if (value.inv_m_barang_id !== undefined) {
                    value.subtotal = parseInt(value.harga) - parseInt(value.diskon);
                    $scope.form.grand_total += parseInt(value.subtotal);
                }
            });

            $scope.form.cash = 0;
            $scope.form.hutang = $scope.form.grand_total;
            $scope.form.biaya_lain = 0;
            $scope.form.potongan = 0;
            $scope.form.diskon = 0;

            $scope.prepareJurnal();
        });
        $scope.total();
    };

    $scope.total = function (jenis_diskon, edit = false, edit_total = false) {
        $scope.form.grand_total = 0;
        $scope.form.diskon = 0;

        angular.forEach($scope.detPembelian, function (value, key) {
            if (value.inv_m_barang_id !== undefined) {
                $scope.form.diskon += Math.round(parseFloat(value.diskon));

                value.kurs = (value.kurs != undefined) ? value.kurs : 15000;

                if (value.kurs != undefined) {
                    if (jenis_diskon == 'kurs_edit') {
                        value.harga = value.dollar * value.kurs / value.jumlah

                    } else if (jenis_diskon == 'kurs') {

                        if ($scope.activeIndex != undefined && $scope.activeIndex == key) {
                            value.harga = value.harga * value.kurs;
                        }

                    }
                }

                console.log("urutan", key);
                console.log("activeIndex", $scope.activeIndex);
                console.log(value);

                value.dollar = value.kurs != undefined ? value.harga * value.jumlah / value.kurs : 0;
                if ($scope.activeIndex != undefined && $scope.activeIndex == key) {
                    $scope.activeIndex = undefined;
                    console.log("activeIndex after", $scope.activeIndex);
                }

                value.subtotal = Math.round(parseFloat(value.jumlah) * parseFloat(value.harga));

                value.subtotal_edit = edit || (value.subtotal_edit != undefined && value.subtotal_edit != 0 && value.subtotal_edit != value.subtotal) ? value.subtotal_edit : (value.subtotal_edit != undefined && value.subtotal_edit != 0 && !$scope.is_create ? value.subtotal_edit : value.subtotal);
                if (jenis_diskon == 'kurs' || jenis_diskon == 'rupiah') {
                    value.subtotal_edit = value.subtotal;
                }

                value.selisih = value.subtotal_edit - value.subtotal;
                value.bg = value.selisih > 0 ? 'bg-warning' : (value.selisih < 0 ? 'bg-danger' : '')
                $scope.form.grand_total += parseFloat(value.subtotal_edit);

            } else {
                value.harga = 0;
                value.jumlah = 0;
            }
        });

        $scope.form.grand_total += $scope.form.biaya_lain != undefined ? parseInt($scope.form.biaya_lain) : 0;
        $scope.form.total = $scope.form.grand_total;
        $scope.form.total_edit = edit_total ? $scope.form.total_edit : $scope.form.total;
        $scope.form.selisih_total = $scope.form.total_edit - $scope.form.total;

        $scope.form.bg_total = $scope.form.selisih_total > 0 ? 'bg-warning' : ($scope.form.selisih_total < 0 ? 'bg-danger' : '')
        $scope.form.cash = $scope.form.cash != undefined ? $scope.form.cash : 0;

        $scope.form.grand_total = $scope.form.total_edit - ($scope.form.potongan != undefined ? parseInt($scope.form.potongan) : 0);
        $scope.form.grand_total = $scope.form.total_edit - parseInt($scope.form.diskon) - $scope.form.cash;
        $scope.form.ppnbm = 0;

        $scope.bayar();
        $scope.prepareJurnal();
    };

    $scope.bayar = function (edit = false) {
        // if ($scope.form.cash == 0) {
        //     $scope.is_kas = 0;
        //     $scope.form.m_akun_id = '';
        // } else {
        //     $scope.is_kas = 1;
        //     if ($scope.form.cash > $scope.form.grand_total) {
        //         toaster.pop("error", "Terjadi Kesalahan", "Terbayar tidak boleh melebihi total");
        //         $scope.form.cash = 0;
        //     }
        // }
        console.log('DPP : ', $scope.form.grand_total);
        console.log('PPN : ', $scope.form.form_ppn);
        $scope.form.form_ppn = $scope.form.is_ppn == 1 ? parseFloat($scope.form.jenis_ppn / 100 * $scope.form.grand_total).toFixed(2) : 0;
        $scope.form.ppn_edit = edit ? $scope.form.ppn_edit : ($scope.form.ppn_edit != undefined && $scope.form.ppn_edit != 0 && !$scope.is_create ? $scope.form.ppn_edit : $scope.form.form_ppn);
        console.log('PPN Edit : ', $scope.form.ppn_edit);
        $scope.form.selisih = $scope.form.ppn_edit - $scope.form.form_ppn;
        $scope.form.bg = $scope.form.selisih > 0 ? 'bg-warning' : ($scope.form.selisih < 0 ? 'bg-danger' : '')
//
        var ppn_edit = $scope.form.ppn_edit != undefined ? $scope.form.ppn_edit : 0;
        $scope.form.hutang = parseFloat($scope.form.grand_total) + parseFloat($scope.form.ongkos_kapal_rp) + parseFloat($scope.form.ppn_edit);
        $scope.prepareJurnal();
    };

    $scope.prepareJurnal = function () {
        return;
        $scope.totalDebit = 0;
        $scope.totalKredit = 0;

        var listJurnal = [];
        var subtotal = 0;
        var index = 0;
//        console.log($scope.detPembelian)
        angular.forEach($scope.detPembelian, function (val, key) {
            if (val.inv_m_barang_id.akun_pembelian_id.id != undefined) {
//                console.log(val.subtotal_edit)
//                console.log(listJurnal[val.inv_m_barang_id.akun_pembelian_id.id])

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
                    listJurnal[key_exists].debit += val.subtotal_edit;
                } else {
                    listJurnal[index] = {
                        debit: val.subtotal_edit,
                        kredit: 0,
                        type: 'debit',
                        akun: val.inv_m_barang_id.akun_pembelian_id,
                        keterangan: 'Pembelian'
                    };

                    index++;
                }

                subtotal += val.subtotal_edit;
                $scope.totalDebit += val.subtotal_edit;
                $scope.totalKredit += val.subtotal_edit;
            }
        })

        if ($scope.form.cash > 0 && $scope.form.acc_m_akun_id != undefined) {
            listJurnal[index] = {
                debit: 0,
                kredit: $scope.form.cash,
                type: 'kredit',
                akun: $scope.form.acc_m_akun_id,
                keterangan: 'Pembelian'
            };
            index++;
        }

        if ($scope.form.grand_total > 0) {
            listJurnal[index] = {
                debit: 0,
                kredit: $scope.form.hutang,
                type: 'kredit',
                akun: $scope.form.acc_m_kontak_id.acc_m_akun_id,
                keterangan: 'Pembelian'
            };
        }

        $scope.listJurnal = listJurnal;
    }

    $scope.resetPPN = function () {
        $scope.form.ppn_edit = $scope.form.form_ppn;
        $scope.form.selisih = 0;
        $scope.form.bg = '';
    }

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
                val: val,
                jenis: 'pembelian',
                is_pakai: 0
            };

            Data.get("m_barang/getBarang", dataa).then(function (response) {
                $scope.listBarang = response.data.list;
            });
        }
    };

    $scope.cariBarangAll = function () {
        var dataa = {
            acc_m_lokasi_id: $scope.form.acc_m_lokasi_id.id,
            jenis: 'pembelian',
            is_pakai: 0
        };

        Data.get("m_barang/getBarang", dataa).then(function (response) {
            $scope.listBarang = response.data.list;
        });
    };

    /* List Detail Pajak Pelabuhan */
    $scope.getFP = function () {
        var params = {
            'inv_pembelian.jenis_pembelian': 'jasa',
            'status_fp': 'draft',
        };

        var paramBulan = $scope.form.tanggal != undefined ? $scope.form.tanggal : undefined;
//        console.log(params)
        Data.get(control_link + "/index", {filter: params, bulan: paramBulan}).then(function (response) {
            $scope.getListFP = response.data.list;
//            angular.forEach($scope.getListFP, function (val, key) {
//                val.total = parseFloat(val.total) + parseFloat(val.total * val.jenis_ppn / 100)
//            })
//            console.log(response.data.list)
        });
    };

    $scope.getDetailPajak = function (form) {
        var params = {
            id: form.id
        }
        Data.get(control_link + "/getDetailPajak", params).then(function (response) {
            $scope.listDetailPajak = response.data.jasa1;
            $scope.listDetailPajakNonPPN = response.data.jasa2;
            angular.forEach($scope.listDetailPajak, function (val, key) {
//                val.faktur.total = val.faktur.total + (val.faktur.total * val.faktur.jenis_ppn / 100)
            })
            angular.forEach($scope.listDetailPajakNonPPN, function (val, key) {
                val.tanggal = new Date(val.tanggal)
            })
//            console.log($scope.listDetailPajak)

            $scope.totalPajak();
        });
    };

    $scope.getDetailBiaya = function (form) {
        var params = {
            id: form.id
        }
        Data.get(control_link + "/getDetailBiaya", params).then(function (response) {
            $scope.listBiayaTambahan = response.data;
            angular.forEach($scope.listBiayaTambahan, function (val, key) {
                val.tanggal_ntpn = new Date(val.tanggal_ntpn)
            })
//            console.log($scope.listBiayaTambahan)

//            $scope.totalPajak();
        });
    };

    $scope.initDetailPajak = function () {
        $scope.listDetailPajak = [{}];
        $scope.listDetailPajakNonPPN = [{
            tanggal: new Date,
        }];
        $scope.listBiayaTambahan = [{
            tanggal_ntpn: new Date,
        }];
    }

    $scope.addDetailPajak = function (arr = 'listDetailPajak') {
        var val = $scope.listDetailPajak.length;
//        var acc_m_kontak_id = {is_ppn : is_ppn};
//        var faktur = {acc_m_kontak_id : acc_m_kontak_id};
        var newDet = {
            tanggal: new Date,
        };
        $scope[arr].push(newDet);
    };

    $scope.removeRowPajak = function (paramindex, arr = 'listDetailPajak') {
        var konf = "Apakah Anda ingin menghapus item ini?"

        if (confirm(konf)) {
            $scope[arr].splice(paramindex, 1);
            $scope.totalPajak();
        }
    };

    $scope.addDetailBiaya = function (arr = 'listBiayaTambahan') {
        var val = $scope.listBiayaTambahan.length;
//        var acc_m_kontak_id = {is_ppn : is_ppn};
//        var faktur = {acc_m_kontak_id : acc_m_kontak_id};
        var newDet = {
            tanggal_ntpn: new Date(),
        };
        $scope[arr].push(newDet);
    };

    $scope.removeRowBiaya = function (paramindex, arr = 'listBiayaTambahan') {
        var konf = "Apakah Anda ingin menghapus item ini?"

        if (confirm(konf)) {
            $scope[arr].splice(paramindex, 1);
            $scope.totalBiaya();
        }
    };

    $scope.setDetPajak = function (det, index) {
        //periksa apakah faktur dah dimasukkan?
        var is_duplicate = false;
        angular.forEach($scope.listDetailPajak, function (val, key) {
            if ((val.inv_m_faktur_pajak_id == det) && (key != index)) {
                is_duplicate = true;
            }
        });

        if (is_duplicate == true) {
            $scope.listDetailPajak.splice(index, 1);
            $rootScope.alert("Peringatan", "No. Faktur Pajak sudah dimasukkan! Silakan pilih yang lain.", "error");
        } else {
            $scope.totalPajak();
        }
    };

    $scope.totalPajak = function () {
        $scope.totalPajakPelabuhan = 0;
        $scope.totalPajakPelabuhanNonPPN = 0;
        var totalPajakPelabuhanPPN = 0;
        var totalPajakPelabuhanNonPPN = 0;
//        console.log($scope.listDetailPajakNonPPN)
        angular.forEach($scope.listDetailPajak, function (val, key) {
            totalPajakPelabuhanPPN += val.faktur != undefined ? parseFloat(val.faktur.total) : 0;

        });
        angular.forEach($scope.listDetailPajakNonPPN, function (val, key) {
            totalPajakPelabuhanNonPPN += parseFloat(val.total);

        });

        $scope.totalPajakPelabuhan = totalPajakPelabuhanPPN;
        $scope.totalPajakPelabuhanNonPPN = totalPajakPelabuhanNonPPN;
        $scope.form.pelabuhan_ppn = totalPajakPelabuhanPPN;
        $scope.form.pelabuhan_non_ppn = totalPajakPelabuhanNonPPN;
    };

    /* List Detail Pajak Pelabuhan - END */

    $scope.getFakturPajak = function () {
        Data.get("m_faktur_penjualan/index", {
            filter: {
                pembelian_terpakai: 'tidak',
                jenis_faktur: 'pembelian'
            }
        }).then(function (response) {
            $scope.listFaktur = response.data.list;
        });
    };

    $scope.modalSupplier = function (type, detail = false) {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/t_pembelian/modalSupplier.html",
            controller: "supplierCtrl",
            size: "lg",
            backdrop: "static",
            keyboard: false,
            resolve: {
                form: {type: type},
            }
        });
        modalInstance.result.then(function (response) {
//            console.log(response)
            if (response.data == undefined) {
            } else {
                if (detail)
                    detail.acc_m_kontak_id = response.data;
                else
                    $scope.form.acc_m_kontak_id = response.data;
//                $scope.fakturPenjualan({id: response.data.form.inv_penjualan_id.id});
//                $scope.cancel();
            }
        });
    }

    $scope.modalFaktur = function (form = {}) {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/t_pembelian/modalFaktur.html",
            controller: "fakturCtrl",
            size: "md",
            backdrop: "static",
            keyboard: false,
            resolve: {
                form: form,
            }
        });
        modalInstance.result.then(function (response) {
//            console.log(response)
            if (response.data == undefined) {
            } else {
                $scope.form.inv_m_faktur_pajak_id = response.data;
//                $scope.fakturPenjualan({id: response.data.form.inv_penjualan_id.id});
//                $scope.cancel();
            }
        });
    }

    $scope.activeIndex = undefined;
    $scope.modalHarga = function (detail, tipe, indexData) {
        console.log("ngahahaha", detail, tipe, indexData);
        $scope.activeIndex = indexData;
        var form = detail;
        form.tipe = tipe;
        form.is_import = $scope.is_import;
        console.log('fak', form);
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/t_pembelian/modalHarga.html",
            controller: "hargaCtrl",
            size: "md",
            backdrop: "static",
            keyboard: false,
            resolve: {
                form: form,
            }
        });
        modalInstance.result.then(function (response) {
            if (response.data == undefined) {
            } else {
                if (tipe == 'Harga') {
                    detail.harga = response.data.harga;
                    detail.subtotal_edit = response.data.subtotal_edit
                    detail.jumlah = response.data.jumlah
                    detail.kurs = response.data.kurs

                    if ($scope.is_import) {
                        $scope.total('kurs');
                        console.log('import kurus');
                    } else {
                        $scope.total()
                    }

                } else {
                    detail.diskon = response.data.diskon;
                    $scope.total('rupiah')
                }
            }
        });
    }

    $scope.modalBarangJasa = function () {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/t_pembelian/modalBarangJasa.html",
            controller: "barangjasaCtrl",
            size: "md",
            backdrop: "static",
            keyboard: false,
            resolve: {
                form: {jenis_pembelian: $scope.form.jenis_pembelian},
            }
        });
        modalInstance.result.then(function (response) {
//            console.log(response)
            if (response.data == undefined) {
            } else {
                $scope.addDetail({
                    no: '',
                    inv_m_barang_id: response.data,
                    nama_satuan: response.data.nama_satuan,
                    harga: 0,
                    jumlah: 0,
                    diskon_persen: 0,
                    diskon: 0
                })
            }
        });
    }
});

app.controller("supplierCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {

//    $scope.is_view = true;
    $scope.form = form;
//    $scope.detPenjualan = [];
    $scope.form.is_ppn = 1;
    Data.get('acc/m_supplier/kode', {project: 'afu'}).then(function (response) {
        $scope.form.kode = response;
    });

    Data.get('acc/m_akun/akunHutang').then(function (response) {
        $scope.listAkun = response.data.list;
    });

    $scope.save = function (form) {
        Data.post('acc/m_supplier/save', form).then(function (result) {
//            console.log(result)
            if (result.status_code == 200) {
                $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
//                $scope.cancel();
                $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
                $uibModalInstance.close({
                    'data': result.data
                });
            } else {
                $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
            }
        });
    };

    $scope.close = function () {
        $uibModalInstance.close({
            'data': undefined
        });
    };
});

app.controller("fakturCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {

//    $scope.is_view = true;
    $scope.form = form;
    $scope.form.jenis_faktur = 'pembelian';

//    console.log(form)

    if (form.id != undefined) {
        var myarr = form.nomor.split("-");
        var myarr1 = myarr[0].split(".");
        var myarr2 = myarr[1].split(".");

        $scope.form.form1 = myarr1[0];
        $scope.form.form2 = myarr1[1];
        $scope.form.form3 = myarr2[0];
        $scope.form.form4 = myarr2[1];
    }


    $scope.setFaktur = function (param, max) {
        if ($scope.form['form' + param] != undefined) {
            if ($scope.form['form' + param].length == max) {
//                console.log("ok")
                setTimeout(function () {
                    $('#form' + (param + 1)).focus()
                }, 1)

            }
        }

    }

    $scope.save = function () {
        if ($scope.form.form1 != undefined && $scope.form.form2 != undefined && $scope.form.form3 != undefined && $scope.form.form4 != undefined) {
            var no_faktur = $scope.form.form1 + "." + $scope.form.form2 + "-" + $scope.form.form3 + "." + $scope.form.form4;
            var params = {
                form: $scope.form,
                detail: [{no_faktur: no_faktur}]
            }
            Data.post('m_faktur_penjualan/save', params).then(function (result) {
//                console.log(result)
                if (result.status_code == 200) {
                    $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
                    $uibModalInstance.close({
                        'data': result.data
                    });
                } else {
                    $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
                }
            });
        } else {
            $scope.close()
        }

    };

    $scope.close = function () {
        $uibModalInstance.close({
            'data': undefined
        });
    };
});

app.controller("hargaCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {
    $scope.form = form;

    console.log('in modal', $scope.form);

    if ($scope.form.tipe == 'Diskon') {
        $scope.form.total_diskon = $scope.form.diskon;

    } else if ($scope.form.tipe == 'Harga' && $scope.form.is_import == 1) {
        $scope.form.total_harga = parseFloat(($scope.form.subtotal_edit) / parseFloat($scope.form.kurs));

    } else {
        $scope.form.total_harga = $scope.form.subtotal_edit;
    }

    console.log('setelah itunggggg', $scope.form);

    $scope.save = function () {
        if ($scope.form.harga != undefined && $scope.form.jumlah != undefined) {
            var satuan = ($scope.form.tipe == 'Diskon' ? $scope.form.total_diskon : $scope.form.total_harga) / $scope.form.jumlah;
            if ($scope.form.tipe == 'Diskon') {
                $uibModalInstance.close({
                    'data': {'diskon': parseFloat(satuan)}
                });
            } else {
                $uibModalInstance.close({
                    'data': {
                        'harga': parseFloat(satuan),
                        'subtotal_edit': parseFloat($scope.form.total_harga),
                        'jumlah': $scope.form.jumlah,
                        'kurs': $scope.form.kurs
                    }
                });
            }
        } else {
            $scope.close()
        }

    };

    $scope.close = function () {
        $uibModalInstance.close({
            'data': undefined
        });
    };
});

app.controller("barangjasaCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {

//    $scope.is_view = true;
    $scope.form = {
        harga_pokok: 'fifo',
        type_barcode: 'non serial',
        is_pakai: 1,
        harga_jual: 0,
        harga_beli: 0,
        jenis_pembelian: form.jenis_pembelian
    };

    Data.get('m_kategori/index', {filter: {"inv_m_kategori.is_deleted": 0}}).then(function (response) {
        $scope.listKategori = response.data.list;
    });

    Data.get('m_satuan/index', {filter: {is_deleted: 0}}).then(function (response) {
        $scope.listSatuan = response.data.list;
    });

    $scope.setFaktur = function (param, max) {
        if ($scope.form['form' + param] != undefined) {
            if ($scope.form['form' + param].length == max) {
//                console.log("ok")
                setTimeout(function () {
                    $('#form' + (param + 1)).focus()
                }, 1)

            }
        }

    }

    $scope.save = function (form) {
        var params = {
            form: form
        };
        params.form.is_popup = 1;
        Data.post('m_barang/save', params).then(function (result) {
//            console.log(result)
            if (result.status_code == 200) {
                $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
//                console.log(form)
                result.data.nama_satuan = form.inv_m_satuan_id != undefined ? form.inv_m_satuan_id.nama : '';
                $uibModalInstance.close({
                    'data': result.data
                });
            } else {
                $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
            }
        });

    };

    $scope.close = function () {
        $uibModalInstance.close({
            'data': undefined
        });
    };
});
