app.controller("tpengajuanCtrl", function ($scope, Data, $rootScope, $uibModal, $stateParams) {
    /**
     * Inialisasi
     */
    var tableStateRef;
    $scope.formtittle = "";
    $scope.displayed = [];
    $scope.form = {};
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.is_create = false;
    $scope.is_copy = false;
    $scope.loading = false;
    $scope.special_filter = {};
    $scope.cari = {
        periode: {
            endDate: moment().add(1, 'M'),
            startDate: moment().subtract(1, 'M')
        }
    };
    var master = "Transaksi Pengajuan";
    $scope.master = master;
    $scope.cek_special = 0;
    $scope.form.periode = {
        endDate: moment().add(1, 'M'),
        startDate: moment()
    };
    Data.get('site/base_url').then(function (response) {
        $scope.url = response.data;
    });

    Data.get('acc/apppengajuan/getKategori').then(function (response) {
        $scope.listKategori = response.data;
    })
    /**
     * Ambil semua lokasi
     */
    Data.get('acc/m_lokasi/getLokasi').then(function (response) {
        $scope.listLokasi = response.data.list;
    });
    /*
     * Ambil akun untuk detail
     */
    Data.get('acc/m_akun/akunDetail').then(function (data) {
        $scope.listAkun = data.data.list;
    });
    /*
     * ambil user
     */
    Data.get("/acc/appuser/getAll").then(function (response) {
        $scope.listUser = response.data;
    });
    $scope.filterTanggal = function () {
        $scope.callServer(tableStateRef);
    }
    $scope.getBudgeting = function (lokasi, tanggal) {
        var form = {
            lokasi: lokasi,
            tanggal: tanggal,
            detail: $scope.listDetail
        }
        Data.post("acc/apppengajuan/getBudgeting", form).then(function (response) {
            $scope.listDetail = response.data;
        })
    };
    if (typeof $stateParams.lokasi != "undefined" && $stateParams.lokasi != "" && $stateParams.lokasi !== null) {
        $scope.special_filter = {
            tahun: new Date($stateParams.tahun),
            lokasi: $stateParams.lokasi
        }
    }
    /**
     * End inialisasi
     */
    $scope.callServer = function callServer(tableState) {
        tableStateRef = tableState;
        $scope.isLoading = true;
        var offset = tableState.pagination.start || 0;
        var limit = tableState.pagination.number || 10;
        var param = {
            offset: offset,
            limit: limit,
            special_tahun: $scope.special_filter.tahun,
            special_lokasi: $scope.special_filter.lokasi,
            start_date: moment($scope.cari.periode.startDate).format("YYYY-MM-DD"),
            end_date: moment($scope.cari.periode.endDate).format("YYYY-MM-DD")
        };
        if (tableState.sort.predicate) {
            param["sort"] = tableState.sort.predicate;
            param["order"] = tableState.sort.reverse;
        }
        if (tableState.search.predicateObject) {
            param["filter"] = tableState.search.predicateObject;
        }
        param["type"] = "pengajuan";
        Data.get("acc/apppengajuan/index", param).then(function (response) {
            $scope.displayed = response.data.list;
            tableState.pagination.numberOfPages = Math.ceil(response.data.totalItems / limit);
        });
        $scope.isLoading = false;
    }
    $scope.getDetail = function (id) {
        Data.get("acc/apppengajuan/view?t_pengajuan_id=" + id).then(function (response) {
            $scope.listDetail = response.data;
            if ($scope.is_copy) {
                angular.forEach($scope.listDetail, function (value, key) {
                    value.id = "";
                });
            }
            $scope.sumTotal();
        });
    };
    $scope.listDetail = [{}];
    $scope.addDetail = function (val) {
        var comArr = eval(val);
        var newDet = {
            m_akun_id: {
                id: $scope.listAkun[0].id,
                kode: $scope.listAkun[0].kode,
                nama: $scope.listAkun[0].nama
            },
            jenis_satuan: 'unit',
            harga_satuan: 0,
            jumlah: 1
        };
        val.push(newDet);
        $scope.sumTotal();
    };
    $scope.removeDetail = function (val, paramindex) {
        var comArr = eval(val);
        if (comArr.length > 1) {
            val.splice(paramindex, 1);
        } else {
            alert("Something gone wrong");
        }
    };
    $scope.sumTotal = function () {
        var jumlah_perkiraan = 0;
        angular.forEach($scope.listDetail, function (value, key) {
            if (value.harga_satuan === undefined) {
                value.harga_satuan = 0;
            }
            if (value.jumlah === undefined) {
                value.jumlah = 0;
            }
            value.sub_total = parseInt(value.harga_satuan) * parseInt(value.jumlah);
            jumlah_perkiraan += value.sub_total;
        });
        $scope.form.jumlah_perkiraan = jumlah_perkiraan;
    };
    $scope.getAcc = function (id) {
        Data.get("acc/apppengajuan/getAcc?t_pengajuan_id=" + id).then(function (response) {
            $scope.listAcc = response.data;
        });
    };
    $scope.listAcc = [{}];
    $scope.addAcc = function (val) {
        var comArr = eval(val);
        var newDet = {};
        val.push(newDet);
    };
    $scope.removeDetail = function (val, paramindex) {
        var comArr = eval(val);
        if (comArr.length > 1) {
            val.splice(paramindex, 1);
        } else {
            alert("Something gone wrong");
        }
    };
    $scope.create = function (form) {
        $scope.is_copy = false;
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_create = true;
        $scope.formtittle = master + " | Form Tambah Data";
        $scope.form = {};
        $scope.form.tanggal = new Date();
        $scope.form.butuhapproval = 1;
        $scope.form.tipe = 'Budgeting';
        $scope.listDetail = [{
                m_akun_id: {
                    id: $scope.listAkun[0].id,
                    kode: $scope.listAkun[0].kode,
                    nama: $scope.listAkun[0].nama
                },
                jenis_satuan: 'unit',
                harga_satuan: 0,
                jumlah: 1,
                detail: []
            }];
        if ($scope.listLokasi.length > 0) {
            $scope.form.m_lokasi_id = $scope.listLokasi[0];
        }

        if ($scope.listKategori.length > 0) {
            $scope.form.m_kategori_pengajuan_id = $scope.listKategori[0];
        }
        $scope.listAcc = {};
        $scope.sumTotal();
    };
    $scope.copy = function (form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_create = true;
        $scope.is_copy = true;
        $scope.formtittle = master + " | Form Salin Data";
        $scope.form = {};
        $scope.form.tanggal = new Date();
        $scope.form.approval = 1;
        $scope.listAcc = {};
        $scope.sumTotal();
        /*
         * ambil pengajuan untuk copy
         */
        Data.get("acc/apppengajuan/getAll").then(function (response) {
            $scope.listPengajuan = response.data;
        });
    };
    $scope.update = function (form) {
        $scope.is_copy = false;
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_create = false;
        $scope.formtittle = master + " | Edit Data : " + form.no_proposal;
        $scope.form = form;
        $scope.getDetail(form.id);
        $scope.getAcc(form.id);
        $scope.form.tanggal = new Date(form.tanggal);

    };
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.formtittle = master + " | Lihat Data : " + form.no_proposal;
        $scope.form = form;
        $scope.getDetail(form.id);
        $scope.getAcc(form.id);
        $scope.form.tanggal = new Date(form.tanggal);
    };
    $scope.save = function (form) {
        $scope.loading = true;
        var form = {
            data: form,
            detail: $scope.listDetail,
            acc: $scope.listAcc
        }
        Data.post("acc/apppengajuan/save", form).then(function (result) {
            if (result.status_code == 200) {
                $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
                $scope.cancel();
            } else {
                $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
            }
            $scope.loading = false;
        });
    };
    $scope.cancel = function () {
        $scope.is_edit = false;
        $scope.is_view = false;
        $scope.is_create = false;
        $scope.callServer(tableStateRef);
    };
    $scope.delete = function (row) {
        if (confirm("Apa anda yakin akan Menghapus item ini ?")) {
            row.is_deleted = 0;
            Data.post("acc/apppengajuan/hapus", row).then(function (result) {
                $scope.displayed.splice($scope.displayed.indexOf(row), 1);
            });
        }
    };
    $scope.getPengajuan = function (no_proposal) {
        Data.get("acc/apppengajuan/getAll?id=" + no_proposal.id).then(function (response) {
            $scope.form = response.data[0];
            $scope.form.tanggal = new Date($scope.form.tanggal)
            $scope.getDetail($scope.form.id)
            $scope.form.no_proposal = "";
            $scope.form.id = "";
            $scope.tersalin_dari = no_proposal;
        });
    }
    $scope.print = function (row) {
        window.open("api/acc/apppengajuan/printPengajuan?" + $.param(row), "_blank");
    }
    /**
     * Modal setting template print
     */
    $scope.modalSetting = function () {
        var modalInstance = $uibModal.open({
            templateUrl: $scope.url.base_url + "api/" + $scope.url.acc_dir + "/tpl/t_pengajuan/modal.html",
            controller: "settingPrintCtrl",
            size: "xl",
            backdrop: "static",
            keyboard: false,
        });
        modalInstance.result.then(function (response) {
            if (response.data == undefined) {
            } else {
            }
        });
    }
    /**
     * Modal setting template print
     */
    $scope.modalWhatsapp = function (form) {
        var modalInstance = $uibModal.open({
            templateUrl: $scope.url.base_url + "api/" + $scope.url.acc_dir + "/tpl/t_pengajuan/whatsapp.html",
            controller: "whatsappCtrl",
            size: "lg",
            backdrop: "static",
            keyboard: false,
            resolve: {
                form: form,
            }
        });
        modalInstance.result.then(function (response) {
            if (response.data == undefined) {
            } else {
            }
        });
    }
    $scope.modalDetail = function (form, index) {
        var modalInstance = $uibModal.open({
            templateUrl: $scope.url.base_url + "api/" + $scope.url.acc_dir + "/tpl/t_pengajuan/detail.html",
            controller: "modalDetailCtrl",
            size: "lg",
            backdrop: "static",
            keyboard: false,
            resolve: {
                form: form,
                data: {
                    is_view: $scope.is_view,
                    status: $scope.form.status
                }
            }
        })
        modalInstance.result.then(function (response) {
            if (response.detail.length > 0) {
                form.detail = response.detail;
            }
            form.harga_satuan = response.total;
            form.jumlah = 1;
            $scope.sumTotal();
        })
    }
});
app.controller("settingPrintCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope) {
    $scope.templateDefault = "";
    Data.get("acc/apppengajuan/getTemplate").then(function (response) {
        $scope.templateDefault = response.data;
    });
    $scope.close = function () {
        $uibModalInstance.close({
            'data': undefined
        });
    };
    $scope.save = function () {
        var ckeditor_data = CKEDITOR.instances.editor1.getData();
        var params = {
            print_pengajuan: ckeditor_data
        };
        Data.post("acc/apppengajuan/saveTemplate", params).then(function (result) {
            if (result.status_code == 200) {
                $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
                $scope.close();
            } else {
                $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
            }
        });
    }
});
app.controller("whatsappCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form) {
    $scope.form = {};
    Data.get("acc/appuser/getAll").then(function (result) {
        $scope.listUser = result.data;
    });
    $scope.data = form;
    $scope.form.pesan = $scope.data.no_proposal + "\nNama Kegiatan : " + $scope.data.dasar_pengajuan + "\nTotal Biaya : " + $scope.data.jumlah_perkiraan + "\nTanggal : " + $scope.data.tanggal + "\n+++++++++++++++++++++++\nPenerima : " + $scope.data.penerima + "\nNo. Rekening : " + $scope.data.norek + "\nCatatan :";
    $scope.close = function () {
        $uibModalInstance.close({
            'data': undefined
        });
    };
    $scope.send = function (form) {
        var telp = 0;
        if (form.penerima.telepon.charAt(0) == 0) {
            telp = form.penerima.telepon.replace(form.penerima.telepon.charAt(0), 62);
        } else {
            telp = form.penerima.telepon;
        }
        window.open("https://wa.me/" + telp + "?text=" + encodeURIComponent(form.pesan), '_blank');
    }
});
app.controller("modalDetailCtrl", function ($state, $scope, Data, $uibModalInstance, $rootScope, form, data) {
    $scope.form = {};
    $scope.form = form;
    $scope.is_view = data.is_view;
    $scope.status = data.status;
    $scope.lihatDariApprove = false;
    $scope.listDetail = [];
    if ($scope.form.detail != undefined) {
        $scope.listDetail = $scope.form.detail;
    }
    $scope.addDetail = function (val) {
        var comArr = eval(val);
        var newDet = {
            keterangan: "",
            budget: 0,
            realisasi: 0
        };
        val.push(newDet);
    };
    $scope.removeDetail = function (val, paramindex) {
        var comArr = eval(val);
        val.splice(paramindex, 1);
        $scope.sumTotal();
    };
    $scope.sumTotal = function () {
        var total = 0;
        angular.forEach($scope.listDetail, function (value, key) {
            if (value.budget === undefined) {
                value.budget = 0;
            }
            if (value.realisasi === undefined) {
                value.realisasi = 0;
            }
            total += value.budget;
        });
        $scope.form.sub_total = total;
    };
    $scope.save = function () {
        if ($scope.status == "Terbayar" || $scope.status == "Approved") {
            Data.post("acc/apppengajuan/saveDetail", $scope.listDetail).then(function (result) {
                if (result.status_code == 200) {
                    $rootScope.alert("Berhasil", "Data berhasil disimpan", "success");
                    $scope.close();
                } else {
                    $rootScope.alert("Terjadi Kesalahan", setErrorMessage(result.errors), "error");
                }
            });
        } else {
            $scope.close();
        }
    }
    $scope.close = function () {
        $uibModalInstance.close({
            detail: $scope.listDetail,
            total: $scope.form.sub_total
        });
    };
});