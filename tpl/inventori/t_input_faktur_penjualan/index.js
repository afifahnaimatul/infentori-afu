app.controller('inputfakturpenjualanCtrl', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_input_faktur_penjualan";
    var master = 'Input Faktur Pajak Penjualan';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.base_url = '';
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.is_create = false;
    $scope.is_plus = true;

    Data.get("m_faktur_penjualan/index", {filter: {'inv_penjualan.id': 0, 'jenis_faktur': 'penjualan'}}).then(function (response) {
        $scope.listFaktur = response.data.list;
//        $scope.listFaktur = [];
    });


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
        Data.get(control_link + '/index', param).then(function (response) {
            $scope.displayed = response.data.list;
            $scope.base_url = response.data.base_url;
            tableState.pagination.numberOfPages = Math.ceil(
                    response.data.totalItems / limit
                    );
        });
        $scope.isLoading = false;
    };

    $scope.fillFaktur = function () {
        console.log($scope.displayed)
        var arr = $scope.displayed.length > $scope.listFaktur.length ? $scope.listFaktur : $scope.displayed
        var status = $scope.displayed.length <= $scope.listFaktur.length ? "ok" : "kurang"
        var kurang = $scope.displayed.length - $scope.listFaktur.length;
        if (status == "kurang") {
            Swal.fire({
                title: "Peringatan ! ",
                text: "Nomor Faktur kurang " + kurang + ", Apakah anda ingin menginputkan nomor faktur baru ?",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Iya",
                cancelButtonText: "Tidak",
            }).then((result) => {
                if (result.value) {
                    $scope.modalFaktur()
                }
            });
        } else {
            angular.forEach(arr, function (val, key) {
                if ($scope.displayed[key].terpakai != 'ya') {
                    $scope.displayed[key].faktur = $scope.listFaktur[key];
                }

            })
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
        $scope.formtitle = master + " | Edit Data : " + form.no_faktur;

        $scope.form = form;
        $scope.detFaktur = [{no_faktur: $scope.form.no_faktur, id: $scope.form.id}];
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
    $scope.save = function (form) {
        var duplicate = false;
        var alert = '';
        console.log(form)
        //cek faktur duplikat
        angular.forEach(form, function (val, key) {
            if (val.faktur != undefined) {
                var d = false;
                angular.forEach(form, function (v, k) {
                    if (v.faktur != undefined) {
                        if (val.faktur.no_faktur == v.faktur.no_faktur && key != k) {
                            duplicate = true;
                            d = true;
                            if (alert.includes(val.faktur.no_faktur)) {
                                if (alert.includes(v.kode)) {
                                    alert += '';
                                } else {
                                    alert += v.kode + ', ';
                                }
                            } else {
                                alert += val.faktur.no_faktur + ' terdapat di Penjualan : ' + v.no_surat_jalan + ', ';
                            }
//                            alert += alert.includes(val.faktur.no_faktur) ? v.kode + ', ' : val.faktur.no_faktur + ' terdapat di Penjualan : ' + v.kode + ', ';
                        }
                    }
                })
                if (d)
                    alert += '<br/>';
            }
        })
//        alert = alert.slice(0, -2) + ' Duplikat';
//        if (duplicate) {
//            Swal.fire(
//                    'Terdapat Duplikasi Nomer Faktur',
//                    alert,
//                    'error'
//                    )
////            $rootScope.alert("Terjadi Kesalahan", "Terdapat nomer faktur pajak yg sama", "error");
//        } else {
        Swal.fire({
            title: "Peringatan ! ",
            text: "Apakah Anda Yakin Ingin Menginput Nomer Faktur Pada Penjualan ?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Iya",
            cancelButtonText: "Tidak",
        }).then((result) => {
            if (result.value) {
                Data.post(control_link + '/save', form).then(function (result) {
                    if (result.status_code == 200) {
                        $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
                        $scope.cancel();
                    } else {
                        $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
                    }
                });
            }
        });
//        }
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

    $scope.modalFaktur = function () {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/m_faktur_penjualan/modalFaktur.html",
            controller: "fakturCtrl",
            size: "md",
            backdrop: "static",
            keyboard: false,
//            resolve: {
//                form: form,
//            }
        });
        modalInstance.result.then(function (response) {
            console.log(response)
            if (response.data == undefined) {
            } else {
                Data.get("m_faktur_penjualan/index", {filter: {'inv_penjualan.id': 0, 'jenis_faktur': 'penjualan'}}).then(function (response) {
                    $scope.listFaktur = response.data.list;
                    $scope.fillFaktur()
                });

//                $scope.form.inv_m_faktur_pajak_id = response.data;
//                $scope.fakturPenjualan({id: response.data.form.inv_penjualan_id.id});
//                $scope.cancel();
            }
        });
    }
});

app.controller("fakturCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope) {

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
        if ($scope.form.form1 != undefined && $scope.form.form2 != undefined && $scope.form.form3 != undefined && $scope.form.form4 != undefined && $scope.form.form5 != undefined) {
            var detail = [];
            for (var i = $scope.form.form4; i <= $scope.form.form5; i++) {
                var str = "00000000";
                var num = i + "";
                var res = str.substr(0, str.length - num.length) + "" + num;
                detail.push({no_faktur: $scope.form.form1 + "." + $scope.form.form2 + "-" + $scope.form.form3 + "." + res});
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