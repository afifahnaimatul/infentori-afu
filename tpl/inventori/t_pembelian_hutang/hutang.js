app.controller('pembelianHutangCtrl', function ($scope, Data, $rootScope, $stateParams, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_pembelian";
    var master = 'Saldo Awal Hutang Pembelian';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.master = master;

    $scope.date_open = function ($event, dt) {
        $event.preventDefault();
        $event.stopPropagation();

        dt.opened = true;
    };

    $scope.callServer = function callServer(tableState) {
        tableStateRef = tableState;
        $scope.isLoading = true;
        var offset = tableState.pagination.start || 0;
        var limit = tableState.pagination.number || 10;
        /** set offset and limit */
        var param = {
            offset    : offset,
            limit     : limit
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
        param['filter']['jenis_pembelian']  = 'hutang';


        Data.get(control_link + '/index', param).then(function (response) {
            $scope.displayed = response.data.list;
            tableState.pagination.numberOfPages = Math.ceil(response.data.totalItems / limit);
            $scope.totalDpp = response.data.totalDpp;
        });
        $scope.isLoading = false;
    };

    /** create */
    $scope.create = function () {
        $scope.is_edit    = true;
        $scope.is_view    = false;
        $scope.is_create  = true;
        $scope.is_disable = false;
        $scope.formtitle  = master + " | Form Tambah Data";
        $scope.form       = {};
        $scope.form.tanggal = new Date();
        $scope.form.jatuh_tempo = new Date();
        $scope.form.acc_m_lokasi_id = $scope.listLokasi.length > 0 ? $scope.listLokasi[0] : [];
        $scope.form.jenis_pembelian = 'hutang';
        $scope.form.total       = 0;
        $scope.form.ppn_edit    = 0;
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
    };

    /** view */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.is_create = false;
        $scope.formtitle = master + " | Lihat Data : " + form.kode;
        $scope.form = form;
        $scope.form.tanggal = new Date(form.tanggal);
        $scope.form.tanggal_ntpn = new Date(form.tanggal_ntpn);
        $scope.form.jatuh_tempo = new Date(form.jatuh_tempo * 1000);
    };

    /** save action */
    $scope.save = function (form, status) {
        form.type = 'save';
        form.status = status;

        var params = {
            form        : form,
            detail      : {},
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

    $scope.total = function(){
      console.log('total func');
      $scope.form.total       = $scope.form.total != undefined ? $scope.form.total : 0;
      $scope.form.ppn_edit    = $scope.form.ppn_edit != undefined ? $scope.form.ppn_edit : 0;
      $scope.form.hutang      = parseFloat($scope.form.total) + parseFloat($scope.form.ppn_edit);

      $scope.form.total_edit  = $scope.form.grand_total = $scope.form.total;
      $scope.form.ppn         = $scope.form.ppn_edit;
      // console.log($scope.form);
    }

    /** CRUD - END **/
    Data.get('acc/m_lokasi/index').then(function (response) {
        $scope.listLokasi = response.data.list;
    });

    Data.get('acc/m_akun/akunDetail').then(function (data) {
        $scope.listAkun = data.data.list;
    });

    $scope.getSuplier = function(pencarian = ''){
      if(pencarian != 'kosong' && pencarian.toString().length > 3){
        var params = {
          nama : pencarian
        };

      } else {
        var params = {};
      }


      Data.get(control_link + '/getSupplierAll', params).then(function (response) {
          $scope.listSupplier = response.data;
      });
    }

    $scope.getSuplier();

    $scope.changeSupplier = function (supplier) {
        $scope.form.is_ppn    = supplier.is_ppn;
    }
});
