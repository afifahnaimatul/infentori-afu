angular.module("app").config(["$stateProvider", "$urlRouterProvider", "$ocLazyLoadProvider", "$breadcrumbProvider",
    function ($stateProvider, $urlRouterProvider, $ocLazyLoadProvider, $breadcrumbProvider) {
        $urlRouterProvider.otherwise("/dashboard");
        $ocLazyLoadProvider.config({
            debug: false
        });
        $breadcrumbProvider.setOptions({
            prefixStateName: "app.main",
            includeAbstract: true,
            template: '<li class="breadcrumb-item" ng-repeat="step in steps" ng-class="{active: $last}" ng-switch="$last || !!step.abstract"><a ng-switch-when="false" href="{{step.ncyBreadcrumbLink}}">{{step.ncyBreadcrumbLabel}}</a><span ng-switch-when="true">{{step.ncyBreadcrumbLabel}}</span></li>'
        });

        var time = new Date().getTime();

        $stateProvider.state("app", {
            abstract: true,
            templateUrl: "tpl/common/layouts/full.html?time=" + time,
            ncyBreadcrumb: {
                label: "Root",
                skip: true
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load([]);
                    }
                ],
            }
        }).state("app.main", {
            url: "/dashboard",
            templateUrl: "tpl/dashboard/dashboard.html?time=" + time,
            ncyBreadcrumb: {
                label: "Home"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["chart.js"]).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/dashboard/dashboard.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("app.laporan", {
            url: "/laporan",
            templateUrl: "tpl/laporan/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Laporan"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, cache: false, files: ["tpl/laporan/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("app.master", {
            url: "/master",
            templateUrl: "tpl/master/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Master"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["tpl/master/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("app.generator", {
            url: "/generator",
            templateUrl: "tpl/generator/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Home"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["chart.js"]).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/generator/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("app.monitoring_pengajuan", {
            url: "/monitoring-pengajuan",
            templateUrl: "tpl/t_monitoring_pengajuan/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Monitoring Pengajuan"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/t_monitoring_pengajuan/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan", {
            abstract: true,
            templateUrl: "tpl/common/layouts/full.html?time=" + time,
            ncyBreadcrumb: {
                label: "Laporan"
            },
            resolve: {

                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load([]);
                    }
                ],
                loadPlugin: ["$ocLazyLoad", function ($ocLazyLoad) {
                    }],
                authenticate: authenticate
            }
        }).state("laporan.hutang", {
            url: "/l_hutang",
            templateUrl: "api/acc/landaacc/tpl/l_hutang/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Hutang"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/l_hutang/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rekap_hutang", {
            url: "/l_rekap_hutang",
            templateUrl: "api/acc/landaacc/tpl/l_rekap_hutang/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rekap Hutang"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/l_rekap_hutang/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.piutang", {
            url: "/l_piutang",
            templateUrl: "api/acc/landaacc/tpl/l_piutang/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Piutang"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/l_piutang/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rekap_piutang", {
            url: "/l_rekap_piutang",
            templateUrl: "api/acc/landaacc/tpl/l_rekap_piutang/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rekap Piutang"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/l_rekap_piutang/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.budgeting", {
            url: "/l_budgeting",
            templateUrl: "api/acc/landaacc/tpl/l_budgeting/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Budgeting"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/l_budgeting/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.neraca_saldo", {
            url: "/l_neracasaldo",
            templateUrl: "api/acc/landaacc/tpl/l_neraca_saldo/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Neraca Saldo"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/l_neraca_saldo/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.neraca", {
            url: "/l_neraca",
            templateUrl: "api/acc/landaacc/tpl/l_neraca/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Posisi Keuangan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/l_neraca/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.laba_rugi", {
            url: "/l_labarugi",
            templateUrl: "api/acc/landaacc/tpl/l_laba_rugi/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Laba Rugi"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/l_laba_rugi/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.arus_kas", {
            url: "/l_aruskas",
            templateUrl: "api/acc/landaacc/tpl/l_arus_kas/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Arus Kas"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/l_arus_kas/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.jurnal_umum", {
            url: "/l_jurnalumum",
            templateUrl: "api/acc/landaacc/tpl/l_jurnal_umum/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Jurnal Umum"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/l_jurnal_umum/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.buku_besar", {
            url: "/l_bukubesar?:akun",
            templateUrl: "api/acc/landaacc/tpl/l_buku_besar/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Buku Besar"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/l_buku_besar/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.kartu_stok", {
            url: "/kartu_stok",
            templateUrl: "tpl/inventori/l_kartu_stok/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Satuan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_kartu_stok/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.faktur_sederhana", {
            url: "/faktur_sederhana",
            templateUrl: "tpl/inventori/l_faktur_sederhana/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Faktur Sederhana"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_faktur_sederhana/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.faktur_standart", {
            url: "/faktur-standart",
            templateUrl: "tpl/inventori/l_faktur_standart/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Faktur Standart"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_faktur_standart/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.retur_penjualan", {
            url: "/laporan-retur-penjualan",
            templateUrl: "tpl/inventori/l_retur_penjualan/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Laporan Retur Penjualan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_retur_penjualan/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.retur_pembelian", {
            url: "/laporan-retur-pembelian",
            templateUrl: "tpl/inventori/l_retur_pembelian/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Laporan Retur Pembelian"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_retur_pembelian/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.penjualan_perbarang", {
            url: "/penjualan_perbarang",
            templateUrl: "tpl/inventori/l_penjualan_perbarang/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Penjualan Perbarang"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_penjualan_perbarang/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.stok_barang_dagang", {
            url: "/stok-barang-dagang",
            templateUrl: "tpl/inventori/l_stok_barang_dagang/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Stok Barang Dagangan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_stok_barang_dagang/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.harga_barang_dagang", {
            url: "/harga-barang-dagang",
            templateUrl: "tpl/inventori/l_harga_barang_dagang/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Stok Barang Dagangan (Dengan Harga)"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_harga_barang_dagang/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.penjualan_perpembeli", {
            url: "/penjualan_perpembeli",
            templateUrl: "tpl/inventori/l_penjualan_perpembeli/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Penjualan Perpembeli"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_penjualan_perpembeli/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.template_penjualan", {
            url: "/template-penjualan",
            templateUrl: "tpl/inventori/l_template_penjualan/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Template Penjualan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_template_penjualan/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.penjualan", {
            url: "/laporan-penjualan",
            templateUrl: "tpl/inventori/l_penjualan/penjualan.html?time=" + time,
            ncyBreadcrumb: {
                label: "Penjualan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_penjualan/penjualan.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rekap_penjualan", {
            url: "/rekap-penjualan",
            templateUrl: "tpl/inventori/l_rekap_penjualan/rekap_penjualan.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rekap Penjualan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_rekap_penjualan/rekap_penjualan.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rekap_penjualan_pertahun", {
            url: "/rekap-penjualan-pertahun",
            templateUrl: "tpl/inventori/l_rekap_penjualan_pertahun/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rekap Penjualan per Tahun"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_rekap_penjualan_pertahun/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rekap_penjualan_pertahun_detail", {
            url: "/rekap-penjualan-pertahun-detail",
            templateUrl: "tpl/inventori/l_rekap_penjualan_pertahun_detail/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rekap Penjualan per Tahun Detail"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_rekap_penjualan_pertahun_detail/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.pembelian_perbulan", {
            url: "/pembelian-per-bulan",
            templateUrl: "tpl/inventori/l_pembelian_perbulan/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Pembelian per Bulan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_pembelian_perbulan/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.pembelian_import", {
            url: "/l-pembelian-import",
            templateUrl: "tpl/inventori/l_pembelian_import/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Pembelian Import"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_pembelian_import/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.pembelian_import_ppn", {
            url: "/l-pembelian-import-ppn",
            templateUrl: "tpl/inventori/l_pembelian_import_ppn/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Pembelian Import PPN"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_pembelian_import_ppn/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.pembelian_import_pph", {
            url: "/l-pembelian-import-pph",
            templateUrl: "tpl/inventori/l_pembelian_import_pph/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Pembelian Import PPh22"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_pembelian_import_pph/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rekap_pembelian", {
            url: "/rekap-pembelian",
            templateUrl: "tpl/inventori/l_rekap_pembelian/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rekap Pembelian"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_rekap_pembelian/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rekap_pembelian_perbulan", {
            url: "/rekap-pembelian-perbulan",
            templateUrl: "tpl/inventori/l_rekap_pembelian_perbulan/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rekap Pembelian per Bulan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_rekap_pembelian_perbulan/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rekap_pembelian_pertahun", {
            url: "/rekap-pembelian-pertahun",
            templateUrl: "tpl/inventori/l_rekap_pembelian_pertahun/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rekap Pembelian per Tahun"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/inventori/l_rekap_pembelian_pertahun/index.js?time=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("customer", {
            abstract: true,
            templateUrl: "tpl/common/layouts/full.html?time=" + time,
            ncyBreadcrumb: {
                label: "Customer"
            },
            resolve: {

                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load([]);
                    }
                ],
                loadPlugin: ["$ocLazyLoad", function ($ocLazyLoad) {
                    }],
                authenticate: authenticate
            }
        }).state("customer.saldo_awal_piutang", {
            url: "/saldo_awal_piutang",
            templateUrl: "api/acc/landaacc/tpl/t_saldo_awal_piutang/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Saldo Awal Piutang"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/t_saldo_awal_piutang/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("transaksi.saldo_awal_hutang_persupplier", {
            url: "/hutang-supplier",
            templateUrl: "api/acc/landaacc/tpl/t_saldo_awal_hutang_persupplier/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Saldo Awal Hutang"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/t_saldo_awal_hutang_persupplier/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("transaksi.pembayaran_hutang_persupplier", {
            url: "/bayar-hutang-supplier",
            templateUrl: "tpl/acc/t_pembayaran_hutang/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Pembayaran Hutang"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/acc/t_pembayaran_hutang/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("transaksi.saldo_awal_piutang_percustomer", {
            url: "/piutang-customer",
            templateUrl: "api/acc/landaacc/tpl/t_saldo_awal_piutang_percustomer/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Saldo Awal Piutang"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/t_saldo_awal_piutang_percustomer/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("transaksi.pembayaran_piutang_percustomer", {
            url: "/bayar-piutang-customer",
            templateUrl: "tpl/acc/t_pembayaran_piutang/bayar_piutang.html?time=" + time,
            ncyBreadcrumb: {
                label: "Pembayaran Piutang"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/acc/t_pembayaran_piutang/bayar_piutang.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rincian_biaya", {
            url: "/rincian-biaya",
            templateUrl: "tpl/acc/l_rincian_biaya/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rincian Biaya"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/acc/l_rincian_biaya/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rekapan_pusat", {
            url: "/rekapan-pusat",
            templateUrl: "tpl/acc/l_rekapan_pusat/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rekapan Pusat"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/acc/l_rekapan_pusat/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rekapan_depo", {
            url: "/rekapan-depo",
            templateUrl: "tpl/acc/l_rekapan_depo/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rekapan Depo"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/acc/l_rekapan_depo/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rekapan_aliran_hutang", {
            url: "/rekapan-aliran-hutang",
            templateUrl: "tpl/acc/l_rekapan_aliran_hutang/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rekapan Aliran Hutang"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/acc/l_rekapan_aliran_hutang/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("laporan.rekapan_aliran_piutang", {
            url: "/rekapan-aliran-piutang",
            templateUrl: "tpl/acc/l_rekapan_aliran_piutang/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Rekapan Aliran Piutang"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["tpl/acc/l_rekapan_aliran_piutang/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        })

                /* Transaksi Inventori */
                .state("transaksi.stok_opname", {
                    url: "/stok-opname",
                    templateUrl: "tpl/inventori/t_stok_opname/stok_opname.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Penyesuaian Persediaan"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_stok_opname/stok_opname.js?t=" + time]
                                });
                            }
                        ]
                    }
                })
                /* Transaksi Inventori - End */

                .state("customer.customer", {
                    url: "/customer",
                    templateUrl: "api/acc/landaacc/tpl/m_customer/index.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Customer"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["api/acc/landaacc/tpl/m_customer/index.js?t=" + time]
                                });
                            }
                        ]
                    }
                }).state("supplier", {
            abstract: true,
            templateUrl: "tpl/common/layouts/full.html?time=" + time,
            ncyBreadcrumb: {
                label: "Supplier"
            },
            resolve: {

                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load([]);
                    }
                ],
                loadPlugin: ["$ocLazyLoad", function ($ocLazyLoad) {
                    }],
                authenticate: authenticate
            }
        }).state("supplier.supplier", {
            url: "/supplier",
            templateUrl: "api/acc/landaacc/tpl/m_supplier/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Supplier"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_supplier/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("master", {
            abstract: true,
            templateUrl: "tpl/common/layouts/full.html?time=" + time,
            ncyBreadcrumb: {
                label: "Master"
            },
            resolve: {

                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load([]);
                    }
                ],
                loadPlugin: ["$ocLazyLoad", function ($ocLazyLoad) {
                    }],
                authenticate: authenticate
            }
        }).state("master.formatkode", {
            url: "/format",
            templateUrl: "api/acc/landaacc/tpl/m_format_kode/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Format Kode"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_format_kode/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("master.customer", {
            url: "/customer",
            templateUrl: "api/acc/landaacc/tpl/m_customer/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Customer"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_customer/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("master.setting_approval", {
            url: "/settingapproval",
            templateUrl: "api/acc/landaacc/tpl/m_setting_approval/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Setting Approval"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_setting_approval/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("master.akun", {
            url: "/akun",
            templateUrl: "api/acc/landaacc/tpl/m_akun/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Akun"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/m_akun/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("master.asset", {
            url: "/asset",
            templateUrl: "api/acc/landaacc/tpl/m_asset/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Asset"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_asset/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("master.umur_ekonomis", {
            url: "/Umur-Ekonomis",
            templateUrl: "api/acc/landaacc/tpl/m_umur_ekonomis/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Umur Ekonomis"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_umur_ekonomis/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("master.lokasi", {
            url: "/lokasi",
            templateUrl: "api/acc/landaacc/tpl/m_lokasi/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Lokasi"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_lokasi/index.js?t=" + time]
                        });
                    }
                ]
            }
        })
                // Master Inventori
                .state("master.satuan", {
                    url: "/satuan",
                    templateUrl: "tpl/inventori/m_satuan/satuan.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Satuan"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/m_satuan/satuan.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("master.kategori", {
                    url: "/kategori-barang",
                    templateUrl: "tpl/inventori/m_kategori/index.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Kategori Barang"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/m_kategori/index.js?time=" + time]
                                });
                            }
                        ]
                    }
                }).state("master.inv_faktur_penjualan", {
            url: "/faktur-penjualan",
            templateUrl: "tpl/inventori/m_faktur_penjualan/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Faktur Penjualan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["tpl/inventori/m_faktur_penjualan/index.js?time=" + time]
                        });
                    }
                ]
            }
        }).state("master.inv_faktur_pembelian", {
            url: "/faktur-pembelian",
            templateUrl: "tpl/inventori/m_faktur_pembelian/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Faktur Pembelian"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["tpl/inventori/m_faktur_pembelian/index.js?time=" + time]
                        });
                    }
                ]
            }
        }).state("master.inv_pelabuhan", {
            url: "/penyedia-pelabuhan",
            templateUrl: "tpl/inventori/m_pelabuhan/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Penyedia Jasa"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["tpl/inventori/m_pelabuhan/index.js?time=" + time]
                        });
                    }
                ]
            }
        }).state("master.proses_akhir", {
            url: "/proses-akhir",
            templateUrl: "tpl/inventori/t_proses_akhir/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Proses Akhir"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["tpl/inventori/t_proses_akhir/index.js?time=" + time]
                        });
                    }
                ]
            }
        }).state("master.jenis", {
            url: "/jenis",
            templateUrl: "tpl/inventori/m_jenis/jenis.html?time=" + time,
            ncyBreadcrumb: {
                label: "Jenis"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["tpl/inventori/m_jenis/jenis.js?time=" + time]
                        });
                    }
                ]
            }
        })
                .state("master.barang", {
                    url: "/barang",
                    templateUrl: "tpl/inventori/m_barang/barang.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Barang"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload", "angularFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/m_barang/barang.js?time=" + time]
                                });
                            }
                        ]
                    }
                }).state("master.inv_lokasi", {
            url: "/inv_lokasi",
            templateUrl: "tpl/inventori/m_lokasi/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Lokasi"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload", "angularFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["tpl/inventori/m_lokasi/index.js?time=" + time]
                        });
                    }
                ]
            }
        }).state("master.inv_customer", {
            url: "/inv_customer",
            templateUrl: "tpl/inventori/m_customer/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Customer"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload", "angularFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["tpl/inventori/m_customer/index.js?time=" + time]
                        });
                    }
                ]
            }
        }).state("master.inv_supplier", {
            url: "/inv_supplier",
            templateUrl: "tpl/inventori/m_supplier/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Supplier"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload", "angularFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["tpl/inventori/m_supplier/index.js?time=" + time]
                        });
                    }
                ]
            }
        })
                .state("master.inv_f_pajak_pelabuhan", {
                    url: "/inv-fp-pelabuhan",
                    templateUrl: "tpl/inventori/m_faktur_pelabuhan/faktur_pelabuhan.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Faktur Pajak Pelabuhan"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload", "angularFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/m_faktur_pelabuhan/faktur_pelabuhan.js?time=" + time]
                                });
                            }
                        ]
                    }
                })

                // ACC AFU
                .state("master.acc_akun_peta", {
                    url: "/acc-akun-peta",
                    templateUrl: "tpl/inventori/m_akun_peta/m_akun_peta.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Pemetaan Akun"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload", "angularFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/m_akun_peta/m_akun_peta.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                // ACC AFU - END

                // Master Inventori - END
                .state("transaksi_pembelian", {
                    abstract: true,
                    templateUrl: "tpl/common/layouts/full.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Transaksi"
                    },
                    resolve: {

                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([]);
                            }
                        ],
                        loadPlugin: ["$ocLazyLoad", function ($ocLazyLoad) {
                            }],
                        authenticate: authenticate
                    }
                })
                // Transaksi Inventori
                .state("transaksi_pembelian.pembelian", {
                    url: "/pembelian",
                    params: {
                        is_import: 0
                    },
                    templateUrl: "tpl/inventori/t_pembelian/pembelian.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Pembelian Barang"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_pembelian/pembelian.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("transaksi_pembelian.pembelian_import", {
                    url: "/pembelian-import",
                    params: {
                        is_import: 1
                    },
                    templateUrl: "tpl/inventori/t_pembelian/pembelian.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Pembelian Barang"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_pembelian/pembelian.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("transaksi_pembelian.pembelian_hutang", {
                    url: "/pembelian-hutang",
                    params: {
                        is_hutang: 1
                    },
                    templateUrl: "tpl/inventori/t_pembelian_hutang/hutang.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Saldo Awal Hutang"
                    },
                    resolve: {
                      loadCSS: ["$ocLazyLoad",
                          function ($ocLazyLoad) {
                              return $ocLazyLoad.load(["ngFileUpload"]);
                          }
                      ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_pembelian_hutang/hutang.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("transaksi_pembelian.po_pembelian", {
                    url: "/po-pembelian",
                    templateUrl: "tpl/inventori/t_po_pembelian/po_pembelian.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Pembelian PO Barang"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_po_pembelian/po_pembelian.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("transaksi_pembelian.retur_pembelian", {
                    url: "/retur-pembelian",
                    templateUrl: "tpl/inventori/t_retur_pembelian/retur_pembelian.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Retur Pembelian"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_retur_pembelian/retur_pembelian.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("transaksi_penjualan", {
                    abstract: true,
                    templateUrl: "tpl/common/layouts/full.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Transaksi"
                    },
                    resolve: {

                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([]);
                            }
                        ],
                        loadPlugin: ["$ocLazyLoad", function ($ocLazyLoad) {
                            }],
                        authenticate: authenticate
                    }
                })
                .state("transaksi_penjualan.penjualan", {
                    url: "/penjualan",
                    templateUrl: "tpl/inventori/t_penjualan/penjualan.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Penjualan Barang"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_penjualan/penjualan.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("transaksi_penjualan.input_faktur_penjualan", {
                    url: "/input-faktur-penjualan",
                    templateUrl: "tpl/inventori/t_input_faktur_penjualan/index.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Faktur Pajak penjualan"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_input_faktur_penjualan/index.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("transaksi_penjualan.pengajuan_penjualan", {
                    url: "/pengajuan-penjualan",
                    templateUrl: "tpl/inventori/t_pengajuan_penjualan/p_penjualan.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Pengajuan Penjualan"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_pengajuan_penjualan/p_penjualan.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("transaksi_penjualan.saldo_awal_piutang", {
                    url: "/saldo-awal-piutang",
                    templateUrl: "tpl/inventori/t_pembelian_piutang/saldo_awal_piutang.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Saldo Awal Piutang"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_pembelian_piutang/saldo_awal_piutang.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("transaksi_penjualan.retur_penjualan", {
                    url: "/retur-penjualan",
                    params: {
                        form: null
                    },
                    templateUrl: "tpl/inventori/t_retur_penjualan/retur_penjualan.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Retur Penjualan"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_retur_penjualan/retur_penjualan.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("transaksi.transfer_barang", {
                    url: "/transfer-barang",
                    params: {
                        transfer: true
                    },
                    templateUrl: "tpl/inventori/t_transfer_barang/index.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Transfer Barang"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_transfer_barang/index.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("transaksi.terima_barang", {
                    url: "/terima-barang",
                    params: {
                        transfer: false
                    },
                    templateUrl: "tpl/inventori/t_transfer_barang/index.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Terima Barang"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/t_transfer_barang/index.js?time=" + time]
                                });
                            }
                        ]
                    }
                })
                // Transaksi Inventori - END
                .state("transaksi", {
                    abstract: true,
                    templateUrl: "tpl/common/layouts/full.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Transaksi"
                    },
                    resolve: {

                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load([]);
                            }
                        ],
                        loadPlugin: ["$ocLazyLoad", function ($ocLazyLoad) {
                            }],
                        authenticate: authenticate
                    }
                })
                .state("transaksi.saldo_awal", {
                    url: "/saldoawal",
                    templateUrl: "api/acc/landaacc/tpl/t_saldo_awal/index.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Saldo Awal"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["api/acc/landaacc/tpl/t_saldo_awal/index.js?t=" + time]
                                });
                            }
                        ]
                    }
                }).state("transaksi.pengajuan", {
            url: "/pengajuan?:tahun:lokasi",
            templateUrl: "api/acc/landaacc/tpl/t_pengajuan/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Pengajuan Proposal"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/t_pengajuan/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("transaksi.approveatasan", {
            url: "/approveatasan",
            templateUrl: "api/acc/landaacc/tpl/t_approve_atasan/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Approve Atasan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/t_approve_atasan/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("transaksi.pertanggungjawaban", {
            url: "/pertanggungjawaban",
            templateUrl: "api/acc/landaacc/tpl/t_pertanggungjawaban/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Pertanggungjawaban Pembayaran"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/t_pertanggungjawaban/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("transaksi.saldo_awal_hutang", {
            url: "/saldoawalhutang",
        })

                .state("supplier.saldo_awal_hutang", {
                    url: "/saldo_awal_hutang",
                    templateUrl: "api/acc/landaacc/tpl/t_saldo_awal_hutang/index.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Saldo Awal Hutang"
                    },
                    resolve: {
                        loadCSS: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load(["ngFileUpload"]);
                            }
                        ],
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["api/acc/landaacc/tpl/t_saldo_awal_hutang/index.js?t=" + time]
                                });
                            }
                        ]
                    }
                }).state("keuangan", {
            abstract: true,
            templateUrl: "tpl/common/layouts/full.html?time=" + time,
            ncyBreadcrumb: {
                label: "Keuangan"
            },
            resolve: {

                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load([]);
                    }
                ],
                loadPlugin: ["$ocLazyLoad", function ($ocLazyLoad) {
                    }],
                authenticate: authenticate
            }
        }).state("keuangan.penerimaan", {
            url: "/penerimaan",
            templateUrl: "api/acc/landaacc/tpl/t_penerimaan/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Penerimaan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload", "angularFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/t_penerimaan/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("keuangan.pengeluaran", {
            url: "/pengeluaran?:no_proposal:total",
            templateUrl: "api/acc/landaacc/tpl/t_pengeluaran/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Pengeluaran"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload", "angularFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/t_pengeluaran/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("keuangan.transfer", {
            url: "/transfer",
            templateUrl: "api/acc/landaacc/tpl/t_transfer/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Transfer Kas"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload", "angularFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/t_transfer/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("bukubesar", {
            abstract: true,
            templateUrl: "tpl/common/layouts/full.html?time=" + time,
            ncyBreadcrumb: {
                label: "Buku Besar"
            },
            resolve: {

                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load([]);
                    }
                ],
                loadPlugin: ["$ocLazyLoad", function ($ocLazyLoad) {
                    }],
                authenticate: authenticate
            }
        }).state("bukubesar.jurnal_umum", {
            url: "/jurnal_umum",
            templateUrl: "api/acc/landaacc/tpl/t_jurnal_umum/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Jurnal Umum"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload", "angularFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/t_jurnal_umum/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("bukubesar.tutup_bulan", {
            url: "/tutupbulan",
            templateUrl: "api/acc/landaacc/tpl/t_tutup_bulan/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Tutup Bulan"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/t_tutup_bulan/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("bukubesar.tutup_tahun", {
            url: "/tutuptahun",
            templateUrl: "api/acc/landaacc/tpl/t_tutup_tahun/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Tutup Tahun"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/t_tutup_tahun/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("asset", {
            abstract: true,
            templateUrl: "tpl/common/layouts/full.html?time=" + time,
            ncyBreadcrumb: {
                label: "Asset"
            },
            resolve: {

                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load([]);
                    }
                ],
                loadPlugin: ["$ocLazyLoad", function ($ocLazyLoad) {
                    }],
                authenticate: authenticate
            }
        }).state("asset.asset", {
            url: "/asset",
            templateUrl: "api/acc/landaacc/tpl/m_asset/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Asset"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_asset/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("asset.umur_ekonomis", {
            url: "/Umur-Ekonomis",
            templateUrl: "api/acc/landaacc/tpl/m_umur_ekonomis/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Umur Ekonomis"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_umur_ekonomis/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("asset.penyusutan_asset", {
            url: "/penyusutan-asset",
            templateUrl: "api/acc/landaacc/tpl/m_asset/penyusutan.html?time=" + time,
            ncyBreadcrumb: {
                label: "Penyusutan Asset"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_asset/penyusutan.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("asset.pelepasan_asset", {
            url: "/pelepasan-asset",
            templateUrl: "api/acc/landaacc/tpl/m_asset/pelepasan.html?time=" + time,
            ncyBreadcrumb: {
                label: "Pelepasan Asset"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_asset/pelepasan.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("akun", {
            abstract: true,
            templateUrl: "tpl/common/layouts/full.html?time=" + time,
            ncyBreadcrumb: {
                label: "Akun"
            },
            resolve: {

                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load([]);
                    }
                ],
                loadPlugin: ["$ocLazyLoad", function ($ocLazyLoad) {
                    }],
                authenticate: authenticate
            }
        }).state("akun.klasifikasi", {
            url: "/klasifikasi_akun",
            templateUrl: "api/acc/landaacc/tpl/m_klasifikasi/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Klasifikasi Akun"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_klasifikasi/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("akun.budgeting_akun", {
            url: "/budgeting_akun",
            templateUrl: "api/acc/landaacc/tpl/t_budgeting/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Budgeting Akun"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(['daterangepicker']).then(() => {
                            return $ocLazyLoad.load({
                                cache: false, files: ["api/acc/landaacc/tpl/t_budgeting/index.js?t=" + time]
                            });
                        });
                    }
                ]
            }
        }).state("akun.monitoring_budget", {
            url: "/monitoring-budget",
            templateUrl: "api/acc/landaacc/tpl/t_monitoring_budget/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Monitoring Budget"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/t_monitoring_budget/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("akun.akun", {
            url: "/akun",
            templateUrl: "api/acc/landaacc/tpl/m_akun/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Akun"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_akun/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("akun.saldo_awal", {
            url: "/saldo_awal",
            templateUrl: "api/acc/landaacc/tpl/t_saldo_awal/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Akun"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/t_saldo_awal/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("akun.akun_peta", {
            url: "/pemetaan_akun",
            templateUrl: "api/acc/landaacc/tpl/m_akun_peta/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Akun"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_akun_peta/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("transaksi.penyusutan_asset", {
            url: "/penyusutan_asset",
            templateUrl: "api/acc/landaacc/tpl/m_asset/penyusutan.html?time=" + time,
            ncyBreadcrumb: {
                label: "Penyusutan Asset"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load(["ngFileUpload", "angularFileUpload"]);
                    }
                ],
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_asset/penyusutan.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("pengguna", {
            abstract: true,
            templateUrl: "tpl/common/layouts/full.html?time=" + time,
            ncyBreadcrumb: {
                label: "User Login"
            },
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load([]);
                    }
                ],
                loadPlugin: ["$ocLazyLoad", function ($ocLazyLoad) {
                    }],
                authenticate: authenticate
            }
        }).state("pengguna.akses", {
            url: "/hak-akses",
            templateUrl: "api/acc/landaacc/tpl/m_akses/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Hak Akses"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_akses/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("pengguna.inv_akses", {
            url: "/hak-akses-inv",
            templateUrl: "tpl/inventori/m_akses/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Hak Akses"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["tpl/inventori/m_akses/index.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("pengguna.user", {
            url: "/user",
            templateUrl: "api/acc/landaacc/tpl/m_user/index.html?time=" + time,
            ncyBreadcrumb: {
                label: "Pengguna"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["api/acc/landaacc/tpl/m_user/index.js?t=" + time]
                        });
                    }
                ]
            }
        })
        .state("pengguna.migrasi", {
            url: "/migrasi",
            templateUrl: "tpl/inventori/m_migrasi/migrasi.html?time=" + time,
            ncyBreadcrumb: {
                label: "Migrasi AFU"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["tpl/inventori/m_migrasi/migrasi.js?t=" + time]
                        });
                    }
                ]
            }
        })
                .state("pengguna.inv_user", {
                    url: "/user",
                    templateUrl: "tpl/inventori/m_user/index.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Pengguna"
                    },
                    resolve: {
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["tpl/inventori/m_user/index.js?t=" + time]
                                });
                            }
                        ]
                    }
                })
                .state("pengguna.profil", {
                    url: "/profil",
                    templateUrl: "api/acc/landaacc/tpl/m_user/profile.html?time=" + time,
                    ncyBreadcrumb: {
                        label: "Profil Pengguna"
                    },
                    resolve: {
                        loadMyCtrl: ["$ocLazyLoad",
                            function ($ocLazyLoad) {
                                return $ocLazyLoad.load({
                                    cache: false, files: ["api/acc/landaacc/tpl/m_user/profile.js?t=" + time]
                                });
                            }
                        ]
                    }
                }).state("page", {
            abstract: true,
            templateUrl: "tpl/common/layouts/blank.html?time=" + time,
            resolve: {
                loadCSS: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load([]);
                    }
                ]
            }
        }).state("page.login", {
            url: "/login",
            templateUrl: "tpl/common/pages/login.html?time=" + time,
            ncyBreadcrumb: {
                label: "Login"
            },
            resolve: {
                loadMyCtrl: ["$ocLazyLoad",
                    function ($ocLazyLoad) {
                        return $ocLazyLoad.load({
                            cache: false, files: ["tpl/site/login.js?t=" + time]
                        });
                    }
                ]
            }
        }).state("page.404", {
            url: "/404",
            templateUrl: "tpl/common/pages/404.html?time=" + time
        }).state("page.500", {
            url: "/500",
            templateUrl: "tpl/common/pages/500.html?time=" + time
        });

        function authenticate($q, UserService, $state, $transitions, $location, $rootScope) {
            var deferred = $q.defer();
            if (UserService.isAuth()) {
                deferred.resolve();
                var fromState = $state;
                var globalmenu = ["page.login", "pengguna.profil", "app.main", "page.500", "app.generator"];
                $transitions.onStart({}, function ($transition$) {
                    var toState = $transition$.$to();
                    if ($rootScope.user.akses[toState.name.replace(".", "_")] || globalmenu.indexOf(toState.name)) {
                    } else {
                        $state.target("page.500")
                    }
                });
            } else {
                $location.path("/login");
            }
            return deferred.promise;
        }
    }
]);
