app.controller('returPenjualanCtrl', function ($scope, Data, $rootScope, $stateParams, $window, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_retur_penjualan";
    var master = 'Transaksi Retur Penjualan';
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
            console.log($scope.displayed);
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
        $scope.generateKode();

        // Data.get(control_link + '/getAll').then(function (response) {
        //     $scope.listPenjualan = response.data;
        // });
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
        // $scope.prepareJurnal();
        // $scope.form.tanggal         = new Date(form.tanggal * 1000);
        $scope.form.tanggal_retur   = new Date(form.tanggal_retur);
        $scope.form.tanggal_nota     = new Date(form.tanggal_nota);
        $scope.form.tanggal_penjualan = new Date(form.tanggal_penjualan);
    };
    /** view */
    $scope.view = function (form) {
        console.log(form);
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Lihat Data : " + form.kode_retur;
        $scope.form = form;
        $scope.getDetail(form);
        // $scope.form.tanggal           = new Date(form.tanggal * 1000);
        $scope.form.tanggal_retur     = new Date(form.tanggal_retur);
        $scope.form.tanggal_penjualan = new Date(form.tanggal_penjualan);
        $scope.form.tanggal_nota      = new Date(form.tanggal_nota);
    };

    /** save action */
    $scope.save = function (type, form) {
        form.status = type;
        console.log(form)
        var params = {
            form: form,
            detail: $scope.detPenjualan,
            listJurnal: $scope.listJurnal
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

    $scope.unpost = function (form) {
        form.status = 'unpost';

        var params = {
            form: form
        }

        Swal.fire({
            title: "Peringatan ! ",
            text: "Apakah Anda Yakin Ingin Mengunpost Data Ini",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Iya, Unpost",
            cancelButtonText: "Tidak",
        }).then((result) => {
            if (result.value) {
                Data.post(control_link + '/unpost', params).then(function (result) {
                    if (result.status_code == 200) {
                        $rootScope.alert("Berhasil", "Data berhasil diunpost", "success");
                        $scope.cancel();
                    } else {
                        $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
                    }
                });
            }
        });
    }

    $scope.getDetail = function (form) {
        Data.get(control_link + "/getDetail?id=" + form.id + '&acc_m_lokasi_id=1').then(function (response) {
            $scope.detPenjualan = response.data.detail;
            // $scope.prepareJurnal();
        });
    };

    $scope.getJurnal = function (form) {
        Data.get(control_link + "/getJurnal?id=" + form.id + '&acc_m_lokasi_id=1').then(function (response) {
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
            $scope.detPenjualan = [{no: ""}];
            $scope.listJurnal = [];
            $scope.totalDebit = undefined;
            $scope.totalKredit = undefined;
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

    $scope.setFaktur = function (param, max) {
        if ($scope.form['form' + param] != undefined) {
            if ($scope.form['form' + param].length == max) {
                setTimeout(function () {
                    $('#form' + (param + 1)).focus()
                }, 1)
            }
        }
    }

    $scope.fillAlamat = function () {
        if ($scope.form.is_same && $scope.form.acc_m_kontak_id != undefined) {
            $scope.form.alamat_pengiriman = $scope.form.acc_m_kontak_id.alamat;
        }

        console.log($scope.form.acc_m_kontak_id)
        console.log(typeof $scope.form.acc_m_kontak_id.npwp)
        console.log($scope.form.no_invoice)
        if ($scope.form.acc_m_kontak_id != undefined && $scope.form.acc_m_kontak_id.is_ppn == 1) {
            $scope.form.is_ppn = 1;
            console.log('ok')
        } else {
            $scope.form.no_invoice = '';
            $scope.form.is_ppn = 0;
            console.log('ok2')
        }
    }

    $scope.generateKode = function () {
        Data.get("t_retur_penjualan/getKode").then(function (response) {
            $scope.form.kode_retur = response.data;
        })
    }

    $scope.cariBarang = function (val, index, $select, is_dijual) {
        if (val.length > 2) {
            var dataa = {
                acc_m_lokasi_id: 1,
                val: val,
                is_dijual: is_dijual
            };

            Data.get("m_barang/getBarang", dataa).then(function (response) {
                $scope.listBarang = response.data.list;
            });
        }
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


    // $scope.cariBarang = function (val, index, $select, is_dijual) {
    //     if (val.length > 2) {

    //         var dataa = {
    //             acc_m_lokasi_id: $scope.form.acc_m_lokasi_id.id,
    //             val: val,
    //             is_dijual: is_dijual
    //         };

    //         Data.get("m_barang/getBarang", dataa).then(function (response) {
    //             $scope.listBarang = response.data.list;

    //             if ($scope.listBarang.length == 1) {

    //                 $scope.detPenjualan[index].inv_m_barang_id = $scope.listBarang[0];
    //                 $scope.changeBarang($scope.detPenjualan[index].inv_m_barang_id, index);

    //                 var satuan = $scope.listBarang[0].nama_satuan != null ? $scope.listBarang[0].nama_satuan : "";
    //                 $select.search = $scope.listBarang[0].kode + " - " + $scope.listBarang[0].nama + satuan + " (" + $scope.listBarang[0].stock + ")";
    //                 focusInput("jumlah");
    //                 $scope.nama_satuan = $scope.listBarang[0].nama_satuan;
    //             }
    //         });
    //     }
    // };

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
            console.log('batu ok');
            if (dari != "ongkos_kirim") {
                console.log(';dan cow');
                if (form.type == "jasa") {
                    $scope.detPenjualan[index].jumlah = 1;
                } else if (form.type == "barang" && form.type_barcode != "serial") {
                    $scope.detPenjualan[index].jumlah = 1;
                } else {
                    $scope.detPenjualan[index].jumlah = 0;
                }

                $scope.detPenjualan[index].type = form.type;
                $scope.detPenjualan[index].harga_pokok = form.harga_pokok;
                $scope.detPenjualan[index].stok = form.stok;
                // $scope.detPenjualan[index].harga = form.harga_jual;
                $scope.detPenjualan[index].subtotal = form.harga_jual;
                $scope.detPenjualan[index].grand_total = form.harga_jual;
                $scope.detPenjualan[index].deskripsi = form.nama;
                /*AKUN KONEK ACC*/
                $scope.detPenjualan[index].akun_persediaan_brg_id = form.akun_persediaan_brg_id;
                $scope.detPenjualan[index].akun_penjualan_id = form.akun_penjualan_id;
                $scope.detPenjualan[index].akun_hpp_id = form.akun_hpp_id;
                /*AKUN KONEK ACC*/
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
            $scope.form.piutang = 0;
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
        $scope.form.sub_total = parseInt($scope.form.ongkos_kirim) + parseInt(total);
        $scope.form.ppn = 10 / 100 * $scope.form.sub_total;
        $scope.form.grand_total = $scope.form.sub_total + $scope.form.ppn;
        $scope.form.piutang = parseInt($scope.form.grand_total) - parseInt($scope.form.cash);

        console.log($scope.form)
    };
    $scope.total = function () {
        $scope.form.grand_total = 0;
        $scope.form.sub_total = 0;
        angular.forEach($scope.detPenjualan, function (value, key) {
            // if (parseInt(value.jumlah_retur) > parseInt(value.jumlah)) {

            //     // alert('Jumlah Barang Retur Melebihi Barang');
            //     Swal.fire({
            //         type: "error",
            //         title: "Peringatan!",
            //         text: "Jumlah Barang Retur Melebihi Jumlah Barang",
            //         timeout: 1000
            //     });
            //     value.jumlah_retur = 0;
            // }
            value.jumlah_retur = parseInt(value.jumlah_retur);
            value.subtotal = parseInt(value.jumlah_retur) * parseInt(value.harga_retur);
            $scope.form.sub_total += parseInt(value.subtotal);
            console.log($scope.form.sub_total);
        });
        $scope.form.piutang = 0;
        $scope.form.ppn = 10 / 100 * $scope.form.sub_total;
        $scope.form.grand_total = $scope.form.sub_total + $scope.form.ppn;
        // $scope.form.piutang = parseInt($scope.form.grand_total) - parseInt($scope.form.cash);
        // $scope.kalkulasi();
        // $scope.prepareJurnal();
        $scope.total_lama = $scope.form.grand_total;
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
        // $scope.prepareJurnal();
    };

    // $scope.prepareJurnal = function () {
    //     $scope.totalDebit = 0;
    //     $scope.totalKredit = 0;

    //     var listJurnal = [];
    //     var subtotal = 0;
    //     var index = 0;
    //     angular.forEach($scope.detPenjualan, function (val, key) {
    //         if (val.inv_m_barang_id.akun_penjualan_id.id != undefined && val.jumlah_retur > 0) {
    //             // console.log(val.subtotal_edit)

    //             var exists = false;
    //             var key_exists = 0;
    //             angular.forEach(listJurnal, function (vals, keys) {
    //                 if (val.inv_m_barang_id.akun_penjualan_id.id == vals.akun.id) {
    //                     exists = true;
    //                     key_exists = keys;
    //                 }
    //             });

    //             //debit
    //             if (exists) {
    //                 listJurnal[key_exists].debit += val.subtotal;
    //             } else {
    //                 listJurnal[index] = {
    //                     debit: val.subtotal,
    //                     kredit: 0,
    //                     type: 'kredit',
    //                     akun: val.inv_m_barang_id.akun_penjualan_id,
    //                     keterangan: 'Retur Penjualan'
    //                 };

    //                 index++;
    //             }

    //             subtotal += val.subtotal;
    //             $scope.totalDebit += val.subtotal;
    //             $scope.totalKredit += val.subtotal;
    //         }
    //     })

    //     listJurnal[index] = {
    //         debit: 0,
    //         kredit: subtotal,
    //         type: 'kredit',
    //         akun: $scope.form.m_akun_id,
    //         keterangan: 'Retur Penjualan'
    //     };

    //     $scope.listJurnal = listJurnal;
    // }

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

    $scope.notaRetur = function (form) {
//        console.log(form)

        var param = {
            id: form.id,
            print: 1
        };

        Data.get('site/base_url').then(function (response) {
            window.open(response.data.base_url + "api/t_retur_penjualan/notaRetur?" + $.param(param), "_blank");
        });
    }

    if ($stateParams.form != null) {
        var tableStateRef;
        $scope.create();
        $scope.getPenjualan($stateParams.form);

        // Data.get(control_link + "/getPenjualan", $stateParams.form).then(function (response) {
        //     $scope.detPenjualan         = response.data.detail;
        //     $scope.form                 = response.data.form;
        //     $scope.form.rusak           = 0;
        //     $scope.form.tanggal         = new Date($scope.form.tanggal);
        //     $scope.form.tanggal_retur   = new Date();
        //     $scope.total();
        // });
    }

    $scope.modalRetur = function (form) {
//        console.log($scope.detRetur)
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/t_retur_penjualan/modal.html",
            controller: "historiRetur",
            size: "lg",
            backdrop: "static",
            keyboard: false,
            resolve: {
                form: Object.assign({}, form),
            }
        });
        modalInstance.result.then(function (response) {
            console.log(response)
            if (response.data == undefined) {
            } else {
            }
        });
    }

    $scope.modalHarga = function (detail) {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/t_retur_penjualan/modalHarga.html",
            controller: "hargaCtrl",
            size: "lg",
            backdrop: "static",
            keyboard: false,
            resolve: {
                form: detail,
            }
        });
        modalInstance.result.then(function (response) {
            console.log(response)
            if (response.data == undefined) {
            } else {
                detail.harga_retur = parseInt(response.data);

                $scope.total('');
            }
        });
    }
});

app.controller("historiRetur", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {

    console.log(form)
    $scope.is_view = true;
    $scope.detRetur = [];
    $scope.detRetur = form;

    $scope.close = function () {
        $uibModalInstance.close({
            'data': undefined
        });
    };
});

app.controller("hargaCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {

    console.log(form)
    $scope.form = form;

    $scope.save = function (form) {
        var harga = form.harga_retur;
        if (form.tipe == 'feet') {
            harga = (form.harga_feet / form.ft) * form.panjang;
        } else if (form.tipe == 'potongan') {
            harga = form.harga_potongan - ((form.potongan1 / 100) * form.harga_potongan);
            if (form.potongan2 != undefined) {
                harga = harga - ((form.potongan2 / 100) * harga);
            }
        } else {
            harga = form.harga_khusus;
        }
        console.log(harga)
        harga = harga / 1.1;
        harga = Math.round(harga)
        console.log(harga)
        $scope.close(harga)
    }

    $scope.close = function (harga) {
        $uibModalInstance.close({
            data: harga
        });
    };
});
