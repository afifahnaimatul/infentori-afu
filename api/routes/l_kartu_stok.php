<?php

$app->get('/l_kartu_stok/laporan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;


////
//    echo json_encode($params['barang_id']);
//    die();
//


    $barang_id = $params['barang_id'];
    $tgl_mulai = $params['startDate'];
    $tgl_selesai = $params['endDate'];

    $data = $thargamasuk = $thargakeluar = $hrg2 = [];


    $produk = $db->select('*')->from('inv_m_barang')->where('id', '=', $barang_id)->find();


    /** Ambil Saldo Awal */
    $db->select('
            inv_kartu_stok.id,
            acc_m_lokasi_id,
            hpp,
            jenis_kas,
            catatan,
            jumlah_masuk,
            harga_masuk,
            jumlah_keluar,
            tanggal,
            inv_kartu_stok.kode,
            catatan,
            inv_m_barang_id,
            inv_m_barang.harga_pokok,
            inv_m_barang.type_barcode,
            inv_kartu_stok.harga_keluar,
            inv_kartu_stok.harga_masuk,
            inv_kartu_stok.stok
        ')
            ->from('inv_kartu_stok')
            ->leftJoin('inv_m_barang', 'inv_m_barang.id = inv_kartu_stok.inv_m_barang_id')
            ->where('inv_m_barang_id', '=', $produk->id)
            ->andWhere("stok", ">", 0)
            ->orderBy("inv_kartu_stok.created_at");

    $cabang = 'Semua Cabang';

    if (isset($params['saldosekarang'])) {
        $db->customWhere("FROM_UNIXTIME(tanggal, '%Y-%m-%d') <= '" . $tgl_mulai . "'", "AND");
    } else {
        $db->customWhere("FROM_UNIXTIME(tanggal, '%Y-%m-%d') < '" . $tgl_mulai . "'", "AND");
    }

    if (isset($params['acc_m_lokasi_id']) && !empty($params['acc_m_lokasi_id'])) {
        $db->andWhere("inv_kartu_stok.acc_m_lokasi_id", "=", $params['acc_m_lokasi_id']);

        $get_cabang = $db->find("select * from acc_m_lokasi where id = '" . $params['acc_m_lokasi_id'] . "'");
        $cabang = isset($get_cabang->nama) ? $get_cabang->nama : 'Semua Cabang';
    }

    $saldoAwal = $db->findAll();


    $arrSaldoAwal = [];

    $data[1]['jumlah_saldo'][0] = ['nilai' => 0];
    $data[1]['harga_saldo'][0] = ['nilai' => 0];
    $data[1]['total_saldo'][0] = ['nilai' => 0];
    foreach ($saldoAwal as $value) {

        if ($value->type_barcode == 'serial') {
            $jumlah = (isset($arrSaldoAwal[$value->harga_masuk]) ? $arrSaldoAwal[$value->harga_masuk]['jumlah'] : 0) + $value->stok;
            $harga = $value->harga_masuk;

            $arrSaldoAwal[$value->harga_masuk] = [
                'jumlah' => $jumlah,
                'harga' => $harga,
                'saldo' => $harga * $jumlah,
            ];
        } else {
            if ($produk->harga_pokok == 'fifo' || $produk->harga_pokok == 'lifo') {
                /** Saldo Akhir stok FIFO & LIFO */
//                $arrSaldoAwal[] = [
//                    'jumlah' => $value->stok,
//                    'harga' => $value->harga_masuk,
//                    'saldo' => $value->harga_masuk * $value->stok,
//                ];
                $jumlah = (isset($arrSaldoAwal[$value->harga_masuk]) ? $arrSaldoAwal[$value->harga_masuk]['jumlah'] : 0) + $value->stok;
                $harga = $value->harga_masuk;

                $arrSaldoAwal[$value->harga_masuk] = [
                    'jumlah' => $jumlah,
                    'harga' => $harga,
                    'saldo' => $harga * $jumlah,
                ];
            } else {
                /** Saldo Akhir stok Average */
                $jumlah = $value->stok;
                $harga = $value->harga_masuk;
                $saldoAkhir = $value->harga_masuk * $value->stok;

                $saldo['jumlah'] = (isset($saldo['jumlah']) ? $saldo['jumlah'] : 0) + $jumlah;
                $saldo['saldo'] = (isset($saldo['saldo']) ? $saldo['saldo'] : 0) + $saldoAkhir;

                if ($saldo['jumlah'] > 0) {
                    $saldo['harga'] = round($saldo['saldo'] / $saldo['jumlah']);
                } else {
                    $saldo['harga'] = 0;
                }

                $data[1]['jumlah_saldo'][0] = ['nilai' => $saldo['jumlah']];
                $data[1]['harga_saldo'][0] = ['nilai' => $saldo['harga']];
                $data[1]['total_saldo'][0] = ['nilai' => $saldo['saldo']];
            }
        }
    }

    $data[1]['kode'] = '';
    $data[1]['catatan'] = 'Saldo Awal';
    $data[1]['tanggal'] = date('d-m-Y', strtotime($tgl_mulai));
    $data[1]['harga_pokok'] = $produk->harga_pokok;

    foreach ($arrSaldoAwal as $k => $v2) {
        if (isset($data[1]['jumlah_saldo'][0]['nilai']) && $data[1]['jumlah_saldo'][0]['nilai'] == 0) {
            unset($data[1]['jumlah_saldo'][0]);
            unset($data[1]['harga_saldo'][0]);
            unset($data[1]['total_saldo'][0]);
        }

        $data[1]['jumlah_saldo'][] = ['nilai' => $v2['jumlah']];
        $data[1]['harga_saldo'][] = ['nilai' => $v2['harga']];
        $data[1]['total_saldo'][] = ['nilai' => $v2['jumlah'] * $v2['harga']];
    }

    /** End */
    if (isset($params['show_kartu']) && $params['show_kartu']) {
        $db->select('
            inv_kartu_stok.id,
            acc_m_lokasi_id,
            hpp,
            jenis_kas,
            catatan,
            jumlah_masuk,
            harga_masuk,
            jumlah_keluar,
            tanggal,
            inv_kartu_stok.kode,
            catatan,
            inv_m_barang_id,
            inv_m_barang.harga_pokok,
            inv_m_barang.type_barcode,
            inv_kartu_stok.harga_keluar,
            inv_kartu_stok.harga_masuk
        ')
                ->from('inv_kartu_stok')
                ->leftJoin('inv_m_barang', 'inv_m_barang.id = inv_kartu_stok.inv_m_barang_id')
                ->where('inv_m_barang_id', '=', $produk->id)
                ->customWhere("FROM_UNIXTIME(tanggal, '%Y-%m-%d') >= '" . $tgl_mulai . "'", "AND")
                ->customWhere("FROM_UNIXTIME(tanggal, '%Y-%m-%d') <= '" . $tgl_selesai . "'", "AND")
                ->orderBy("inv_kartu_stok.created_at");

        if (isset($params['acc_m_lokasi_id']) && !empty($params['acc_m_lokasi_id'])) {
            $db->andWhere("inv_kartu_stok.acc_m_lokasi_id", "=", $params['acc_m_lokasi_id']);
        }

        $models = $db->findAll();
        
//        pd($models);die;

        $harga_masuk_terakhir = 0;
        $saldo = [
            'jumlah' => 0,
            'saldo' => 0,
            'harga' => 0,
        ];

        foreach ($models as $key => $value) {
            $data[$value->kode]['harga_pokok'] = $value->harga_pokok;

            if ($value->jenis_kas == 'masuk') {
                if ($value->type_barcode == 'serial') {
                    $jumlah = (isset($hrg2[$value->harga_masuk]) ? $hrg2[$value->harga_masuk]['jumlah'] : 0) + $value->jumlah_masuk;
                    $harga = $value->harga_masuk;

                    $hrg2[$value->harga_masuk] = [
                        'jumlah' => $jumlah,
                        'harga' => $harga,
                        'saldo' => $harga * $jumlah,
                    ];
                } else {
                    if ($value->harga_pokok == 'fifo' || $value->harga_pokok == 'lifo') {
                        /** Saldo Akhir stok FIFO & LIFO */
//                        $hrg2[] = [
//                            'jumlah' => $value->jumlah_masuk,
//                            'harga' => $value->harga_masuk,
//                            'saldo' => $value->harga_masuk * $value->jumlah_masuk,
//                        ];
                        $jumlah = (isset($hrg2[$value->harga_masuk]) ? $hrg2[$value->harga_masuk]['jumlah'] : 0) + $value->jumlah_masuk;
                        $harga = $value->harga_masuk;

                        $hrg2[$value->harga_masuk] = [
                            'jumlah' => $jumlah,
                            'harga' => $harga,
                            'saldo' => $harga * $jumlah,
                        ];
                    } else {
                        /** Saldo Akhir stok Average */
                        $jumlah = $value->jumlah_masuk;
                        $harga = $value->harga_masuk;
                        $saldoAkhir = $value->harga_masuk * $value->jumlah_masuk;

                        $saldo['jumlah'] = (isset($saldo['jumlah']) ? $saldo['jumlah'] : 0) + $jumlah;
                        $saldo['saldo'] = (isset($saldo['saldo']) ? $saldo['saldo'] : 0) + $saldoAkhir;

                        if ($saldo['jumlah'] > 0) {
                            $saldo['harga'] = round($saldo['saldo'] / $saldo['jumlah']);
                        } else {
                            $saldo['harga'] = 0;
                        }

                        $data[$value->kode]['jumlah_saldo'][0] = ['nilai' => $saldo['jumlah']];
                        $data[$value->kode]['harga_saldo'][0] = ['nilai' => $saldo['harga']];
                        $data[$value->kode]['total_saldo'][0] = ['nilai' => $saldo['saldo']];
                    }
                }

                $tot_masuk = $value->harga_masuk * $value->jumlah_masuk;
                $data[$value->kode]['jumlah_keluar'] = '';
                $data[$value->kode]['hargas_keluar'] = '';
                $data[$value->kode]['total_keluar'] = '';
                $data[$value->kode]['jumlah_masuk'][] = ['nilai' => $value->jumlah_masuk];
                $data[$value->kode]['harga_masuk'][] = ['nilai' => $value->harga_masuk];
                $data[$value->kode]['total_masuk'][] = ['nilai' => $tot_masuk];

                if ($value->harga_pokok == 'fifo' || $value->harga_pokok == 'lifo' || ($value->harga_pokok == 'average' && $value->type_barcode == 'serial')) {
                    $data[$value->kode]['saldo'] = $hrg2;
                }
            } else if ($value->jenis_kas == 'keluar') {
                if ($value->catatan == 'Unpost Pembelian' || $value->catatan == 'Retur Pembelian') {
                    $value->hpp = $value->harga_keluar;
                }
                $totals = $value->hpp * $value->jumlah_keluar;
                $stop = false;

                if ($value->harga_pokok == 'fifo') {
                    $arrTmp = $hrg2;
                } else if ($value->harga_pokok == 'lifo') {
                    $arrTmp = ksort($hrg2);
                }

                /** Pengurangan stok FIFO & LIFO */
                if ($value->harga_pokok == 'fifo' || $value->harga_pokok == 'lifo' || ($value->harga_pokok == 'average' && $value->type_barcode == 'serial')) {
                    $sisaStok = $value->jumlah_keluar;
                    foreach ($hrg2 as $k => $v) {
                        if ($v['harga'] == $value->hpp) {
                            $sisa = $hrg2[$k]['jumlah'] - $sisaStok;
                            if ($sisa < 0) {
                                $hrg2[$k]['jumlah'] = 0;
                                $sisaStok -= $v['jumlah'];
                            } else {
                                $hrg2[$k]['jumlah'] = $sisa;
                                $sisaStok = 0;
                            }

                            $hrg2[$k]['saldo'] = $hrg2[$k]['jumlah'] * $hrg2[$k]['saldo'];
                        }

                        if ($hrg2[$k]['jumlah'] == 0) {
                            unset($hrg2[$k]);
                            if (empty($hrg2[$k])) {
                                unset($hrg2[$k]);
                            }
                        }

                        if ($v['harga'] == $value->hpp && $sisaStok == 0) {
                            $stop = true;
                            break;
                        }
                    }
                } else {
                    /** Saldo akhir AVERAGE */
                    $saldo['jumlah'] = (isset($saldo['jumlah']) ? $saldo['jumlah'] : 0) - $value->jumlah_keluar;
                    $saldo['saldo'] = (isset($saldo['saldo']) ? $saldo['saldo'] : 0) - $totals;

                    if ($saldo['jumlah'] > 0) {
                        $saldo['harga'] = round($saldo['saldo'] / $saldo['jumlah']);
                    } else {
                        $saldo['harga'] = 0;
                    }

                    $data[$value->kode]['jumlah_saldo'][] = ['nilai' => $saldo['jumlah']];
                    $data[$value->kode]['harga_saldo'][] = ['nilai' => $saldo['harga']];
                    $data[$value->kode]['total_saldo'][] = ['nilai' => $saldo['saldo']];
                }

                $hrg_k = implode('<br>', $thargakeluar);

                $data[$value->kode]['jumlah_masuk'] = '';
                $data[$value->kode]['harga_masuk'] = '';
                $data[$value->kode]['total_masuk'] = '';
                $data[$value->kode]['jumlah_keluar'][] = ['nilai' => $value->jumlah_keluar];
                $data[$value->kode]['hargas_keluar'][] = ['nilai' => $value->hpp];
                $data[$value->kode]['total_keluar'][] = ['nilai' => $totals];

                if ($value->harga_pokok == 'fifo' || $value->harga_pokok == 'lifo' || ($value->harga_pokok == 'average' && $value->type_barcode == 'serial')) {
                    $data[$value->kode]['saldo'] = $hrg2;
                }
            } else {
                unset($models[$key]);
            }

            $data[$value->kode]['kode'] = $value->kode;
            $data[$value->kode]['catatan'] = $value->catatan;
            $data[$value->kode]['tanggal'] = date('d-m-Y', $value->tanggal);
        }
    }

    /** menata saldo akhir FIFO & LIFO */
    foreach ($data as $key => $val) {
        if ($val['harga_pokok'] != 'average' && $key != '1') {
            $data[$key]['jumlah_saldo'][0] = ['nilai' => 0];
            $data[$key]['harga_saldo'][0] = ['nilai' => 0];
            $data[$key]['total_saldo'][0] = ['nilai' => 0];
        }

        if (isset($val['saldo'])) {
            foreach ($val['saldo'] as $k => $v2) {
                if (isset($data[$key]['total_saldo'][0]) && $data[$key]['total_saldo'][0]['nilai'] == 0 && $val['harga_pokok'] != 'average') {
                    unset($data[$key]['jumlah_saldo'][0]);
                    unset($data[$key]['harga_saldo'][0]);
                    unset($data[$key]['total_saldo'][0]);
                }

                $data[$key]['jumlah_saldo'][] = ['nilai' => $v2['jumlah']];
                $data[$key]['harga_saldo'][] = ['nilai' => $v2['harga']];
                $data[$key]['total_saldo'][] = ['nilai' => $v2['jumlah'] * $v2['harga']];
            }
        }
    }

    $produk->detail = $data;

    $data = [
        'model' => $produk,
        'tgl_mulai' => $tgl_mulai,
        'tgl_selesai' => $tgl_selesai,
        'barang' => $produk->nama,
        'lokasi' => $cabang,
        'harga_pokok' => $produk->harga_pokok,
        'disiapkan' => date("d-m-Y, H:i")];



    if (isset($params['export']) && $params['export'] == 1) {
        $view = twigView();
        $content = $view->fetch('laporan/kartu_stok.html', [
            "data" => $data,
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"laporan-kartu-stok.xls\"");
        echo $content;
    } elseif (isset($params['print']) && $params['print'] == 1) {
        $view = twigView();
        $content = $view->fetch('laporan/kartu_stok.html', [
            "data" => $data,
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, [
            'model' => $produk,
            'tgl_mulai' => $tgl_mulai,
            'tgl_selesai' => $tgl_selesai,
            'barang' => $produk->nama,
            'lokasi' => $cabang,
            'harga_pokok' => $produk->harga_pokok,
            'disiapkan' => date("d-m-Y, H:i")]);
    }
});

$app->get('/l_kartu_stok/listbarang', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select('*')
            ->from('inv_m_barang')
            ->where('type', '=', 'barang');
//            ->andWhere('nama', 'LIKE', $params['nama']);

    if (isset($params['produk_id']) && !empty($params['produk_id'])) {
        $db->andWhere('inv_m_barang.id', '=', $params['produk_id']);
    }
    
    $barang = $db->findAll();

    return successResponse($response, $barang);
});
?>
