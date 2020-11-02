app.controller('customerCtrl', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "acc/m_customer";
    var master = 'Master Customer';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.master = master;

    Data.get('acc/m_lokasi/getLokasi').then(function (response) {
        $scope.listLokasi = response.data.list;

    });

    Data.get('acc/m_akun/akunPiutang').then(function (response) {
        $scope.listAkun = response.data.list;
    });

    $scope.generateKode = function () {
        Data.get(control_link + '/kode', {project: 'afu'}).then(function (response) {
            $scope.form.kode = response;
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
        if ($scope.listLokasi.length > 0) {
            $scope.form.acc_m_lokasi_id = $scope.listLokasi[0];
        }
        $scope.generateKode();
    };
    /** update */
    $scope.update = function (form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_update = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Edit Data : " + form.nama;
        $scope.form = form;
        if (!$scope.form.kode) {
            $scope.generateKode();
        }
    };
    /** view */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Lihat Data : " + form.nama;
        $scope.form = form;
    };
    /** save action */
    $scope.save = function (form) {
        console.log("asd")
        form.project = "afu";
        Data.post(control_link + '/save', form).then(function (result) {
            console.log(result)
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

    // Modal Copy
    $scope.modalFaktur = function (form = {}) {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/m_customer/modalCopy.html",
            controller: "copyCtrl",
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
              $scope.cancel()
            }
        });
    }
    // Modal Copy - END
});

app.controller("copyCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {
  console.log(form);
  console.log('modal open');
  $scope.form = form;
    // Get Cabang / Depo
    Data.get('acc/m_lokasi/index', {is_deleted:0}).then(function (result) {
      if (result.status_code == 200) {
          $scope.listLokasi = result.data.list;
          console.log(result);
      } else {
          $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
      }
    });

    $scope.save = function (form, tujuan) {
      console.log(form);
        var params = {
            form: form,
        }

        Data.post('m_kategori/copyCustomer', params).then(function (result) {
            if (result.status_code == 200) {
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
