app.controller('penjualanCtrl', function ($scope, Data, $rootScope, $state, $stateParams, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_penjualan";
    var master = 'Transaksi Penjualan';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.master = master;
    $scope.data = {checklist: false, is_check: false};
    $scope.filter = {};

    // Untuk filter tabset
    $scope.is_draft = 1;
    $scope.totalDpp = 0;

    $scope.sinkronInvoice = function () {
        Data.post(control_link + "/sinkronInvoice").then(function (response) {
            console.log(response)
            $scope.cancel()
        });
    }

    $scope.getFakturPajak = function () {
        Data.get("m_faktur_penjualan/index", {filter: {'inv_penjualan.id': 0, 'jenis_faktur': 'penjualan'}}).then(function (response) {
            $scope.listFaktur = response.data.list;
        });
    }

    $scope.getKode = function (lokasi, type = null) {
        var params = {
            lokasi: lokasi
        }
        Data.post(control_link + "/generatePenjualan", params).then(function (response) {
            if (type == 'invoice' || type == null) {
                $scope.form.no_invoice = response.data.invoice;
            }

            if (type == 'no_surat_jalan' || type == null) {
                $scope.form.no_surat_jalan = response.data.surat_jalan;
            }
        });
    }


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
        if ($scope.filter != undefined) {
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
        /** filter tabset */
        if (param['filter'] == undefined) {
            param['filter'] = {};
        }
        // param['filter']['inv_penjualan.is_draft'] = $scope.is_draft;
        Data.get(control_link + '/index', param).then(function (response) {
            $scope.displayed = response.data.list;
            tableState.pagination.numberOfPages = Math.ceil(response.data.totalItems / limit);
            $scope.totalDpp = response.data.totalDpp;
        });
        $scope.isLoading = false;
    };

    $scope.filterStatus = function (status) {
        $scope.is_draft = status;
        $scope.callServer(tableStateRef);
    };

    $scope.fillAlamat = function () {
        if ($scope.form.is_same && $scope.form.acc_m_kontak_id != undefined) {
            $scope.form.alamat_pengiriman = $scope.form.acc_m_kontak_id.alamat;
        }

        console.log($scope.form.acc_m_kontak_id)
        console.log(typeof $scope.form.acc_m_kontak_id.npwp)
        console.log($scope.form.no_invoice)
        if ($scope.form.acc_m_kontak_id != undefined && $scope.form.acc_m_kontak_id.is_ppn == 1) {
//            if($scope.is_create)
            if ($scope.form.no_invoice == '' || $scope.is_create)
                $scope.getKode($scope.form.acc_m_lokasi_id);
            $scope.form.is_ppn = 1;
            console.log('ok')
        } else {
            $scope.form.no_invoice = '';
            $scope.form.is_ppn = 0;
            console.log('ok2')
        }
    }
    $scope.fillPPN = function () {
        if ($scope.form.is_ppn) {
            $scope.form.persen_ppn = 10;
        } else {
            $scope.form.persen_ppn = 0;
        }
        $scope.total();
    }

    $scope.fillChecklist = function (type = '') {
        if (type != 'table') {
            if ($scope.data.checklist) {
                angular.forEach($scope.displayed, function (val, key) {
                    val.checklist = true;
                    $scope.data.is_check = true;
                })
            } else if (!$scope.data.checklist) {
                angular.forEach($scope.displayed, function (val, key) {
                    val.checklist = false;
                    $scope.data.is_check = false;
                })
            }
        } else {
            $scope.data.is_check = false;
            angular.forEach($scope.displayed, function (val, key) {
                if (val.checklist) {
                    $scope.data.is_check = true;
                }
            })
    }
    }

    /** create */
    $scope.create = function () {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_create = true;
        $scope.is_disable = false;
        $scope.formtitle = master + " | Form Tambah Data";
        $scope.form = {};
        $scope.form.is_ppn = undefined;
        $scope.form.persen_ppn = 10;
        $scope.form.is_same = true;
        $scope.form.tanggal = new Date();
        $scope.form.is_koreksi = 0;
//        $scope.form.is_draft == 1;

        $scope.listJurnal = [];

        $scope.getFakturPajak();

        console.log($scope.form)

        $scope.detPenjualan = [{
                no: ""
            }];

        Data.get('t_po_penjualan/getAll').then(function (response) {
            $scope.listPengajuan = response.data;
        });

        Data.get('t_penjualan/getLastData').then(function (response) {
            console.log(response)
            $scope.form.acc_m_lokasi_id = response.data.depo;
            $scope.form.tanggal = new Date(response.data.tanggal);
            if ($scope.form.acc_m_lokasi_id != undefined) {
                $scope.getKode($scope.form.acc_m_lokasi_id)
            }
        });
    };
    /** update */
    $scope.update = function (form, koreksi) {
        $scope.is_edit = true;
        $scope.is_create = false;
        $scope.is_view = false;
        $scope.is_update = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Edit Data : " + form.kode;
        $scope.form = form;
        $scope.form.is_koreksi = koreksi;
        $scope.form.tanggal = new Date(form.tanggal2);
        $scope.form.jatuh_tempo = new Date(form.jatuh_tempo * 1000);
        console.log("mode update");
        $scope.getDetail(form);
//        $scope.getJurnal(form);


//        $scope.getKode(form.acc_m_lokasi_id)

        if ($scope.form.acc_m_kontak_id.alamat == $scope.form.alamat_pengiriman) {
            $scope.form.is_same = true;
        }
    };
    /** view */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Lihat Data : " + form.kode;
        $scope.form = form;
        $scope.form.tanggal = new Date(form.tanggal2);
        $scope.form.jatuh_tempo = new Date(form.jatuh_tempo * 1000);
        console.log("mode view");
        // $scope.form.persen_ppn = 0;
        console.log("$scope.form.is_ppn", $scope.form.is_ppn);
        if ($scope.form.is_ppn == 1) {
          $scope.form.persen_ppn = 10;
        }
        $scope.getDetail(form);

        // if ($scope.form.acc_m_kontak_id.alamat == $scope.form.alamat_pengiriman) {
        //   $scope.form.is_same = true;
        // }
        // $scope.getJurnal(form);
        // $scope.form.grand_total = $scope.form.total + $scope.form.ppn;


//        $scope.total();
    };

    $scope.filterBulan = function () {
        $scope.callServer(tableStateRef);
    }

    $scope.reset_bulan = function () {
        $scope.filter = undefined;
        $scope.callServer(tableStateRef);
    }

    /** save action */
    $scope.save = function (form, type, sisipan = 0) {
      if(sisipan){
        var konf = `Apakah Anda ingin menyimpan penjualan ini sebagai Sisipan?` +
                    `\n No. Invoice dan No. Faktur Pajak akan otomatis diisi oleh sistem.`;

        if(!confirm( konf )){
          return;
        }
      }

        form.type = type;
        form.sisipan = sisipan;
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

    $scope.koreksiPenjualan = function (form, type) {
        form.type = type;
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
            if (form.is_koreksi == 0) {
                angular.forEach($scope.detPenjualan, function (val, key) {
                    val.koreksi = undefined;
                    val.koreksi_harga = undefined;
                });
            }
            $scope.total('');
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
        $scope.status_po = 'pending';
//        if (!$scope.is_view) {
        $scope.callServer(tableStateRef);
//        }
        $scope.is_edit = false;
        $scope.is_view = false;
        $scope.data.checklist = false;
        $scope.data.count = 0;
        $scope.data.is_check = false;
    };
    /** approved 1 */
    $scope.approve = function (form, approved) {
        var param = {
            id: form.id,
            approved: approved
        };

        if (param.approved == 'approved2') {
            Swal.fire({
                title: "Approve",
                text: "Apakah Barang diterima Semua?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3A9D5D",
                confirmButtonText: "Iya, diterima Semua",
                cancelButtonText: "Tidak"
            }).then((result) => {
                if (result.value) {
                    Data.post(control_link + '/approved', param).then(function (result) {
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
                            $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
                        }
                    });
                } else {
                    $scope.modalRetur($scope.form);
                }
            });
        } else {
            Swal.fire({
                title: "Approve",
                text: "Apakah Anda Ingin Approve Data Ini?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3A9D5D",
                confirmButtonText: "Iya, Aprrove",
                cancelButtonText: "Tidak"
            }).then((result) => {
                if (result.value) {
                    Data.post(control_link + '/approved', param).then(function (result) {
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
                            $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
                        }
                    });
                }
            });
        }
    };

    $scope.approveAll = function () {
        var form = [];
        var id = [];
        angular.forEach($scope.displayed, function (val, key) {
            if (val.checklist) {
                form.push(val)
                id.push(val.id)
            }
        })
        var params = {
            form: form,
            id: id
        }
        Data.post(control_link + '/approveAll', params).then(function (response) {
            if (response.status_code == 200) {
                $rootScope.alert("Tersimpan", "Data Berhasil Di Simpan", "success");
                $scope.cancel();
            } else {
                $rootScope.alert("Terjadi kesalahan", response.errors, "error");
            }
        });
    }

    $scope.unpost = function (form) {
        console.log(form)

        Swal.fire({
            title: "Unpost",
            text: "Apakah Anda Ingin Membatalkan Data Ini?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3A9D5D",
            confirmButtonText: "Iya, Batalkan",
            cancelButtonText: "Tidak"
        }).then((result) => {
            if (result.value) {
                Data.post(control_link + '/unpost', form).then(function (response) {
                    if (response.status_code == 200) {
                        $rootScope.alert("Tersimpan", "Data Berhasil Di Simpan", "success");
                        $scope.cancel();
                    } else {
                        $rootScope.alert("Terjadi kesalahan", response.errors, "error");
                    }
                })
            }
        });


    }

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

    $scope.cancel_data = function (row) {
        Swal.fire({
            title: "Peringatan ! ",
            text: "Apakah Anda Yakin Ingin Membatalkan Data Ini",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Iya, Batalkan",
            cancelButtonText: "Tidak",
        }).then((result) => {
            if (result.value) {
                row.is_deleted = 1;
                Data.post(control_link + '/cancel', row).then(function (result) {
                    Swal.fire({
                        title: "Dibatalkan",
                        text: "Data Berhasil Dibatalkan.",
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


    $scope.cariBarang = function (val, index, $select, is_dijual) {
        if (val.length > 2) {

            var dataa = {
                acc_m_lokasi_id: $scope.form.acc_m_lokasi_id.id,
//            acc_m_lokasi_id: 1,
                val: val,
                is_dijual: is_dijual
            };

            Data.get("m_barang/getBarang", dataa).then(function (response) {
                $scope.listBarang = response.data.list;

//                if ($scope.listBarang.length == 1) {
//
//                    $scope.detPenjualan[index].inv_m_barang_id = $scope.listBarang[0];
//                    $scope.changeBarang($scope.detPenjualan[index].inv_m_barang_id, index);
//
//                    var satuan = $scope.listBarang[0].nama_satuan != null ? $scope.listBarang[0].nama_satuan : "";
////                    $select.search = $scope.listBarang[0].kode + " - " + $scope.listBarang[0].nama + satuan + " (" + $scope.listBarang[0].stock + ")";
//                    focusInput("jumlah");
//
//                    $scope.nama_satuan = $scope.listBarang[0].nama_satuan;
//                }
            });
        }
    };

    $scope.getPengajuan = function (pengajuan) {
        Data.get(control_link + "/getPengajuan", pengajuan).then(function (response) {
            $scope.form = response.data.form;
            $scope.form.ongkos_kirim = 0
            $scope.form.tanggal = new Date($scope.form.tanggal * 1000)
            $scope.form.jatuh_tempo = new Date();
            $scope.detPenjualan = response.data.detail;
            $scope.total();
        });
    };

    $scope.changeBarang = function (form, index, dari, $select) {
//        console.log(';pj');
        angular.forEach($scope.detPenjualan, function (val, key) {
//            console.log('batu ok');
            if (dari != "ongkos_kirim") {
//                console.log(';dan cow');
//                if (form.stok == 0 && form.type != "jasa") {
//                    // toaster.pop({
//                    //     type: "error",
//                    //     title: "Peringatan!",
//                    //     body: "Stok " + form.nama + " Kosong",
//                    //     timeout: 3000
//                    // });
//                    alert("Stok " + form.nama + " Kosong!");
//                    form = undefined;
//                    $scope.detPenjualan[index].inv_m_barang_id = undefined;
//                }
                if (form.type == "jasa") {
                    $scope.detPenjualan[index].jumlah = $scope.form.is_koreksi ? $scope.detPenjualan[index].jumlah : 0;
                } else if (form.type == "barang" && form.type_barcode != "serial") {
                    $scope.detPenjualan[index].jumlah = $scope.form.is_koreksi ? $scope.detPenjualan[index].jumlah : 0;
                } else {
                    $scope.detPenjualan[index].jumlah = 0;
                }

                $scope.detPenjualan[index].type = form.type;
                $scope.detPenjualan[index].stok = form.stok;
                $scope.detPenjualan[index].hpp = form.hpp;
                $scope.detPenjualan[index].harga = form.harga_jual;
                $scope.detPenjualan[index].harga_beli = form.harga_beli;
                $scope.detPenjualan[index].subtotal = form.harga_jual;
                $scope.detPenjualan[index].grand_total = form.harga_jual;
                $scope.detPenjualan[index].deskripsi = form.nama;
                $scope.nama_satuan = form.nama_satuan;
                /*AKUN KONEK ACC*/
                $scope.detPenjualan[index].akun_pembelian_id = form.akun_pembelian_id;
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
            $scope.form.cash = 0;
            $scope.form.piutang = 0;
//            $scope.prepareJurnal();
            // }
        });
        $scope.modalHarga($scope.detPenjualan[index])
//        $scope.total();
    };

    $scope.kalkulasi = function () {
        var total = 0;
        angular.forEach($scope.detPenjualan, function (value, key) {
            total += parseInt(value.subtotal);
        });
        $scope.form.grand_total = parseInt($scope.form.ongkos_kirim) + parseInt(total);
        $scope.form.ppn = ($scope.form.persen_ppn / 100) * $scope.form.grand_total;
        $scope.form.grand_total = $scope.form.grand_total;
        $scope.form.grand_total2 = $scope.form.grand_total + $scope.form.ppn;

        $scope.form.piutang = parseInt($scope.form.grand_total) - parseInt($scope.form.cash);
    };
    $scope.total = function (jenis_diskon) {
        $scope.form.grand_total   = 0;
        $scope.form.grand_total2  = 0;
        $scope.form.ppn           = 0;
        $scope.form.diskon        = 0;

        angular.forEach($scope.detPenjualan, function (value, key) {
            var jumlah  = value.koreksi != undefined ? value.koreksi : value.jumlah;
            var harga   = value.koreksi_harga != undefined ? value.koreksi_harga : value.harga;

            /*DISKON*/
            if (jenis_diskon == "persen") {
                value.diskon = parseInt(harga) * parseInt(value.diskon_persen) / 100;
            } else if (jenis_diskon == "rupiah") {
                value.diskon_persen = Math.round(parseInt(value.diskon) * 100 / parseInt(harga));
            } else {
                value.diskon = parseInt(harga) * parseInt(value.diskon_persen) / 100;
            }
            var pengurangan_diskon = parseInt(value.diskon) * parseInt(jumlah);
            /*DISKON*/
            if (jenis_diskon == "persen" || jenis_diskon == "rupiah") {
                value.diskon = pengurangan_diskon;
            } else {
                value.diskon = pengurangan_diskon;
            }
            $scope.form.diskon      += pengurangan_diskon;
            value.subtotal          = parseInt(jumlah) * parseInt(harga) - parseInt(pengurangan_diskon);
            $scope.form.grand_total += parseInt(value.subtotal);
        });

        console.log("grnd ttl", $scope.form.grand_total);

        $scope.form.piutang       = 0;
        $scope.form.persen_ppn    = $scope.form.persen_ppn != undefined ? $scope.form.persen_ppn : 10;

        console.log("persen_ppn", $scope.form.persen_ppn);

        $scope.total_lama         = $scope.form.grand_total;
        $scope.form.ppn           = ($scope.form.persen_ppn / 100) * $scope.form.grand_total;
        console.log("ppn", $scope.form.ppn);
        $scope.form.grand_total   = $scope.form.grand_total;
        $scope.form.grand_total2  = $scope.form.grand_total + $scope.form.ppn;
        $scope.kalkulasi();
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
                akun: $scope.form.acc_m_kontak_id.acc_m_akun_id,
                keterangan: 'Penjualan'
            };
            index++;
        }
        //HUTANG
        angular.forEach($scope.detPenjualan, function (val, key) {
            if (val.akun_penjualan_id.id != undefined) {

                var exists = false;
                var key_exists = 0;
                angular.forEach(listJurnal, function (vals, keys) {
                    if (val.akun_penjualan_id.id == vals.akun.id) {
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
                        akun: val.akun_penjualan_id,
                        keterangan: 'Penjualan'
                    };

                    index++;
                }

                subtotal += val.subtotal;
                $scope.totalDebit += val.subtotal;
                $scope.totalKredit += val.subtotal;
            }
        })
        //HUTANG - END

        //HPP
        angular.forEach($scope.detPenjualan, function (val, key) {
            if (val.akun_hpp_id.id != undefined && val.hpp > 0) {

                var exists = false;
                var key_exists = 0;
                angular.forEach(listJurnal, function (vals, keys) {
                    if (val.akun_hpp_id.id == vals.akun.id) {
                        exists = true;
                        key_exists = keys;
                    }
                });

                //debit
                if (exists) {
                    listJurnal[key_exists].debit += val.hpp;
                } else {
                    listJurnal[index] = {
                        debit: val.hpp,
                        kredit: 0,
                        type: 'debit',
                        akun: val.akun_hpp_id,
                        keterangan: 'Penjualan'
                    };

                    index++;
                }

                subtotal += val.hpp;
                $scope.totalDebit += val.hpp;
                $scope.totalKredit += val.hpp;
            }
        })

        angular.forEach($scope.detPenjualan, function (val, key) {
            if (val.akun_persediaan_brg_id.id != undefined && val.hpp > 0) {

                var exists = false;
                var key_exists = 0;
                angular.forEach(listJurnal, function (vals, keys) {
                    if (val.akun_persediaan_brg_id.id == vals.akun.id) {
                        exists = true;
                        key_exists = keys;
                    }
                });

                //kredit
                if (exists) {
                    listJurnal[key_exists].kredit += val.hpp;
                } else {
                    listJurnal[index] = {
                        debit: 0,
                        kredit: val.hpp,
                        type: 'kredit',
                        akun: val.akun_persediaan_brg_id,
                        keterangan: 'Penjualan'
                    };

                    index++;
                }

                subtotal += val.hpp;
                $scope.totalDebit += val.hpp;
                $scope.totalKredit += val.hpp;
            }
        })
        //HPP - END

        $scope.listJurnal = listJurnal;
    }

    $scope.cariCustomer = function (query) {
        var params = {
            nama    : query != undefined ? query : null,
            lokasi  : $scope.form.acc_m_lokasi_id != undefined ? $scope.form.acc_m_lokasi_id.id : null
        };
        Data.get('acc/m_customer/getCustomer', params).then(function (response) {
            $scope.listCustomer = response.data.list;
        });

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

    $scope.suratJalan = function (form) {
        var param = {
            id: form.id,
            print: 1
        };

        Data.get('site/base_url').then(function (response) {
            window.open(response.data.base_url + "api/t_penjualan/suratJalan?" + $.param(param), "_blank");
        });
    }

    $scope.fakturPenjualan = function (form) {
        var param = {
            id    : form.id,
            print : 1
        };

        Data.get('site/base_url').then(function (response) {
            window.open(response.data.base_url + "api/t_penjualan/fakturPenjualan?" + $.param(param), "_blank");
        });
    }

    $scope.kwitansiPenjualan = function (form) {
        var param = {
            id: form.id,
            print: 1
        };

        Data.get('site/base_url').then(function (response) {
            window.open(response.data.base_url + "api/t_penjualan/kwitansiPenjualan?" + $.param(param), "_blank");
        });
    }

    $scope.modalRetur = function (form) {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/t_penjualan/modal.html",
            controller: "returPenjualanCtrl",
            size: "lg",
            backdrop: "static",
            keyboard: false,
            resolve: {
                form: form,
            }
        });
        modalInstance.result.then(function (response) {
            console.log(response)
            if (response.data == undefined) {
            } else {
                $scope.fakturPenjualan({id: response.data.form.inv_penjualan_id.id});
                $scope.cancel();
            }
        });
    }

    $scope.modalCustomer = function (lokasi, kontak = null) {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/t_penjualan/modalCustomer.html",
            controller: "customerCtrl",
            size: "lg",
            backdrop: "static",
            keyboard: false,
            resolve: {
                form: {
                    acc_m_lokasi_id: lokasi,
                    acc_m_kontak_id: kontak
                },
            }
        });
        modalInstance.result.then(function (response) {
            console.log(response)
            if (response.data == undefined) {
            } else {
                $scope.form.acc_m_kontak_id = response.data;
                $scope.fillAlamat()
//                $scope.fakturPenjualan({id: response.data.form.inv_penjualan_id.id});
//                $scope.cancel();
            }
        });
    }

    $scope.modalHarga = function (detail) {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/t_penjualan/modalHarga.html",
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
                if ($scope.form.is_koreksi)
                    detail.koreksi_harga = parseInt(response.data);
                else
                    detail.harga = parseInt(response.data);

                $scope.total('')
//                $scope.form.acc_m_kontak_id = response.data;
//                $scope.fakturPenjualan({id: response.data.form.inv_penjualan_id.id});
//                $scope.cancel();
            }
        });
    }
    $scope.modalKuitansi = function (detail) {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/t_penjualan/modalKuitansi.html?t=" + $.now(),
            controller: "kuitansiCtrl",
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
                detail.harga = parseInt(response.data);
                $scope.total('')
//                $scope.form.acc_m_kontak_id = response.data;
//                $scope.fakturPenjualan({id: response.data.form.inv_penjualan_id.id});
//                $scope.cancel();
            }
        });
    }

    $scope.modalFaktur = function (detail) {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/t_penjualan/modalFaktur.html",
            controller: "fakturCtrl",
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
                detail.harga = parseInt(response.data);
                $scope.total('')
//                $scope.form.acc_m_kontak_id = response.data;
//                $scope.fakturPenjualan({id: response.data.form.inv_penjualan_id.id});
//                $scope.cancel();
            }
        });
    }

    $scope.modalEditFaktur = function (form = {}) {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/m_faktur_penjualan/modalFaktur.html",
            controller: "faktureditCtrl",
            size: "lg",
            backdrop: "static",
            keyboard: false,
            resolve: {
                form: form,
            }
        });
        modalInstance.result.then(function (response) {
            console.log(response)
            if (response.data == undefined) {
            } else {
                $scope.form.inv_m_faktur_pajak_id = response.data;
            }
        });
    }

    $scope.modalSinkron = function (detail) {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/t_penjualan/modalSinkron.html",
            controller: "sinkronCtrl",
            size: "sm",
            backdrop: "static",
            keyboard: false,
            resolve: {
                form: detail,
            }
        });
        modalInstance.result.then(function (response) {
            console.log(response)
            if (response.data.status_code == 200) {
                $scope.cancel();
            } else {
                $rootScope.alert("Terjadi Kesalahan", setErrorMessage(response.errors), "error");
            }
        });
    }


    // Hapus Faktur Permanen
    $scope.hapusFaktur = function(form){
      console.log('asdasd');
      var konfirmasi = `Apakah Anda ingin menghapus Faktur Penjualan dengan Nomor Surat Jalan `
      + form.no_surat_jalan + ` ?`;

      if( !confirm(konfirmasi) ){
        return;
      }

      Data.post('t_penjualan/hapusFaktur', form).then(function (res) {
          if (res.status_code == 200) {
              $rootScope.alert("Berhasil", "Data berhasil dihapus.", "success");
              $scope.cancel();
          } else {
              $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
          }
      });
    };
    // Hapus Faktur Permanen - END
});

