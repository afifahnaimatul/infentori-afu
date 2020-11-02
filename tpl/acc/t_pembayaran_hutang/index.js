app.controller('pembayaranhutangCtrl', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_pembayaran_hutang";
    var master = 'Transaksi Pembayaran Hutang';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.base_url = '';
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.form = {};
    $scope.data = {};
    $scope.listJurnal = [];
    $scope.form.tanggal2 = {
        endDate: moment().add(1, 'D'),
        startDate: moment()
    };
    $scope.url = "";

    $scope.dateOptions = {
//        minMode: 'year'
    };

    Data.get('site/base_url').then(function (response) {
        $scope.url = response.data.base_url;
    });
    Data.get("acc/m_lokasi/getLokasi").then(function (result) {
        $scope.listLokasi = result.data.list;
    });

    /*
     * ambil data di load
     */
    Data.get('acc/m_akun/akunKas').then(function (data) {
        $scope.listAkun = data.data.list;
    });

    /*
     * ambil pemetaan akun potongan pembelian
     */
    Data.get('acc/m_akun_peta/getPemetaanAkun', {type: "Potongan Pembelian"}).then(function (data) {
        $scope.akunPotongan = data.data.list[0];
    });

    $scope.getSupplier = function(pencarian = ''){
      if(pencarian != 'kosong' && pencarian.toString().length > 3){
        var params = {
          nama : pencarian
        };

      } else {
        var params = {};
      }

      Data.get('t_pembelian/getSupplierAll', params).then(function (response) {
          $scope.listSupplier = response.data;
      });
    }

    $scope.resetFilter = function (filter) {
        $scope.form[filter] = undefined;
        $scope.onFilter($scope.form);
    }

    $scope.onFilter = function (val) {
        $scope.callServer(tableStateRef);
    }

    /*
     * detail
     */
    $scope.getListHutang = function (supplier_id, lokasi_id) {
        if ((supplier_id != undefined && supplier_id != '') && (lokasi_id != undefined && lokasi_id != '')) {
            var data = {
                supplier_id: supplier_id,
                lokasi_id: lokasi_id
            };
            Data.get(control_link + "/getListHutang", data).then(function (response) {
                $scope.detHutang = response.data.list;

                // inisiasi & format date
                angular.forEach($scope.detHutang, function (value, key) {
                    if(value.listBayar != undefined && value.listBayar.length > 0){
                      angular.forEach($scope.detPiutang, function (value2, key2) {
                        value2.tanggal = new Date(value2.tanggal);
                      });
                    } else {
                      var newDet = {
                          bayar   : 0,
                          tanggal : new Date(),
                      };
                      value.listBayar.push( newDet );
                    }
                });
                // inisiasi & format date - END

                $scope.kalkulasiBaru();
            });
        }
    };

    $scope.kalkulasi = function (is_view = '') {
        var totalBayar = 0;

        angular.forEach($scope.detHutang, function (value, key) {
            value.bayar       = value.bayar == undefined ? 0 : parseFloat(value.bayar);

            if( value.check == true ){
              value.sisa_pelunasan = parseFloat(value.bayar) - parseFloat(value.sisa);
            } else {
              if(is_view == 1){
                value.sisa_pelunasan = value.sisa_pelunasan;
              } else {
                value.sisa_pelunasan = 0;
              }
            }

            totalBayar                += parseFloat(value.bayar);
        });

        $scope.form.total_bayar = parseFloat(totalBayar);
        $scope.form.totalBayar  = parseFloat(totalBayar);
        $scope.form.totalHutang = parseFloat($scope.form.total_bayar);
        $scope.form.total       = parseFloat($scope.form.totalBayar);
    };

    $scope.toggle = function($event, dt) {
      $event.preventDefault();
      $event.stopPropagation();

      dt.opened = true;
    };

    $scope.bayarIni = function (detail, index) {
      var indexDetail = $scope.detHutang.indexOf(detail);
      $scope.detHutang[indexDetail].sisa_pelunasan = 0;
      $scope.kalkulasiBaru()
    }

    $scope.objectSize =   function(obj) {
      var size = 0, key;
      for (key in obj) {
          if (obj.hasOwnProperty(key)) size++;
      }
      return size;
    };

    // Fungsi Perhitungan Baru
    $scope.kalkulasiBaru = function (is_view = '') {
        var totalBayar = 0;

        $scope.form.denda == undefined ? 0 : parseFloat($scope.form.denda);
        angular.forEach($scope.detHutang, function (value, key) {

            value.bayar = value.bayar == undefined ? 0 : parseFloat(value.bayar);

            var bayarPerNota = 0;
            var panjangData = value.listBayar != undefined ? ($scope.objectSize(value.listBayar) -1) : 0;
            angular.forEach(value.listBayar, function (value2, key2) {
              bayarPerNota += parseFloat(value2.bayar);
            });
            value.bayar = bayarPerNota;

            if( value.check == true ){
              value.sisa_pelunasan = parseFloat(value.bayar) - parseFloat(value.sisa);
            } else {
              if(is_view == 1){
                value.sisa_pelunasan = value.sisa_pelunasan;
              } else {
                value.sisa_pelunasan = 0;
                value.listBayar[panjangData].sisa_pelunasan = 0;
              }
            }

            totalBayar += value.bayar;
        });

        $scope.form.total_bayar   = parseFloat(totalBayar);
        $scope.form.totalBayar    = parseFloat(totalBayar);
        $scope.form.totalHutang  = parseFloat($scope.form.total_bayar);
        $scope.form.total         = parseFloat($scope.form.totalBayar);
        console.log(totalBayar);
        console.log($scope.form);
    };
    // Fungsi Perhitungan Baru - End

    $scope.getDetail = function (id) {
        Data.get(control_link + "/view?id=" + id).then(function (response) {
            $scope.detHutang  = response.data.list;
            $scope.akunHutang = response.data.akun_hutang;
            // Format tanggal
            angular.forEach($scope.detHutang, function (value, key) {
              value.check = value.is_pelunasan == 1 ? true : false;
              angular.forEach(value.listBayar, function (value2, key2) {
                if(value2.is_pelunasan == 1){
                  value2.check = value.check = true;
                }
                value2.tanggal = new Date(value2.tanggal);
              });
            });
            console.log('view', $scope.detPiutang);
            // Format tanggal - END
            $scope.kalkulasiBaru(1);
        });
    };

    $scope.removeRow = function (paramindex) {
      var konfirm = `Apakah Anda yakin akan menghapus item ini?`;
      if( !confirm(konfirm) ){
        return;
      }

      $scope.detHutang.splice(paramindex, 1);
      $scope.kalkulasiBaru();
    };

    $scope.addRow = function (paramindex) {
      var newDet = {
          bayar   : 0,
          tanggal : new Date(),
      };
      $scope.detHutang[paramindex].listBayar.push(newDet);
    };

    $scope.prepareJurnal = function () {
      return;
        console.log("ok")
        console.log($scope.detHutang)
        var listJurnal = [];
        var index = 0;
        var total = 0;
        var keterangan = "";
        var keterangan_potongan = "";
        var potongan = 0;
        angular.forEach($scope.detHutang, function (val, key) {
            if (val.bayar > 0) {
                potongan += val.potongan;
                total += val.bayar;
                keterangan += "Pembayaran Hutang (" + val.kode + ")</br>";
            }
        });
        if (total > 0) {
            listJurnal[index] = {
                akun: $scope.akunHutang,
                tipe: "debit",
                debit: total,
                kredit: 0,
                keterangan: keterangan
            }
            total -= potongan;
            index++;
        }
        if (potongan > 0) {
            listJurnal[index] = {
                akun: $scope.akunPotongan,
                tipe: "kredit",
                debit: 0,
                kredit: potongan,
                keterangan: "Potongan Pembayaran Hutang"
            }
            total -= potongan;
            index++;
        }
        if ($scope.form.akun != undefined) {
            listJurnal[index] = {
                akun: $scope.form.akun,
                tipe: "kredit",
                debit: 0,
                kredit: total,
                keterangan: keterangan
            }
        }
        console.log(listJurnal)
        $scope.data.totalJurnal = total + potongan;
        $scope.listJurnal = listJurnal;
    }
    /*
     * end detail
     */

    $scope.getJurnal = function (id) {
        Data.get(control_link + "/getJurnal", {
            id: id
        }).then(function (response) {
            $scope.listJurnal = response.data.detail;
            $scope.data.totalJurnal = response.data.total.totalDebit;
        });
    };

    Data.get('acc/m_akun/getTanggalSetting').then(function (response) {

        $scope.tanggal_setting = response.data.tanggal;
        console.log($scope.tanggal_setting)

        $scope.options = {
            minDate: new Date(response.data.tanggal),
        };
    });


    $scope.master = master;
    $scope.callServer = function callServer(tableState) {
        tableStateRef = tableState;
        $scope.isLoading = true;
        var offset = tableState.pagination.start || 0;
        var limit = tableState.pagination.number || 1000;
        /** set offset and limit */
        var param = {
            limit: limit,
            offset: offset,
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
            $scope.base_url = response.data.base_url;
            tableState.pagination.numberOfPages = Math.ceil(
                    response.data.totalItems / limit
                    );
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
        $scope.form.tanggal = moment();
        $scope.form.tgl_verifikasi = {
            endDate: moment(),
            startDate: moment()
        };
        if ($scope.listAkun.length > 0) {
            $scope.form.akun = $scope.listAkun[0];
        }
        $scope.form.tanggal = new Date($scope.tanggal_setting);
        if (new Date() >= new Date($scope.tanggal_setting)) {
            $scope.form.tanggal = new Date();
        }
        $scope.detHutang = [{}];
    };
    /** update */
    $scope.update = function (form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_update = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Edit Data : " + form.kode;
        $scope.form = form;
        $scope.form.tanggal = new Date(form.tanggal);
        $scope.form.tgl_verifikasi = {
            startDate: form.tgl_mulai,
            endDate: form.tgl_selesai
        }
        $scope.getDetail(form.id);


    };
    /** view */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.is_create = false;
        $scope.is_update = false;
        $scope.formtitle = master + " | Lihat Data : " + form.kode;
        $scope.form = form;
        $scope.form.tanggal = new Date(form.tanggal);
        $scope.form.tgl_verifikasi = {
            startDate: form.tgl_mulai,
            endDate: form.tgl_selesai
        }
        $scope.getDetail(form.id);
        if (form.status == "Terposting") {
            $scope.getJurnal(form.id)
        }

    };
    /** save action */
    $scope.save = function (form, type_save) {
        // if (($scope.data.totalJurnal != $scope.form.total_bayar)) {
        //     $rootScope.alert(
        //             "Peringatan!",
        //             "Total jurnal dan total bayar tidak sama",
        //             "error"
        //             );
        // } else {
        //     var go = true;
        //     if ($scope.detHutang.length < 1) {
        //         go = false;
        //     }
        //     angular.forEach($scope.listJurnal, function (value, key) {
        //         if (value.akun == undefined) {
        //             go = false;
        //         }
        //     });
        //     if (go) {
                form.status = type_save
                form.startDate = moment(form.tgl_verifikasi.startDate).format("YYYY-MM-DD")
                form.endDate = moment(form.tgl_verifikasi.endDate).format("YYYY-MM-DD")
                var data = {
                    form: form,
                    detail: $scope.detHutang,
                    jurnal: $scope.listJurnal
                };
                Data.post(control_link + "/save", data).then(function (result) {
                    if (result.status_code == 200) {
                        $scope.is_edit = false;
                        $scope.callServer(tableStateRef);
                        $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
                    } else {
                        $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
                    }
                });
        //     } else {
        //         $rootScope.alert(
        //                 "Peringatan!",
        //                 "Jurnal / detail tidak valid, cek kembali jurnal dan detail sebelum simpan",
        //                 "error"
        //                 );
        //     }
        //
        //
        // }

    };
    /** cancel action */
    $scope.cancel = function () {
        if (!$scope.is_view) {
            $scope.callServer(tableStateRef);
        }
        $scope.is_edit = false;
        $scope.is_view = false;
    };
    $scope.trash = function (row) {
        var data = angular.copy(row);
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
                    $rootScope.alert("Berhasil", "Data berhasil dihapus", "success");
                    $scope.cancel();

                });
            }
        });
    };
    $scope.restore = function (row) {
        var data = angular.copy(row);
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
                    $rootScope.alert("Berhasil", "Data berhasil direstore", "success");
                    $scope.cancel();

                });
            }
        });
    };
    $scope.delete = function (row) {
        var data = angular.copy(row);
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
                    $rootScope.alert("Berhasil", "Data berhasil dihapus permanen", "success");
                    $scope.cancel();

                });
            }
        });

    };

    $scope.unpost = function (row) {
        Swal.fire({
            title: "Peringatan!",
            text: "Apakah Anda yakin akan membatalkan Transaksi ini?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Ya",
            cancelButtonText: "Tidak",
        }).then((result) => {
            if (result.value) {
              Data.post(control_link + '/unpost', row).then(function (result) {
                $rootScope.alert("Berhasil", "Transaksi berhasil dibatalkan", "success");
                $scope.callServer(tableStateRef);
                $scope.cancel();
              });
            }
        });
    };

    $scope.printHutang = function (id, tipe) {
        var param = {
            id: id,
            tipe: tipe
        }
        window.open($scope.url + "api/t_pembayaran_hutang/print?" + $.param(param), "_blank");
    }

});
