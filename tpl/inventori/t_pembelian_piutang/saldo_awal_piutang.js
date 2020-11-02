app.controller('saldoawalpiutangCtrl', function ($scope, Data, $rootScope, $state, $stateParams, $uibModal, Upload) {
    var tableStateRef;
    var control_link = "t_saldo_awal_piutang";
    var master = 'Saldo Awal Piutang';
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

        param['filter']['inv_penjualan.tipe']  = 'piutang';

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
        $scope.form.tipe = 'piutang';
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
    };
    /** view */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Lihat Data : " + form.kode;
        $scope.form = form;

        $scope.form.tanggal = new Date(form.tanggal2);
    };

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
        form.grand_total = form.piutang;
        form.is_saldo_awal_piutang = 1;
        var params = {
            form: form,
            detail: {},
            listJurnal: {}
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

    /** CRUD - END **/
    Data.get('acc/m_lokasi/index').then(function (response) {
        $scope.listLokasi = response.data.list;
    });
    Data.get('acc/m_akun/akunDetail').then(function (data) {
        $scope.listAkun = data.data.list;
    });

    $scope.cariCustomer = function (query) {
        var params = {
            nama: query != undefined ? query : null,
            lokasi: $scope.form.acc_m_lokasi_id != undefined ? $scope.form.acc_m_lokasi_id.id : null
        };
        Data.get('acc/m_customer/getCustomer', params).then(function (response) {
            $scope.listCustomer = response.data.list;
        });

    };

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
            }
        });
    }
    // Hapus Faktur Permanen
    $scope.hapusFaktur = function(form){
      console.log('asdasd');
      var konfirmasi = `Apakah Anda ingin menghapus Saldo Piutang `
      + form.no_invoice + ` ?`;

      if( !confirm(konfirmasi) ){
        return;
      }

      Data.post('t_saldo_awal_piutang/hapusFaktur', form).then(function (res) {
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
