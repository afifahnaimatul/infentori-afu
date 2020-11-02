app.controller('barangCtrl', function ($scope, Data, $rootScope, $uibModal, Upload, FileUploader) {
    var tableStateRef;
    var control_link = "m_barang";
    var master = 'Master Barang';
    $scope.formTitle = '';
    $scope.displayed = [];
    $scope.is_edit = false;
    $scope.is_view = false;
    $scope.master = master;


    $scope.callServer = function callServer(tableState) {
//        tableState = $scope.tableState != undefined ? $scope.tableState : tableState;
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
//        if (param['filter']['acc_m_lokasi_id'] == null) {
//            param['filter']['acc_m_lokasi_id'] = '1';
//        }



        Data.get(control_link + '/index', param).then(function (response) {
            $scope.displayed = response.data.list;
            tableState.pagination.numberOfPages = Math.ceil(response.data.totalItems / limit);
        });
        $scope.isLoading = false;
    };

    $scope.detAlternatif = [{
            no: ""
        }];
    $scope.addDetail = function (newdet = {no:''}) {
        var val = $scope.detAlternatif.length;
        var newDet = newdet;
        $scope.detAlternatif.push(newDet);
    };

    $scope.removeRow = function (paramindex) {
        $scope.detAlternatif.splice(paramindex, 1);
        $scope.total();
    };

    /** create */
    $scope.create = function () {
        $scope.tableState = tableStateRef;
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_create = true;
        $scope.is_disable = false;
        $scope.formtitle = master + " | Form Tambah Data";
        $scope.form = {};
        $scope.detAlternatif = [{
                no: ""
            }];
        $scope.form.type_barcode = 'non serial';
        $scope.form.konsinyasi = 0;
        $scope.form.type = "barang";
        $scope.form.harga_pokok = "fifo";
//        $scope.kodeBR();
    };
    /** update */
    $scope.update = function (form) {
        $scope.is_edit = true;
        $scope.is_view = false;
        $scope.is_update = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Edit Data : " + form.kode + " - " + form.nama;
        $scope.form = form;
        $scope.getDetail(form);
    };
    /** view */
    $scope.view = function (form) {
        $scope.is_edit = true;
        $scope.is_view = true;
        $scope.is_disable = true;
        $scope.formtitle = master + " | Lihat Data : " + form.kode + " - " + form.nama;
        $scope.form = form;
        $scope.getDetail(form);
    };

    /** save action */
    $scope.save = function (form) {
        var params = {
            form: form,
            detail: $scope.detAlternatif
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
        $scope.is_edit = false;
        $scope.is_view = false;
        if (!$scope.is_view) {
            $scope.callServer(tableStateRef);
        }
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

    /* CRUD - END */
    Data.get('acc/m_lokasi/index').then(function (response) {
        $scope.listLokasi = response.data.list;

    });

    Data.get('m_kategori/index', {filter: {"inv_m_kategori.is_deleted": 0, "inv_m_kategori.is_parent": 0}}).then(function (response) {
        $scope.listKategori = response.data.list;
    });

    Data.get('m_satuan/index', {filter: {is_deleted: 0}}).then(function (response) {
        $scope.listSatuan = response.data.list;
    });

    $scope.getminmax = function (min, max, bc) {
        var bar = bc.length;

        if (bar >= min && bar <= max) {
        } else {

            $rootScope.alert("Terjadi kesalahan", "Panjang barcode harus sesuai", "error");
        }
    };

    $scope.kodeBR = function () {
        Data.get(control_link + "/kodeBarang").then(function (data) {
            $scope.form.kode = data.data.kode;
        });
    };

    $scope.getDetail = function (form) {
        Data.get(control_link + "/view/" + form.id).then(function (data) {
            $scope.images = data.data.images;
            $scope.path = data.data.path;
            $scope.pathImg = "produk/";
            $scope.detAlternatif = data.data.alternatif;
        });
    };



    // Upload Gambar ================
    var imgView = function (img) {
        return "img/produk/" + img;
    };
    var uploader = ($scope.uploader = new FileUploader({
        url: Data.base + control_link + "/upload/produk",
        formData: [],
        removeAfterUpload: true
    }));
    $scope.uploadGambar = function (form) {
        $scope.uploader.uploadAll();
    };
    uploader.filters.push({
        name: "imageFilter",
        fn: function (item) {
            var type = "|" + item.type.slice(item.type.lastIndexOf("/") + 1) + "|";
            var x = "|jpg|png|jpeg|bmp|gif|".indexOf(type) !== -1;
            if (!x) {

                $rootScope.alert("Terjadi kesalahan", "Jenis gambar tidak sesuai", "error");
            }
            return x;
        }
    });
    uploader.filters.push({
        name: "sizeFilter",
        fn: function (item) {
            var xz = item.size < 2097152;
            if (!xz) {
                $rootScope.alert("Terjadi kesalahan", "Ukuran gambar tidak boleh lebih dari 2 MB", "error");
            }
            return xz;
        }
    });
    $scope.images = [];
    uploader.onSuccessItem = function (fileItem, response) {
        if (response.answer == "File transfer completed") {
            $scope.images.unshift({
                img: imgView(response.img),
                id: response.id
            });
        }
    };
    uploader.onBeforeUploadItem = function (item) {
        item.formData.push({
            id: $scope.form.id
        });
    };
    $scope.removeFoto = function (paramindex, namaFoto, pid) {
        Data.post(control_link + "/removegambar", {
            id: pid,
            img: namaFoto
        }).then(function (data) {
            $scope.images.splice(paramindex, 1);
        });
    };
    $scope.gambarzoom = function (img) {
        var modalInstance = $uibModal.open({
            template: '<center><img src="./' + img + '" class="img-responsive" width="300dp" ></center>',
            size: "xs"
        });
    };



});
