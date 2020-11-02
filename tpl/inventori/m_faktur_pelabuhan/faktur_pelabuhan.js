app.controller('fpelabuhanCtrl', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "m_faktur_pelabuhan";
    var master = 'Master Faktur Pajak Bea Pelabuhan';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.master = master;

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
        $scope.is_edit      = true;
        $scope.is_view      = false;
        $scope.is_create    = true;
        $scope.is_disable   = false;
        $scope.formtitle    = master + " | Form Tambah Data";
        $scope.form         = {};
        $scope.form.tanggal = new Date();
        $scope.form.ppn     = 10;
        $scope.getPelabuhan();
        $scope.initDetail();
    };
    /** update */
    $scope.update = function (form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_update = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Edit Data : " + form.nomor;
        $scope.form = form;
        $scope.form.tanggal = new Date(form.tanggal);
        $scope.getDetail(form);
    };
    /** view */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Lihat Data : " + form.nomor;
        $scope.form = form;
        $scope.form.tanggal = new Date(form.tanggal);
        $scope.getDetail(form);
    };
    /** save action */
    $scope.save = function () {
        var param = {
          form    : $scope.form,
          detail  : $scope.listDetail
        };

        Data.post(control_link + '/save', param).then(function (result) {
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

    $scope.getPelabuhan = function(){
      Data.get('m_pelabuhan/index', {filter:{is_deleted:0}}).then(function (response) {
        $scope.getListPelabuhan = response.data.list;
      });
    };
    $scope.getPelabuhan();

    Data.get('acc/m_lokasi/index', {filter:{is_deleted:0}}).then(function (response) {
        $scope.listLokasi = response.data.list;
    });

    // Detail item
    $scope.getDetail = function (form) {
      var params = {
        id : form.id
      }
      Data.get(control_link + "/getDetail", params).then(function (response) {
        $scope.listDetail = response.data;
        $scope.total();
      });
    };

    $scope.initDetail = function(){
      $scope.listDetail = [{
        deskripsi : "",
        jumlah    : '',
        harga     : ''
      }];
    }

    $scope.addDetail = function () {
        var val = $scope.listDetail.length;
        var newDet = {
          deskripsi : "",
          jumlah    : '',
          harga     : ''
        };
        $scope.listDetail.push(newDet);
    };

    $scope.removeRow = function (paramindex) {
      var konf = "Apakah Anda ingin menghapus item ini?"

      if(confirm(konf)){
        $scope.listDetail.splice(paramindex, 1);
        $scope.total();
      }
    };
    // Detail item - END

    $scope.total = function(){
      var jenis_ppn = parseInt($scope.form.jenis_ppn);
      var diskon    = parseInt($scope.form.diskon) || 0;
      var uang_muka = parseInt($scope.form.uang_muka) || 0;
      var total     = 0;

      angular.forEach($scope.listDetail, function (val, key) {
        $scope.listDetail[key].sub_total = val.harga * val.jumlah;
        total += $scope.listDetail[key].sub_total;
      });

      $scope.form.total     = total;
      $scope.form.diskon    = diskon;
      $scope.form.uang_muka = uang_muka;

      var dpp_awal      = total - diskon - uang_muka;
      $scope.form.dpp   = dpp_awal;

      $scope.form.ppn   = $scope.form.dpp * (jenis_ppn/100);

      $scope.form.ppnbm = 0;
      $scope.form.pajak_terutang = parseInt($scope.form.dpp) + parseInt($scope.form.ppn);
    }
});
