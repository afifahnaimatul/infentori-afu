app.controller('fakturpenjualanCtrl', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "m_faktur_penjualan";
    var master = 'Master Faktur Penjualan';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.base_url = '';
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.is_create = false;
    $scope.is_plus = true;
    $scope.filter = {};

    $scope.master = master;

    $scope.getBulan = function(){
      $scope.callServer(tableStateRef);
    }

    $scope.callServer = function callServer(tableState) {
        tableStateRef = tableState;
        $scope.isLoading = true;
        var offset = tableState.pagination.start || 0;
        // var limit = tableState.pagination.number || 10;
        var limit =  20;

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
        if (param['filter'] != undefined) {
            param['filter']['inv_m_faktur_pajak.jenis_faktur'] = 'penjualan';
        } else {
            param['filter'] = {
                'inv_m_faktur_pajak.jenis_faktur': 'penjualan'
            }
        }

        if( $scope.filter.bulan != undefined ){
          param['filter']['bulan'] = $scope.filter.bulan;
        }

        console.log(param);
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

        $scope.modalFaktur(form)
//        $scope.is_edit = true;
//        $scope.is_view = false;
//        $scope.is_create = false;
//        $scope.is_plus = false;
//        $scope.formtitle = master + " | Edit Data : " + form.nomor;
//
//        $scope.form = form;
//        $scope.detFaktur = [{no_faktur: $scope.form.nomor, id: $scope.form.id}];
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

    $scope.modalFaktur = function (form = {}) {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/m_faktur_penjualan/modalFaktur.html",
            controller: "fakturCtrl",
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
                $scope.cancel()
//                $scope.form.inv_m_faktur_pajak_id = response.data;
//                $scope.fakturPenjualan({id: response.data.form.inv_penjualan_id.id});
//                $scope.cancel();
            }
        });
    }

    $scope.modalHapus = function () {
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/inventori/m_faktur_penjualan/modalHapus.html",
            controller: "hapusCtrl",
            size: "lg",
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
                $scope.cancel()
//                $scope.form.inv_m_faktur_pajak_id = response.data;
//                $scope.fakturPenjualan({id: response.data.form.inv_penjualan_id.id});
//                $scope.cancel();
            }
        });
    }
});

app.controller("fakturCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {

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

app.controller("hapusCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope) {

//    $scope.is_view = true;
//    $scope.form = {
//        jenis_faktur: 'penjualan'
//    };

    $scope.form = [];

    Data.get('/m_faktur_penjualan/index', {filter: {'inv_m_faktur_pajak.jenis_faktur': 'penjualan'}}).then(function (response) {
        $scope.listFaktur = response.data.list;
    });

    $scope.delete = function (all = 0) {

        console.log($scope.form)
        if (($scope.form.faktur_mulai != undefined && $scope.form.faktur_sampai != undefined) || all == 1) {
            var params = {
                mulai: $scope.form.faktur_mulai != undefined ? $scope.form.faktur_mulai.id : null,
                selesai: $scope.form.faktur_sampai != undefined ? $scope.form.faktur_sampai.id : null,
                all: all,
            }

            Data.post('m_faktur_penjualan/delete', params).then(function (result) {
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
            $scope.close();
    }

    };

    $scope.close = function () {
        $uibModalInstance.close({
            'data': undefined
        });
    };
});
