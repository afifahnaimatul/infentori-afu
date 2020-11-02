app.controller('l_template_penjualan', function ($scope, Data, $rootScope, $uibModal, Upload) {
    var control_link = "l_penjualan_perpembeli";
    $scope.form = {
        jenis: 'semua'
    };
    $scope.header_tanggal = false;
    $scope.form.tanggal = {
        endDate: moment().add(1, 'M'),
        startDate: moment()
    };
    /**
     * Ambil list lokasi
     */
    Data.get('acc/m_lokasi/getLokasi').then(function (response) {
        $scope.listLokasi = response.data.list;
        if ($scope.listLokasi.length > 0) {
            $scope.form.m_lokasi_id = $scope.listLokasi[0];
        }
    });
    // Data.get('l_penjualan_perpembeli/listbarang').then(function(response) {
    //     $scope.getBarang = response.data;
    //     if ($scope.getBarang.length > 0) {
    //         $scope.form.barang_id = $scope.getBarang[0];
    //     }
    //     console.log($scope.getBarang);
    // });

    $scope.cariBarang = function ($query) {

        if ($query.length >= 3) {
            Data.get('l_penjualan_perpembeli/listbarang', {
                'nama': $query
            }).then(function (response) {
                $scope.getBarang = response.data;
            });
        }
    };
    $scope.cariPembeli = function ($query) {

        if ($query.length >= 3) {
            Data.get('t_penjualan/customer', {'nama': $query}).then(function (response) {
                $scope.listCustomer = response.data;
            });
        }
    };
    /**
     * Ambil laporan dari server
     */
    $scope.view = function (is_export, is_print) {

        // if ($scope.form.barang_id == undefined) {
        //     $rootScope.alert("Terjadi Kesalahan","Anda belum memilih Barang", "error");
        //     return false;
        // }

        $scope.mulai = moment($scope.form.tanggal.startDate).format('DD-MM-YYYY');
        $scope.selesai = moment($scope.form.tanggal.endDate).format('DD-MM-YYYY');
        // console.log($scope.form.customer_id.id);
        $scope.form.cst_id = 0;
        $scope.form.brg_id = 0;
        if (typeof $scope.form.customer_id !== 'undefined') {

            $scope.form.cst_id = $scope.form.customer_id;
        }
        if (typeof $scope.form.barang_id !== 'undefined') {

            $scope.form.brg_id = $scope.form.barang_id;
        }


        $scope.idx = [];
        angular.forEach($scope.example14model, function (isi, key) {
            $scope.idx.push(isi.id);
        });
        var param = {
            export: is_export,
            print: is_print,
            path : 'template_penjualan',
            acc_m_lokasi_id: $scope.form.m_lokasi_id.id,
            m_customer_id: $scope.form.cst_id.id,
            header: Object.assign({}, $scope.idx),
            barang_id: $scope.form.brg_id.id,
            jenis: $scope.form.jenis,
            startDate: moment($scope.form.tanggal.startDate).format('YYYY-MM-DD'),
            endDate: moment($scope.form.tanggal.endDate).format('YYYY-MM-DD'),
            show_kartu: true,
        };
        if (is_export == 0 && is_print == 0) {
            Data.get(control_link + '/laporan', param).then(function (response) {
                if (response.status_code == 200) {
                    $scope.data = response.data.data;
                    $scope.total = response.data.total;
                    $scope.tanggal_mulai = response.data.tgl_mulai;
                    $scope.tanggal_selesai = response.data.tgl_selesai;
                    $scope.header = response.data.header;
                    $scope.panjang_header = response.data.panjang_header;
                    $scope.panjang_pembatas = response.data.panjang_pembatas;
                    console.log($scope.panjang_header);
                    $scope.tampilkan = true;
                } else {
                    $scope.tampilkan = false;
                }
            });
        } else {
            Data.get('site/base_url').then(function (response) {
//                console.log(response)
                window.open(response.data.base_url + "api/l_penjualan_perpembeli/laporan?" + $.param(param), "_blank");
            });
        }
    };
    $scope.example14model = [];
    $scope.setting1 = {
        scrollableHeight: '300px',
        scrollable: true,
        enableSearch: true,
        buttonClasses: 'form-control form-control-sm align-left'
    };
    $scope.example14data = [
        {
            "label": "No",
            "id": "no"
        }, {
            "label": "Tanggal",
            "id": "tanggal"
        }, {
            "label": "Tanggal Faktur Pajak",
            "id": "tanggal_faktur_pajak"
        },
//    {
//        "label": "Tanggal Penyerahan",
//        "id": "tanggal_penyerahan"
//    },
        {
            "label": "Nomor Surat Jalan",
            "id": "no_surat_jalan"
        },
//    {
//        "label": "Nomor Nota",
//        "id": "no_nota"
//    },
        {
            "label": "Nomor Faktur",
            "id": "no_faktur"
        }, {
            "label": "Pembeli",
            "id": "pembeli"
        }, {
            "label": "NPWP",
            "id": "npwp"
        }, {
            "label": "Barang",
            "id": "barang"
        }, {
            "label": "KWT",
            "id": "kwt"
        }, {
            "label": "Harga Satuan",
            "id": "harga_satuan"
        }, {
            "label": "Nilai",
            "id": "nilai"
        }, {
            "label": "DPP",
            "id": "dpp"
        }, {
            "label": "PPN",
            "id": "ppn"
        }, {
            "label": "Jumlah",
            "id": "jumlah"
        },
                //  {
                //     "label": "Tanggal Bayar",
                //     "id": "tanggal_bayar"
                // }
    ];
});
