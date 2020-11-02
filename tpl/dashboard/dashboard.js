angular.module('app').controller('dashboardCtrl', function($scope, Data, $state, UserService, $location) {
    var user = UserService.getUser();
    if (user === null) {
        $location.path('/login');
    }
    /*
     * chart per tahun
     */
    Data.get('site/getSaldoYear').then(function(result) {
        var kredit = Object.keys(result.data.saldoYear.kredit).map(function(key) {
            return result.data.saldoYear.kredit[key];
        });
        var debit = Object.keys(result.data.saldoYear.debit).map(function(key) {
            return result.data.saldoYear.debit[key];
        });
        /**
         * Grafik penerimaan
         */
        $scope.dataPenerimaan = {
            type: "bar",
            plotarea: {
                'adjust-layout': true
            },
            backgroundColor: "white",
            scaleX: {
                labels: result.data.labelPendapatan,
                "placement": "default",
                "itemsOverlap": true,
                "item": {
                    angle: -50,
                    fontSize: 10
                }
            },
            scaleY: {
                short : true
            },
            series: [{
                short: true,
                values: result.data.totalPendapatan,
                backgroundColor: "#348EB7"
            }]
        };
        /**
         * Grafik beban
         */
        $scope.dataBeban = {
            type: "bar",
            plotarea: {
                'adjust-layout': true
            },
            backgroundColor: "white",
            scaleX: {
                labels: result.data.labelPengeluaran,
                "placement": "default",
                "itemsOverlap": true,
                "item": {
                    angle: -50,
                    fontSize: 10
                }
            },
            scaleY: {
                short : true
            },
            series: [{
                short: true,
                values: result.data.totalPengeluaran,
                backgroundColor: "#CC5867"
            }]
        };
        /**
         * Grafik piutang
         */
        $scope.dataPiutang = {
            type: "bar",
            plotarea: {
                'adjust-layout': true
            },
            backgroundColor: "white",
            scaleX: {
                labels: result.data.labelPiutang,
                "placement": "default",
                "itemsOverlap": true,
                "item": {
                    angle: -50,
                    fontSize: 10
                }
            },
            scaleY: {
                short : true
            },
            series: [{
                short: true,
                values: result.data.totalPiutang,
                backgroundColor: "#CC5867"
            }]
        };
        /**
         * Grafik hutang
         */
        $scope.dataHutang = {
            type: "bar",
            plotarea: {
                'adjust-layout': true
            },
            backgroundColor: "white",
            scaleX: {
                labels: result.data.labelHutang,
                "placement": "default",
                "itemsOverlap": true,
                "item": {
                    angle: -50,
                    fontSize: 10
                }
            },
            scaleY: {
                short : true
            },
            series: [{
                short: true,
                values: result.data.totalHutang,
                backgroundColor: "#CC5867"
            }]
        };
        /**
         * Grafik tahunan
         */
        $scope.dataLine = {
            "background-color": "transparent",
            "graphset": [{
                "type": "line",
                "background-color": "#fff",
                "border-color": "transparent",
                "border-width": "1px",
                "plot": {
                    "animation": {
                        "delay": 500,
                        "effect": "ANIMATION_SLIDE_LEFT"
                    }
                },
                "plotarea": {
                    "margin": "50px 25px 70px 70px"
                },
                "scale-y": {
                    "line-color": "none",
                    "short" : true,
                    "guide": {
                        "line-style": "solid",
                        "line-color": "#d2dae2",
                        "line-width": "1px",
                        "alpha": 0.5
                    },
                    "tick": {
                        "visible": false
                    },
                    "item": {
                        "font-color": "#8391a5",
                        "font-family": "Arial",
                        "font-size": "10px",
                        "padding-right": "5px"
                    }
                },
                "scale-x": {
                    "line-color": "#d2dae2",
                    "line-width": "2px",
                    "values": ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                    "tick": {
                        "line-color": "#d2dae2",
                        "line-width": "1px"
                    },
                    "guide": {
                        "visible": false
                    },
                    "item": {
                        "font-color": "#8391a5",
                        "font-family": "Arial",
                        "font-size": "10px",
                        "padding-top": "5px"
                    }
                },
                "legend": {
                    "layout": "x2",
                    "background-color": "none",
                    "shadow": 0,
                    "margin": "auto auto 15 auto",
                    "text-align": "center",
                    "border-width": 0,
                    "item": {
                        "font-color": "#707d94",
                        "font-family": "Arial",
                        "padding": "0px",
                        "margin": "0px",
                        "font-size": "12px"
                    },
                    "marker": {
                        "show-line": "true",
                        "type": "match",
                        "font-family": "Arial",
                        "font-size": "10px",
                        "size": 4,
                        "line-width": 2,
                        "padding": "3px"
                    }
                },
                "crosshair-x": {
                    "lineWidth": 1,
                    "line-color": "#707d94",
                    "plotLabel": {
                        "shadow": false,
                        "font-color": "#000",
                        "font-family": "Arial",
                        "font-size": "10px",
                        "padding": "5px 10px",
                        "border-radius": "5px",
                        "alpha": 1
                    },
                    "scale-label": {
                        "font-color": "#ffffff",
                        "background-color": "#707d94",
                        "font-family": "Arial",
                        "font-size": "10px",
                        "padding": "5px 10px",
                        "border-radius": "5px"
                    }
                },
                "tooltip": {
                    "visible": false
                },
                "series": [{
                    "values": debit,
                    "text": "Pemasukan",
                    "line-color": "#87D9FC",
                    "line-width": "2px",
                    "shadow": 0,
                    "marker": {
                        "background-color": "#fff",
                        "size": 3,
                        "border-width": 1,
                        "border-color": "#975098",
                        "shadow": 0
                    },
                    "palette": 2,
                    "visible": 1
                }, {
                    "values": kredit,
                    "text": "Pengeluaran",
                    "line-color": "#F2A0AC",
                    "line-width": "2px",
                    "shadow": 0,
                    "marker": {
                        "background-color": "#fff",
                        "size": 3,
                        "border-width": 1,
                        "border-color": "#d37e04",
                        "shadow": 0
                    },
                    "palette": 3
                }]
            }]
        };
    });
    /**
     * chart per bulan
     */
    Data.get('site/getSaldo').then(function(result) {
        $scope.dataPie = {
            type: "pie",
            backgroundColor: "#fff",
            plot: {
                borderColor: "transparent",
                borderWidth: 5,
                valueBox: {
                    placement: 'in',
                    text: '%t\n%npv%',
                },
                tooltip: {
                    fontSize: '12px',
                    padding: "5 10",
                    text: "%npv%"
                },
                animation: {
                    effect: 2,
                    method: 5,
                    speed: 500,
                    sequence: 1
                }
            },
            plotarea: {
                margin: "40"
            },
            series: [{
                text: "Pengeluaran",
                values: [parseInt(result.data.kredit)],
                backgroundColor: "#CC5867",
            }, {
                text: "Pemasukan",
                values: [parseInt(result.data.debit)],
                backgroundColor: "#348EB7"
            }]
        };
    });
    /**
     * Cek pemetaan akun
     */
    Data.get('/acc/m_akun_peta/index').then(function(response) {
        $scope.status = response.data.status_data;
    });
    /**
     * Cek apakah sudah melakukan penyusutan
     */
    Data.get('/acc/m_asset/cekPenyusutanBulanini').then(function(response) {
        $scope.cekPenyusutan = response.data;
    });
    /*
     * ambil saldo dari akun kas
     */
    Data.get("/site/getSaldoAkun").then(function(response) {
        $scope.saldoAkun = response.data;
        $scope.tanggalSekarang = new Date();
    });
    /*
     * ambil data approval pengajuan
     */
    // Data.get("acc/apppengajuan/index").then(function(response) {
    //     var arrApproval = [];
    //     angular.forEach(response.data.list, function(value, key) {
    //         if(value.level == value.levelapproval){
    //             arrApproval.push(value)
    //         }
    //     });
    //     $scope.listApproval = arrApproval;
    // });
    /*
     * function save approval pengajuan
     */
    $scope.save = function(form, status) {
        var param = {
            status: status,
            data: form
        }
        if (status == 'close') {
            var foo = prompt('Alasan ditolak : ')
            if (foo) {
                param['catatan'] = foo;
                Data.post("acc/apppengajuan/status", param).then(function(result) {
                    $scope.cancel();
                });
            } else {
                console.log("batal")
            }
        } else {
            Data.post("acc/apppengajuan/status", param).then(function(result) {
                $scope.cancel();
            });
        }
    };
});