app.controller("returPenjualanCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {

    $scope.is_view = true;
    $scope.form = form;
    $scope.detPenjualan = [];
    Data.get("t_penjualan/getDetail?id=" + form.id + '&acc_m_lokasi_id=' + form.acc_m_lokasi_id.id).then(function (response) {
        $scope.detPenjualan = response.data.detail;
        angular.forEach($scope.detPenjualan, function (val, key) {
            val.jumlah_retur = 0;
        })
        console.log($scope.detPenjualan)
    });

    Data.get('acc/m_lokasi/index').then(function (response) {
        $scope.listLokasi = response.data.list;
        if ($scope.listLokasi.length > 0) {
            $scope.form.lokasi = $scope.listLokasi[0];
        }
    });

    $scope.save = function () {

        var params = {
            form: $scope.form,
            detail: $scope.detPenjualan
        }

        params.form.inv_penjualan_id = {
            id: params.form.id
        };
        params.form.id = undefined;
        params.form.tanggal_retur = params.form.tanggal;

        params.form.total = 0;
        angular.forEach($scope.detPenjualan, function (val, key) {
            params.form.total += val.jumlah_retur * val.harga;
            val.harga_retur = val.harga;
        })

        Data.post('t_retur_penjualan/save', params).then(function (result) {
            if (result.status_code == 200) {
//                $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
                params.form.inv_penjualan_id = {
                    id: result.data.inv_penjualan_id
                };
                Data.post('t_penjualan/update_after_retur', params).then(function (res) {
                    if (res.status_code == 200) {
                        $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
                        $uibModalInstance.close({
                            'data': params
                        });
                    } else {
                        $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
                    }
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

app.controller("customerCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {

//    $scope.is_view = true;
    $scope.form = {};
    $scope.form = form.acc_m_kontak_id != undefined ? form.acc_m_kontak_id : form;
    $scope.form.acc_m_lokasi_id = form.acc_m_lokasi_id;
//    $scope.detPenjualan = [];
    $scope.form.is_ppn = $scope.form.is_ppn != undefined ? $scope.form.is_ppn : 1;
    if (form.acc_m_kontak_id == undefined) {
        Data.get('acc/m_customer/kode', {project: 'afu'}).then(function (response) {
            $scope.form.kode = response;
        });
    }

    Data.get('acc/m_akun/akunPiutang').then(function (response) {
        $scope.listAkun = response.data.list;
    });

    $scope.save = function (form) {
        console.log("asd")
        form.project = 'afu';
        form.npwp = form.is_ppn == 1 ? form.npwp : null;
        Data.post('acc/m_customer/save', form).then(function (result) {
            console.log(result)
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

app.controller("hargaCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {

    console.log(form)
    $scope.form = form;

    $scope.save = function (form) {
        var harga = form.harga;
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

app.controller("kuitansiCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {

    $scope.form = [];
    $scope.getPenjualan = function (tanggal = null) {

        tanggal = tanggal != null ? moment(tanggal).format("YYYY-MM-DD") : null;

        Data.get('t_penjualan/getPenjualanPPN', {tanggal: tanggal}).then(function (response) {

            $scope.listPenjualan = response.data.listInvoice;
            $scope.jumlah = response.data.listInvoice.length;
            $scope.listCustomer = response.data.listCustomer;
        });
    }

    $scope.getPenjualan();

    // $scope.kecualiCustomer = function(){
    //   var listId=[];
    //   var no=0;
    //   angular.forEach($scope.form.kecuali, function (val, key) {
    //     console.log(val);
    //       listId[no] = val.id;
    //       no++;
    //   })
    //
    //   console.log(listId);
    //
    //   angular.forEach($scope.listPenjualan, function (val, key) {
    //     console.log(listId.indexOf(val.id));
    //     console.log(val.id);
    //     return;
    //     if( listId.indexOf(val.id) != -1 ){
    //       val.is_kecuali = true;
    //     }
    //   })
    // };

    $scope.checkAll = function (param, is) {
        angular.forEach($scope.listPenjualan, function (val, key) {
            val.checkbox = is;
        })
    }

    $scope.kwitansiPenjualan = function () {

        $scope.listCheckbox = [];
        angular.forEach($scope.listPenjualan, function (val, key) {
            if (val.checkbox == true) {
                $scope.listCheckbox.push(val.id);
            }
        })

        $scope.listKecuali = [];
        angular.forEach($scope.form.kecuali, function (val, key) {
            $scope.listKecuali.push(val.id);
        })

        console.log($scope.form)

        var param = {
            tanggal       : $scope.form.bulan != null ? moment($scope.form.bulan).format("YYYY-MM-DD") : null,
            kecuali       : $scope.listKecuali,
            mulai         : $scope.form.penjualan_mulai != undefined ? $scope.form.penjualan_mulai.no_invoice : null,
            selesai       : $scope.form.penjualan_sampai != undefined ? $scope.form.penjualan_sampai.no_invoice : null,
            listPenjualan : $scope.listCheckbox,
            print         : 1
        };

        console.log('sabar sabar ', param);

        Data.get('site/base_url').then(function (response) {
            window.open(response.data.base_url + "api/t_penjualan/kwitansiPenjualan?" + $.param(param), "_blank");
        });
    }

    $scope.close = function (harga) {
        $uibModalInstance.close({
            data: harga
        });
    };
});

app.controller("fakturCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {

    $scope.form = {};
    $scope.form.tanggal = {
        endDate: moment().add(1, 'M'),
        startDate: moment()
    };

    $scope.getPenjualan = function (tanggal = null) {

        tanggal = tanggal != null ? moment(tanggal).format("YYYY-MM-DD") : null;

        Data.get('t_penjualan/getPenjualanPPN', {tanggal: tanggal}).then(function (response) {

            $scope.listPenjualan = response.data.listInvoice;
            $scope.jumlah = response.data.length;
        });
    }

    $scope.getPenjualan();

    $scope.checkAll = function (param, is) {
        angular.forEach($scope.listPenjualan, function (val, key) {
            val.checkbox = is;
        })
    }

    $scope.kwitansiPenjualan = function () {

        $scope.listCheckbox = [];
        angular.forEach($scope.listPenjualan, function (val, key) {
            if (val.checkbox == true) {
                $scope.listCheckbox.push(val.id);
            }
        })

        console.log($scope.form)

        var param = {
          tanggal       : $scope.form.bulan != undefined ? moment($scope.form.bulan).format("YYYY-MM-DD") : null,
          mulai         : $scope.form.penjualan_mulai != undefined ? $scope.form.penjualan_mulai.no_invoice : null,
          selesai       : $scope.form.penjualan_sampai != undefined ? $scope.form.penjualan_sampai.no_invoice : null,
          listPenjualan : $scope.listCheckbox,
          print         : 1
        };
        console.log("maafin aku", param);
        Data.get('site/base_url').then(function (response) {
            window.open(response.data.base_url + "api/t_penjualan/fakturPenjualan?" + $.param(param), "_blank");
        });
    }

    $scope.close = function (harga) {
        $uibModalInstance.close({
            data: harga
        });
    };
});

app.controller("faktureditCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {

    if (form.id != undefined) {
        $scope.form = form;

        var myarr = form.nomor.split("-");
        var myarr1 = myarr[0].split(".");
        var myarr2 = myarr[1].split(".");

        $scope.form.form1 = myarr1[0];
        $scope.form.form2 = myarr1[1];
        $scope.form.form3 = myarr2[0];
        $scope.form.form4 = myarr2[1];
    } else {
        $scope.form = {};
    }

    console.log($scope.form)

//    $scope.is_view = true;
//    $scope.form = {
//        jenis_faktur: 'penjualan'
//    };

    $scope.setFaktur = function (param, max) {
        if ($scope.form['form' + param] != undefined) {
            if ($scope.form['form' + param].length == max) {
                console.log("ok")
                setTimeout(function () {
                    $('#form' + (param + 1)).focus()
                }, 1)

            }
        }

    }

    $scope.save = function () {
        if ($scope.form.form1 != undefined && $scope.form.form2 != undefined && $scope.form.form3 != undefined && $scope.form.form4 != undefined && ($scope.form.form5 != undefined || $scope.form.id != undefined)) {
            var detail = [];
            if ($scope.form.form5 != undefined) {
                for (var i = $scope.form.form4; i <= $scope.form.form5; i++) {
                    var str = "00000000";
                    var num = i + "";
                    var res = str.substr(0, str.length - num.length) + "" + num;
                    detail.push({no_faktur: $scope.form.form1 + "." + $scope.form.form2 + "-" + $scope.form.form3 + "." + res});
                }
            } else {
                detail.push({id: $scope.form.id, no_faktur: $scope.form.form1 + "." + $scope.form.form2 + "-" + $scope.form.form3 + "." + $scope.form.form4});
            }


            var params = {
                form: {jenis_faktur: 'penjualan'},
                detail: detail
            }

            Data.post('m_faktur_penjualan/save', params).then(function (result) {
                console.log(result)
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

app.controller("sinkronCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope) {

    $scope.form = {};
    $scope.form.tanggal = new Date();

    $scope.save = function () {
        var params = {
            tanggal: $scope.form.tanggal != null ? moment($scope.form.tanggal).format("YYYY-MM-DD") : null
        };

        Data.post("t_penjualan/sinkronInvoice", params).then(function (response) {
            $uibModalInstance.close({
                'data': response
            });
        });
    }

    $scope.close = function () {
        $uibModalInstance.close({
            'data': undefined
        });
    };
});
