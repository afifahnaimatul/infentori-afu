app.controller('fakturpembelianCtrl', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "m_faktur_penjualan";
    var master = 'Master Faktur Pembelian';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.base_url = '';
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.is_create = false;
    $scope.is_plus = true;

    $scope.master = master;
    $scope.callServer = function callServer(tableState) {
        tableStateRef = tableState;
        $scope.isLoading = true;
        var offset = tableState.pagination.start || 0;
        var limit = tableState.pagination.number || 10;

        /** set offset and limit */
        var param = {
//            offset: offset,
//            limit: limit
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
        if (param['filter'] != undefined) {
            param['filter']['inv_m_faktur_pajak.jenis_faktur'] = 'pembelian';
        }else{
             param['filter'] = {
                 'inv_m_faktur_pajak.jenis_faktur' : 'pembelian'
             }
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

    $scope.addDetail = function (k, v) {
        if ($scope.is_plus) {
            var myarr = v.no_faktur.split(" ");

            console.log(myarr)
            var index = k;
            if (myarr.length > 1) {
                angular.forEach(myarr, function (val, key) {
                    var newDet = {no_faktur: val};
                    $scope.detFaktur[index] = newDet;
                    index++;
                })
                var newDet = {no_faktur: ''};
                $scope.detFaktur[index] = newDet;
            } else {
                if (v.no_faktur != '') {
                    var newDet = {no_faktur: ''};
                    $scope.detFaktur[index + 1] = newDet;
                }
            }
        }


    }

    /** create */
    $scope.create = function () {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_create = true;
        $scope.is_create = true;
        $scope.is_plus = true;
        $scope.formtitle = master + " | Form Tambah Data";
        $scope.form = {};
        $scope.detFaktur = [{no_faktur: ''}];
    };
    /** update */
    $scope.update = function (form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_create = false;
        $scope.is_plus = false;
        $scope.formtitle = master + " | Edit Data : " + form.nomor;

        $scope.form = form;
        $scope.detFaktur = [{no_faktur: $scope.form.nomor, id: $scope.form.id}];
    };
    /** view */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_create = false;
        $scope.formtitle = master + " | Lihat Data : " + form.nama;
        $scope.form = form;
    };
    /** save action */
    $scope.save = function (form, detail) {
        var params = {
            form: form,
            detail: detail
        }
//        var url = (form.id > 0) ? '/update' : '/save';
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
        if (!$scope.is_view) {
            $scope.callServer(tableStateRef);
        }
        $scope.is_edit = false;
        $scope.is_view = false;
    };
    $scope.trash = function (row) {
        var data = angular.copy(row);
        console.log(row)
        if (row.child != 0) {
            $rootScope.alert("Terjadi Kesalahan", "Data tidak bisa dihapus", "error");
        } else {
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
        }

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
                    $rootScope.alert("Berhasil", "Data berhasil dihapus", "success");
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
                    $rootScope.alert("Berhasil", "Data berhasil dihapus", "success");
                    $scope.cancel();

                });
            }
        });

    };
});