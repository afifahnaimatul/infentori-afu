app.controller('pembayaranpiutangCtrl', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_pembayaran_piutang";
    var master = 'Transaksi Pembayaran Piutang';
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

    // Tambahkan akun pemndapatan dimuka
    $scope.setAkunDimuka = function(listAkun){
      Data.get('acc/t_penerimaan/setAkunDimuka').then(function (data) {
          $scope.listAkun = listAkun;
          $scope.listAkun.push(data.data.list);
      });
    }

    Data.get('acc/m_akun/akunKas').then(function (data) {
        $scope.listAkun = data.data.list;
        $scope.setAkunDimuka($scope.listAkun);
    });

    $scope.getCustomer = function (val) {
      if($scope.form.lokasi == undefined) return;

      var param = {
        nama        : val,
        lokasi      : $scope.form.lokasi.id != undefined ? $scope.form.lokasi.id : null,
        is_deleted  : 0
      };
      Data.get("acc/m_customer/getCustomer", param).then(function (response) {
          $scope.listCustomer = response.data.list;
      });
    };

    $scope.getListPiutang = function (customer_id, lokasi_id) {
        if ((customer_id != undefined && customer_id != '') && (lokasi_id != undefined && lokasi_id != '')) {
            var data = {
                customer_id : customer_id,
                lokasi_id   : lokasi_id
            };
            Data.get(control_link + "/getListPiutang", data).then(function (response) {
                $scope.detPiutang   = response.data.list;
                $scope.akunPiutang  = response.data.akun_piutang;

                // inisiasi & format date
                angular.forEach($scope.detPiutang, function (value, key) {
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

  $scope.toggle = function($event, dt) {
    $event.preventDefault();
    $event.stopPropagation();

    dt.opened = true;
  };

    $scope.bayarIni = function (detail, index) {
        var indexDetail = $scope.detPiutang.indexOf(detail);
        $scope.detPiutang[indexDetail].sisa_pelunasan = 0;

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
        angular.forEach($scope.detPiutang, function (value, key) {

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
        $scope.form.totalPiutang  = parseFloat($scope.form.total_bayar);
        $scope.form.total         = parseFloat($scope.form.totalBayar);

        // $scope.prepareJurnal();
    };
    // Fungsi Perhitungan Baru - End

    $scope.getDetail = function (id) {
        Data.get(control_link + "/view?id=" + id).then(function (response) {
            $scope.detPiutang = response.data.list;
            // Format tanggal
            angular.forEach($scope.detPiutang, function (value, key) {
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

      $scope.detPiutang.splice(paramindex, 1);
      $scope.kalkulasiBaru();
    };

    $scope.addRow = function (paramindex) {
      var newDet = {
          bayar   : 0,
          tanggal : new Date(),
      };
      $scope.detPiutang[paramindex].listBayar.push(newDet);
    };

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
        $scope.form.tanggal = new Date();
        $scope.form.tgl_verifikasi = {
            endDate: moment(),
            startDate: moment()
        };
        if ($scope.listAkun.length > 0) {
            $scope.form.akun = $scope.listAkun[0];
        }
        
        $scope.detPiutang = [{}];
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
    };

    /** save action */
    $scope.save = function (form, type_save) {
      form.status     = type_save
      form.startDate  = moment(form.tgl_verifikasi.startDate).format("YYYY-MM-DD")
      form.endDate    = moment(form.tgl_verifikasi.endDate).format("YYYY-MM-DD")

      var data = {
          form: form,
          detail: $scope.detPiutang,
          jurnal: $scope.listJurnal
      };
      Data.post(control_link + "/save", data).then(function (result) {
          if (result.status_code == 200) {
              $scope.is_edit = false;
              $scope.callServer(tableStateRef);
              $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
          } else {
              $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error" );
          }
      });
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

    $scope.printPiutang = function (id, tipe) {
        var param = {
            id: id,
            tipe: tipe
        }
        window.open($scope.url + "api/t_pembayaran_piutang/print?" + $.param(param), "_blank");
    }

});
