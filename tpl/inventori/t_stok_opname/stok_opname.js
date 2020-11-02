app.controller('stokOpnameCtrl', function ($scope, Data, $rootScope, $stateParams, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_stok_opname";
    var master = 'Transaksi Penyesuaian Persediaan';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.master = master;

    Data.get('acc/m_akun_peta/getPemetaanAkun', {type: "Kelebihan Barang"}).then(function (data) {
        $scope.akunLebih = data.data.list[0];
    });
    Data.get('acc/m_akun_peta/getPemetaanAkun', {type: "Kerugian Barang"}).then(function (data) {
        $scope.akunRugi = data.data.list[0];
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

        if (param['filter'] == undefined) {
            param['filter'] = {};
        }
        param['filter']['is_import'] = $stateParams.is_import;

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
        $scope.listJurnal = [];
        $scope.form.tanggal = new Date();
        // $scope.getAll();
        $scope.detBarang = [];
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
        $scope.getJurnal(form);
        $scope.form.tanggal = new Date(form.tanggal);
    };
    /** view */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Lihat Data : " + form.kode;
        $scope.form = form;
        $scope.getDetail(form);
        $scope.getJurnal(form);
        $scope.form.tanggal = new Date(form.tanggal);
    };

    /** save action */
    $scope.save = function (form, status) {
        form.type = 'save';
        form.status = status;
        var params = {
            form: form,
            detail: $scope.detBarang,
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
        console.log(form)

        Data.post(control_link + '/unpost', form).then(function (response) {
            if (response.status_code == 200) {
                $rootScope.alert("Tersimpan", "Data Berhasil Di Simpan", "success");
                $scope.cancel();
            } else {
                $rootScope.alert("Terjadi kesalahan", response.errors, "error");
            }
        })
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
            $scope.form.cash = $scope.form.grand_total;
            $scope.form.hutang = 0;
            console.log($scope.form);
        });

        Data.get(control_link + '/getPengajuanDetail', params).then(function (response) {
            $scope.pengajuanDetail = response.data;
            $scope.detPembelian = $scope.pengajuanDetail;
            angular.forEach($scope.detPembelian, function (val, key) {
                val.diskon_persen = 0;
            });
        });
        console.log('form', $scope.form);
        console.log('detPembelian', $scope.detPembelian);
    };

    /** CRUD - END **/
    Data.get('acc/m_lokasi/index', {filter: {
            "acc_m_lokasi.is_deleted": "0"
        }}).then(function (response) {
        $scope.listLokasi = response.data.list;
    });

    Data.get('m_kategori/index', {filter: {
            "inv_m_kategori.is_deleted": "0"
        }}).then(function (response) {
        $scope.listKategori = response.data.list;
    });

    Data.get('site/getAkunPerTipe').then(function (response) {
        $scope.listAkunHarta = response.data.akunHarta;
        $scope.listAkunKewajiban = response.data.akunKewajiban;
        $scope.listAkunModal = response.data.akunModal;
        $scope.listAkunPendapatan = response.data.akunPendapatan;
        $scope.listAkunBeban = response.data.akunBeban;
    });

    $scope.getDetail = function (form) {
        Data.get(control_link + "/getDetail?id=" + form.id).then(function (response) {
            $scope.detBarang = response.data.detail;
            $scope.total();
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
    $scope.detPembelian = [{
            no: ""
        }];
//    $scope.addDetail = function () {
//        var val = $scope.detPembelian.length;
//        var newDet = {
//            no: ""
//        };
//        $scope.detPembelian.push(newDet);
//    };
    $scope.removeRow = function (paramindex) {
      var conf = `Apakah Anda ingin menghapus item ini?`;
      if( confirm(conf) ){
        $scope.detBarang.splice(paramindex, 1);
        $scope.total();
      }
    };
    // Detail Pembelian -  END

    $scope.cariBarang = function (val, index, $select) {
        if (val.length > 2) {

            var dataa = {
                tanggal           : $scope.form.tanggal,
                acc_m_lokasi_id   : $scope.form.acc_m_lokasi_id.id,
                val               : val,
                is_dijual         : 1
            };

            Data.get("m_barang/getBarang", dataa).then(function (response) {
                $scope.listBarang = response.data.list;
            });
        }
    };

    $scope.getBarang = function () {
        var cabang_id = $scope.form.acc_m_lokasi_id.id;
        var kategori_id = $scope.form.inv_m_kategori_id.id;

        if (cabang_id != undefined && kategori_id != undefined) {
            Data.get(control_link + "/barang/" + cabang_id + "/" + kategori_id + "/0").then(function (data) {
                $scope.detBarang = data.data.data;
                var array_of_numbers = data.data.data;
                var hasil = 0;
                array_of_numbers.forEach(function (current_value, index, initial_array) {
                    hasil += current_value.nilai_penyesuaian;
                });
                $scope.form.total = hasil;
            });
        } else {
            $scope.detBarang = {};
        }
    };
    $scope.detBarang = [];

    $scope.addDetail = function (item) {
      var params = {
        idcabang    : $scope.form.acc_m_lokasi_id.id,
        idkategori  : ($scope.form.inv_m_kategori_id != undefined ? $scope.form.inv_m_kategori_id.id : 0),
        idbarang    : item.id,
        tanggal     : $scope.form.tanggal,
      };
        Data.post(
          // control_link + "/barang/"
          // + $scope.form.acc_m_lokasi_id.id
          // + "/" + ($scope.form.inv_m_kategori_id != undefined ? $scope.form.inv_m_kategori_id.id : 0)
          // + "/" + item.id + '/' + $scope.form.tanggal
          control_link + "/barang/", params
        ).then(function (data) {
            item = data.data.data[0];

            if ($scope.detBarang.length > 0) {
                console.log('1')
                var ada = false;
                angular.forEach($scope.detBarang, function (val, key) {
                    if (val.kode == item.kode) {
                        ada = true;
                    } else {
                        ada = false;
                    }
                });
                if (ada === false) {
                    $scope.detBarang.push(item);
                }
            } else {
                console.log('2')
                $scope.detBarang = [];
                $scope.detBarang.push(item);
            }
            $scope.form.Barang = undefined;
        });

    };

    $scope.total = function () {
        var total = 0;
        angular.forEach($scope.detBarang, function (val, key) {
            console.log(val.stock_real)
            var nilai = parseFloat(val.stock_real) - parseFloat(val.stock_app);
            val.selisih = nilai;
            val.nilai_penyesuaian = parseFloat(nilai * parseFloat(val.hpp));
            total += val.nilai_penyesuaian;
            if (val.selisih < 0) {
                $scope.detBarang[key]["required"] = true;
            } else {
                $scope.detBarang[key]["required"] = false;
            }
        });
        $scope.form.total = total;
    };

    $scope.prepareJurnal = function () {

        $scope.totalDebit = 0;
        $scope.totalKredit = 0;

        var listJurnal = [];
        var jurnalMinus = {
            akun_hpp: [],
            akun_persediaan: [],
            akun_opname: [],
            akun_hpp2: [],
        };
        var jurnalPlus = {
            akun_persediaan: [],
            akun_hpp: [],
            akun_hpp2: [],
            akun_opname: [],
        };
        var subtotal = 0;
        var subtotal_minus = 0;
        var subtotal_plus = 0;
        var index = 0;

        console.log($scope.detBarang)

        //SO MINUS
        angular.forEach($scope.detBarang, function (val, key) {
            if (val.akun_persediaan_id.id != undefined && val.akun_hpp_id.id != undefined) {
                //SO MINUS
                if (val.nilai_penyesuaian < 0) {
                    //AKUN HPP
                    var index = 0;
                    var exists = false;
                    var key_exists = 0;
                    if (jurnalMinus['akun_hpp'] != undefined) {
                        angular.forEach(jurnalMinus['akun_hpp'], function (vals, keys) {
                            if (val.akun_hpp_id.id == vals.akun.id) {
                                exists = true;
                                key_exists = keys;
                            }
                        });
                    }


                    if (exists) {
                        jurnalMinus['akun_hpp'][key_exists].debit += val.nilai_penyesuaian * -1;
                    } else {
                        jurnalMinus['akun_hpp'][index] = {
                            debit: val.nilai_penyesuaian * -1,
                            kredit: 0,
                            type: 'debit',
                            akun: val.akun_hpp_id,
                            keterangan: 'Penyesuaian Persediaan'
                        };

                        index++;
                    }
                    subtotal += val.nilai_penyesuaian * -1;
                    //AKUN HPP - END

                    //AKUN PERSEDIAAN
                    var index = 0;
                    var exists = false;
                    var key_exists = 0;
                    if (jurnalMinus['akun_persediaan'] != undefined) {
                        angular.forEach(jurnalMinus['akun_persediaan'], function (vals, keys) {
                            if (val.akun_persediaan_id.id == vals.akun.id) {
                                exists = true;
                                key_exists = keys;
                            }
                        });
                    }


                    if (exists) {
                        jurnalMinus['akun_persediaan'][key_exists].kredit += val.nilai_penyesuaian * -1;
                    } else {
                        jurnalMinus['akun_persediaan'][index] = {
                            debit: 0,
                            kredit: val.nilai_penyesuaian * -1,
                            type: 'kredit',
                            akun: val.akun_persediaan_id,
                            keterangan: 'Penyesuaian Persediaan'
                        };

                        index++;
                    }
                    subtotal += val.nilai_penyesuaian * -1;
                    //AKUN PERSEDIAAN - END

                    subtotal_minus += val.nilai_penyesuaian * -1;
                }
                //SO MINUS - END

                //SO PLUS
                else if (val.nilai_penyesuaian > 0) {
                    //AKUN PERSEDIAAN
                    var index = 0;
                    var exists = false;
                    var key_exists = 0;
                    if (jurnalPlus['akun_persediaan'] != undefined) {
                        angular.forEach(jurnalPlus['akun_persediaan'], function (vals, keys) {
                            if (val.akun_persediaan_id.id == vals.akun.id) {
                                exists = true;
                                key_exists = keys;
                            }
                        });
                    }


                    if (exists) {
                        jurnalPlus['akun_persediaan'][key_exists].debit += val.nilai_penyesuaian;
                    } else {
                        jurnalPlus['akun_persediaan'][index] = {
                            debit: val.nilai_penyesuaian,
                            kredit: 0,
                            type: 'debit',
                            akun: val.akun_persediaan_id,
                            keterangan: 'Penyesuaian Persediaan'
                        };

                        index++;
                    }
                    subtotal += val.nilai_penyesuaian;
                    //AKUN PERSEDIAAN - END

                    //AKUN HPP
                    var index = 0;
                    var exists = false;
                    var key_exists = 0;
                    if (jurnalPlus['akun_hpp'] != undefined) {
                        angular.forEach(jurnalPlus['akun_hpp'], function (vals, keys) {
                            if (val.akun_hpp_id.id == vals.akun.id) {
                                exists = true;
                                key_exists = keys;
                            }
                        });
                    }


                    if (exists) {
                        jurnalPlus['akun_hpp'][key_exists].kredit += val.nilai_penyesuaian;
                    } else {
                        jurnalPlus['akun_hpp'][index] = {
                            debit: 0,
                            kredit: val.nilai_penyesuaian,
                            type: 'kredit',
                            akun: val.akun_hpp_id,
                            keterangan: 'Penyesuaian Persediaan'
                        };

                        index++;
                    }
                    subtotal += val.nilai_penyesuaian;
                    //AKUN HPP - END

                    subtotal_plus += val.nilai_penyesuaian;
                }
                //SO PLUS - END
            }
        });

        console.log(jurnalPlus)

        if (subtotal_minus > 0) {
            jurnalMinus.akun_opname[0] = {
                debit: subtotal_minus,
                kredit: 0,
                type: 'debit',
                akun: $scope.akunLebih,
                keterangan: 'Penyesuaian Persediaan'
            };
        }

        angular.forEach(jurnalMinus.akun_hpp, function (val, key) {
            var add = {
                debit: 0,
                kredit: val.debit,
                type: 'kredit',
                akun: val.akun,
                keterangan: 'Penyesuaian Persediaan'
            }
            jurnalMinus.akun_hpp2[key] = add;
        });

        if (subtotal_plus > 0) {
            jurnalPlus.akun_opname[0] = {
                debit: 0,
                kredit: subtotal_plus,
                type: 'kredit',
                akun: $scope.akunRugi,
                keterangan: 'Penyesuaian Persediaan'
            };
        }


        angular.forEach(jurnalPlus.akun_hpp, function (val, key) {
            var add = {
                debit: val.kredit,
                kredit: 0,
                type: 'debit',
                akun: val.akun,
                keterangan: 'Penyesuaian Persediaan'
            }
            jurnalPlus.akun_hpp2[key] = add;
        });

//        console.log(jurnalMinus)
//        console.log(jurnalPlus)

        angular.forEach(jurnalMinus, function (val, key) {
            angular.forEach(val, function (vals, keys) {
                listJurnal.push(vals);
            });
        });
        angular.forEach(jurnalPlus, function (val, key) {
            angular.forEach(val, function (vals, keys) {
                listJurnal.push(vals);
            });
        });

//        listJurnal.push(jurnalMinus.akun_hpp, jurnalMinus.akun_persediaan, jurnalPlus.akun_persediaan, jurnalPlus.akun_hpp)

//        console.log(listJurnal)
        $scope.listJurnal = listJurnal;
        $scope.totalDebit = subtotal;
        $scope.totalKredit = subtotal;
    }
});
