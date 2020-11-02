<?php

$app->get("/l_harga_barang_dagang/laporan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    //bulan
    $params['bulan_akhir']  = date("Y-m-t", strtotime($params['bulan'] . "-01"));
    $params['bulan_awal']   = date("Y-m-01", strtotime("-31 days", strtotime($params['bulan_akhir'])));

    //saldo awal
    $db->select("inv_m_barang_id, jumlah_masuk, harga_masuk, jumlah_keluar, trans_tipe, trans_id, inv_kartu_stok.kode")
        ->from("inv_kartu_stok")
        ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
        ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
        ->where("tanggal", "<", strtotime($params['bulan_awal']));

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $saldo_awal = $db->findAll();
    // echo json_encode($saldo_awal); die();

    $arr_saldo_awal = [];
    foreach ($saldo_awal as $key => $val) {
        if (isset($arr_saldo_awal[$val->inv_m_barang_id])) {
            $arr_saldo_awal[$val->inv_m_barang_id]['jumlah_masuk'] += intval($val->jumlah_masuk);
            $arr_saldo_awal[$val->inv_m_barang_id]['jumlah_keluar'] += intval($val->jumlah_keluar);
            $arr_saldo_awal[$val->inv_m_barang_id]['total'] += intval($val->jumlah_masuk) - intval($val->jumlah_keluar);
        } else {
            $arr_saldo_awal[$val->inv_m_barang_id]['jumlah_masuk'] = intval($val->jumlah_masuk);
            $arr_saldo_awal[$val->inv_m_barang_id]['jumlah_keluar'] = intval($val->jumlah_keluar);
            $arr_saldo_awal[$val->inv_m_barang_id]['total'] = intval($val->jumlah_masuk) - intval($val->jumlah_keluar);
        }
    }
    // echo json_encode($arr_saldo_awal); die();

    //saldo_periode
    $db->select("inv_m_barang_id, jumlah_masuk, jumlah_keluar, trans_tipe, trans_id, inv_kartu_stok.kode, jenis_kas")
            ->from("inv_kartu_stok")
            ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
            ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->where("tanggal", ">=", strtotime($params['bulan_awal']))
            ->where("tanggal", "<=", strtotime($params['bulan_akhir']));
            // ->where("acc_m_lokasi_id", "=", $params['lokasi']);

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi']) && $params['lokasi'] != 1) {
        $db->where("acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $saldo_periode = $db->orderBy("inv_m_barang_id, inv_kartu_stok.id")->findAll();
    // echo json_encode($saldo_periode); die();

    $arr_saldo_periode = [];
    foreach ($saldo_periode as $key => $val) {
        $val->add = !empty($val->add) ? $val->add : 'ya';
        if ($val->trans_tipe == 'inv_penjualan_id' && $val->add == 'ya' && ($key + 1) != count($saldo_periode)) {
            if (strpos($saldo_periode[($key + 1)]->kode, "KRM") !== false) {

                $val->add = 'tidak';
                $saldo_periode[($key + 1)]->add = 'tidak';

                // echo $val->kode . "1";
                // die;
                // pd($saldo_periode);
            } else {
                // echo $val->kode . "2";
                // die;
                $val->add = 'ya';
            }
        }
        if ($val->add == 'ya') {
            if (isset($arr_saldo_periode[$val->inv_m_barang_id])) {
                $arr_saldo_periode[$val->inv_m_barang_id]['jumlah_masuk'] += intval($val->jumlah_masuk);
                $arr_saldo_periode[$val->inv_m_barang_id]['jumlah_keluar'] += intval($val->jumlah_keluar);
                $arr_saldo_periode[$val->inv_m_barang_id]['total'] += intval($val->jumlah_masuk) - intval($val->jumlah_keluar);
            } else {
                $arr_saldo_periode[$val->inv_m_barang_id]['jumlah_masuk'] = intval($val->jumlah_masuk);
                $arr_saldo_periode[$val->inv_m_barang_id]['jumlah_keluar'] = intval($val->jumlah_keluar);
                $arr_saldo_periode[$val->inv_m_barang_id]['total'] = intval($val->jumlah_masuk) - intval($val->jumlah_keluar);
            }
        }
    }

    //nama bulan
    $list_month   = [];
    $time         = strtotime($params['bulan_akhir']);
    $last         = date('F Y', strtotime($params['bulan_awal']));
    do {
        $month        = date('F Y', $time);
        $list_month[] = $month;
        $time         = strtotime('-1 month', $time);
    } while ($month != $last);

    //deklarasi bulan per barang
    $arr_tes = [];
    foreach ($list_month as $key => $val) {
        $arr_tes[$val] = [];
    }

    $db->select("
      inv_m_barang_id,
      jumlah_masuk,
      jumlah_keluar,
      FROM_UNIXTIME(tanggal, '%M %Y') as tanggal,
      COALESCE(inv_kartu_stok.harga_masuk, inv_kartu_stok.harga_keluar) AS harga_masuk,
      COALESCE(inv_kartu_stok.harga_masuk, inv_kartu_stok.harga_keluar) AS hpp
    ")
    // inv_kartu_stok.harga_masuk,
    ->from("inv_kartu_stok")
    ->where("tanggal", ">=", strtotime($params['bulan_awal']))
    ->where("tanggal", "<=", strtotime($params['bulan_akhir']))
    ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
    ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id");
    // ->where("acc_m_lokasi_id", "=", $params['lokasi'])
    // ->customWhere("trans_tipe IN ('inv_pembelian_id', 'inv_penjualan_id')", "AND");

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $stok = $db->orderBy("inv_m_barang_id")->findAll();
    // echo json_encode($stok); die();

    $db->select("
      inv_m_barang_id,
      jumlah_masuk,
      jumlah_keluar,
      FROM_UNIXTIME(tanggal, '%M %Y') as tanggal,
      COALESCE(inv_kartu_stok.harga_masuk, inv_kartu_stok.harga_keluar) AS harga_masuk,
      COALESCE(inv_kartu_stok.harga_masuk, inv_kartu_stok.harga_keluar) AS hpp
    ")
    ->from("inv_kartu_stok")
    ->where("tanggal", "<", strtotime($params['bulan_awal']))
    // ->where("tanggal", "<=", strtotime($params['bulan_akhir']))
    ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
    ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id");

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $stok_sebelum = $db->orderBy("inv_m_barang_id")->findAll();
    // echo json_encode($stok_sebelum); die();

    $arr = [];
    foreach ($stok as $key => $val) {
        if (!isset($arr[$val->inv_m_barang_id])) {
            if ($val->harga_masuk == 0) {
                $val->harga_masuk = $val->hpp;
            }
            $arr[$val->inv_m_barang_id] = $arr_tes;
        }
    }
    // echo json_encode($arr);die;

    $total_akhir = [];
    foreach ($stok as $key => $val) {
        if (isset($total_akhir[$val->inv_m_barang_id])) {
            $total_akhir[$val->inv_m_barang_id] = $val->jumlah_masuk != '' ? $total_akhir[$val->inv_m_barang_id] + $val->jumlah_masuk : $total_akhir[$val->inv_m_barang_id] - $val->jumlah_keluar;
        } else {
            if ($val->jumlah_masuk != '') {
                if (isset($arr_saldo_awal[$val->inv_m_barang_id]) && !empty($arr_saldo_awal[$val->inv_m_barang_id])) {
                    $total_akhir[$val->inv_m_barang_id] = $arr_saldo_awal[$val->inv_m_barang_id]['total'] + $val->jumlah_masuk;
                } else {
                    $total_akhir[$val->inv_m_barang_id] = $val->jumlah_masuk;
                }
            } else {
                if (isset($arr_saldo_awal[$val->inv_m_barang_id]) && !empty($arr_saldo_awal[$val->inv_m_barang_id])) {
                    $total_akhir[$val->inv_m_barang_id] = $arr_saldo_awal[$val->inv_m_barang_id]['total'] + (-$val->jumlah_keluar);
                } else {
                    $total_akhir[$val->inv_m_barang_id] = -$val->jumlah_keluar;
                }
            }
        }

        if (isset($arr[$val->inv_m_barang_id][$val->tanggal]['jumlah_masuk'])) {
            $arr[$val->inv_m_barang_id][$val->tanggal]['jumlah_masuk'] = $val->jumlah_masuk != '' ? $arr[$val->inv_m_barang_id][$val->tanggal]['jumlah_masuk'] + $val->jumlah_masuk : $arr[$val->inv_m_barang_id][$val->tanggal]['jumlah_masuk'] - $val->jumlah_keluar;
        } else {
            $arr[$val->inv_m_barang_id][$val->tanggal]['jumlah_masuk'] = $val->jumlah_masuk != '' ? $val->jumlah_masuk : -$val->jumlah_keluar;
        }
        $arr[$val->inv_m_barang_id][$val->tanggal]['inv_m_barang_id'] = $val->inv_m_barang_id;
        $arr[$val->inv_m_barang_id][$val->tanggal]['tanggal'] = $val->tanggal;

        if ($val->harga_masuk != 0) {
            $arr[$val->inv_m_barang_id][$val->tanggal]['harga_masuk'] = $val->harga_masuk;
            $arr[$val->inv_m_barang_id][$val->tanggal]['harga_masuk'] = $val->hpp;
        }
    }
    // echo json_encode($arr); die();

    // foreach ($stok_sebelum as $key => $val) {
    //     if (isset($arr_sebelum[$val->inv_m_barang_id]['jumlah_masuk'])) {
    //         $arr_sebelum[$val->inv_m_barang_id]['jumlah_masuk'] = $val->jumlah_masuk != '' ? $arr_sebelum[$val->inv_m_barang_id]['jumlah_masuk'] + $val->jumlah_masuk : $arr_sebelum[$val->inv_m_barang_id]['jumlah_masuk'] - $val->jumlah_keluar;
    //     } else {
    //         $arr_sebelum[$val->inv_m_barang_id]['jumlah_masuk'] = $val->jumlah_masuk != '' ? $val->jumlah_masuk : -$val->jumlah_keluar;
    //     }
    //     $arr_sebelum[$val->inv_m_barang_id]['inv_m_barang_id'] = $val->inv_m_barang_id;
    //
    //     if ($val->jumlah_masuk != '') {
    //         $arr_sebelum[$val->inv_m_barang_id]['tanggal'] = $val->tanggal;
    //     }
    //
    //     if ($val->harga_masuk != 0) {
    //         $arr_sebelum[$val->inv_m_barang_id]['harga_masuk'] = $val->harga_masuk;
    //         $arr_sebelum[$val->inv_m_barang_id]['harga_masuk'] = $val->hpp;
    //     }
    // }
    // echo json_encode($arr_sebelum); die();

    //saldo_retur_pembelian
    $db->select("inv_m_barang_id, jumlah_masuk, jumlah_keluar")
            ->from("inv_kartu_stok")
            ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
            ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->where("tanggal", ">=", strtotime($params['bulan_awal']))
            ->where("tanggal", "<=", strtotime($params['bulan_akhir']))
            // ->where("acc_m_lokasi_id", "=", $params['lokasi'])
            ->where("trans_tipe", "=", "inv_retur_pembelian_id");

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $saldo_retur_pembelian = $db->findAll();

    $arr_saldo_retur_pembelian = [];
    foreach ($saldo_retur_pembelian as $key => $val) {
        if (isset($arr_saldo_retur_pembelian[$val->inv_m_barang_id])) {
            $arr_saldo_retur_pembelian[$val->inv_m_barang_id]['jumlah_keluar'] += intval($val->jumlah_keluar);
            $arr_saldo_retur_pembelian[$val->inv_m_barang_id]['total'] += intval($val->jumlah_keluar);
        } else {
            $arr_saldo_retur_pembelian[$val->inv_m_barang_id]['jumlah_keluar'] = intval($val->jumlah_keluar);
            $arr_saldo_retur_pembelian[$val->inv_m_barang_id]['total'] = intval($val->jumlah_keluar);
        }
    }

    $db->select("inv_m_barang.*")
        ->from("inv_m_barang")
        ->join("LEFT JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
        ->where("inv_m_kategori.is_dijual", "=", "ya")
        ->andWhere("inv_m_barang.is_deleted", "=", 0);

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("id", "=", $params['barang']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $barang = $db->findAll();
    // echo json_encode($barang); die();

    $arr_month = [];
    foreach ($list_month as $key => $val) {
        $arr_month[$val] = [
            'Beli ' . $val, 'Masa ' . $val, 'Harga Satuan', 'Jumlah (RP)'
        ];
    }
    // echo json_encode($list_month);die;

    $data = [
        'total_qty_awal' => 0,
        'total_qty_jual' => 0,
        'total_qty_beli' => 0,
        'total_qty_akhir' => 0,
        'total_qty_akhir_retur' => 0,
        'total_qty_retur_pembelian' => 0,
        'periode' => date("F Y", strtotime($params['bulan_awal'])) . " - " . date("F Y", strtotime($params['bulan_akhir'])),
        'lokasi' => isset($params['lokasi_nama']) && !empty($params['lokasi_nama']) ? $params['lokasi_nama'] : "SEMUA LOKASI",
        'list_bulan' => $arr_month,
    ];

    $index = 0;
    $arr_hasil = [];
    foreach ($barang as $key => $val) {
        // $val->no = $key + 1;
        $val->qty_awal = isset($arr_saldo_awal[$val->id]) && !empty($arr_saldo_awal[$val->id]) ? $arr_saldo_awal[$val->id]['total'] : 0;
        $val->qty_beli = isset($arr_saldo_periode[$val->id]) && !empty($arr_saldo_periode[$val->id]) ? $arr_saldo_periode[$val->id]['jumlah_masuk'] : 0;
        $val->qty_jual = isset($arr_saldo_periode[$val->id]) && !empty($arr_saldo_periode[$val->id]) ? $arr_saldo_periode[$val->id]['jumlah_keluar'] : 0;

        $val->qty_retur_pembelian = isset($arr_saldo_retur_pembelian[$val->id]) && !empty($arr_saldo_retur_pembelian[$val->id]) ? $arr_saldo_retur_pembelian[$val->id]['total'] : 0;

        $val->qty_akhir = $val->qty_awal + $val->qty_beli - $val->qty_jual;
        $val->qty_akhir_retur = $val->qty_akhir - $val->qty_retur_pembelian;

        $val->total_akhir = isset($total_akhir[$val->id]) ? $total_akhir[$val->id] : 0;
        if (isset($arr_saldo_awal[$val->id]) && !isset($total_akhir[$val->id])) {
            $val->total_akhir = $arr_saldo_awal[$val->id]['total'];
        }

        $val->detail = isset($arr[$val->id]) ? $arr[$val->id] : $arr_tes;

        $data['total_qty_awal'] += $val->qty_awal;
        $data['total_qty_jual'] += $val->qty_jual;
        $data['total_qty_beli'] += $val->qty_beli;
        $data['total_qty_akhir'] += $val->qty_akhir;
        $data['total_qty_retur_pembelian'] += $val->qty_retur_pembelian;
        $data['total_qty_akhir_retur'] += $val->qty_akhir_retur;

        $arr_hasil[$index] = (array) $val;
        $index = $index + 1;
    }
    // echo json_encode($arr_hasil); die();

    foreach ($arr_hasil as $key => $val) {
        $harga_akhir = 0;
        foreach ($val['detail'] as $keys => $vals) {
            if (!empty($vals)) {
                if (isset($vals['harga_masuk'])) {
                    $harga_akhir += $vals['jumlah_masuk'] * $vals['harga_masuk'];
                } else {
                    $harga_akhir += $vals['jumlah_masuk'] * 0;
                }
            }
        }
        $arr_hasil[$key]['harga_akhir'] = $harga_akhir;
    }

    $detail = $arr_hasil;
    // echo json_encode($detail);
    // die();

    if (isset($params['is_export']) && $params['is_export'] == 1) {
      /* kode  lama */
        // $view = twigView();
        // $content = $view->fetch("laporan/harga_barang_dagang.html", [
        //     'data' => $data,
        //     'detail' => $detail,
        //     'css' => modulUrl() . '/assets/css/style.css',
        // ]);
        // header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        // header("Content-Disposition: attachment;Filename=\"Stok Barang Dagang dengan Harga (" . $data['periode'] . ").xls\"");
        // echo $content;
      /* kode  lama - END */

      ob_start();
      $xls = PHPExcel_IOFactory::load("format_excel/template_stok_barang_dagang_harga.xlsx");

      // get the first worksheet
      $sheet = $xls->getSheet(0);
      $sheet->getCell('A3')->setValue( date('F Y', strtotime($params['startDate'])) );
      $index = 6;

      foreach ($data['list_bulan'] as $key => $value) {
          $sheet->getCell('A' . 5)->setValue( $value[0] );
          $sheet->getCell('B' . 5)->setValue( $value[1] );
          $sheet->getCell('C' . 5)->setValue( $value[2] );
          $sheet->getCell('D' . 5)->setValue( $value[3] );
          $sheet->getCell('E' . 5)->setValue( $value[4] );
          $sheet->getCell('F' . 5)->setValue( $value[5] );
          $sheet->getCell('F' . 5)->setValue( $value[6] );
          $sheet->getCell('G' . 5)->setValue( $value[7] );
          $sheet->getCell('H' . 5)->setValue( $value[8] );
          $sheet->getCell('I' . 5)->setValue( $value[9] );
          $sheet->getCell('J' . 5)->setValue( $value[10] );
          $sheet->getCell('K' . 5)->setValue( $value[11] );
          $sheet->getCell('L' . 5)->setValue( $value[12] );
          $sheet->getCell('M' . 5)->setValue( $value[13] );
      }

      $writer = new PHPExcel_Writer_Excel2007($xls);
      header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
      header("Content-Disposition: attachment;Filename=\"STOK BARANG DAGANGAN (HARGA) ". date("F Y", strtotime($params['startDate'])) .".xlsx\"");

      ob_end_clean();
      $writer->save('php://output');
      exit;

    } elseif (isset($params['is_print']) && $params['is_print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/harga_barang_dagang.html", [
            'data' => $data,
            'detail' => $detail,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, ['data' => $data, 'detail' => $detail]);
    }
});

$app->get("/l_harga_barang_dagang/laporanStokHarga", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    //deklarasi bulan
    $params['bulan_akhir']  = date("Y-m-t", strtotime($params['bulan'] . "-01"));
    $a = date("Y-m-01", strtotime($params['bulan_akhir'])) . " -1 Month";
    $params['bulan_awal']   = date("Y-m-01", strtotime($a));

    $list_month   = [];
    $time         = strtotime($params['bulan_akhir']);
    $last         = date('M y', strtotime($params['bulan_awal']));

    $listBulan    = [];

    do {
      $month        = date('M y', $time);
      $list_month[] = $month;
      $listBulan[]  = date("Y-m", $time);
      $time         = strtotime('-1 month', $time);
    } while ($month != $last);
    $listBulan[]    = 'saldo_awal';

    $listBulan = array_unique($listBulan);

    $arr_tes = [];
    foreach ($list_month as $key => $val) {
        $arr_tes[$val] = [];
    }
    $arr_tes['Saldo Awal'] = [];

    $arr_month = [];
    foreach ($list_month as $key => $val) {
        $arr_month[$val] = [
            'Beli ' . $val,
            'Masa ' . $val,
            'Harga Satuan',
            'Jumlah (RP)'
        ];
    }
    $arr_month['Saldo Awal'] = [
        'Beli ' . 'Saldo Awal',
        'Masa ' . 'Saldo Awal',
        'Harga Satuan',
        'Jumlah (RP)'
    ];
    //deklarasi bulan - END

    // Get Stok Masuk & Keluar dalam rentang 2 bulan
    $db->select("
      FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
      tanggal,
      inv_m_barang_id,
      jumlah_masuk,
      harga_masuk,
      jumlah_keluar,
      jenis_kas,
      trans_tipe,
      trans_id,
      inv_kartu_stok.kode
      ")
    ->from("inv_kartu_stok")
    ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
    ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
    ->where("tanggal", "<=", strtotime($params['bulan_akhir']))
    ->andWhere("tanggal", ">=", strtotime($params['bulan_awal']));

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }
    $db->orderBy("tanggal ASC");
    $stokRentang = $db->findAll();
    // Get Stok Masuk & Keluar - END

    /*
      Get Stok Sebelumnya
      Select yg Sisanya > 0
    */

    $db->select("
      inv_m_barang_id,
      (SUM(COALESCE(jumlah_masuk,0)) - SUM(COALESCE(jumlah_keluar,0))) as stok,
      jumlah_keluar
      ")
    ->from("inv_kartu_stok")
    ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
    ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
    ->where("tanggal", "<", strtotime($params['bulan_awal']));

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $db->groupBy("inv_m_barang_id");
    $db->having("(SUM(COALESCE(jumlah_masuk,0)) - SUM(COALESCE(jumlah_keluar,0))) > 0");

    $stokLalu = $db->findAll();

    $stokLaluID=[];
    $saldo_awal_all=0;
    if(!empty($stokLalu)){
      foreach ($stokLalu as $key => $value) {
        $stokLaluID[] = $value->inv_m_barang_id;
        $saldo_awal_all += $value->stok;
      }
      $stokLaluID = implode(",",$stokLaluID);
    }

    // Select stoknya
    $db->select("
      FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
      tanggal,
      inv_m_barang_id,
      jumlah_masuk,
      harga_masuk,
      jenis_kas,
      jumlah_keluar,
      trans_tipe,
      trans_id,
      inv_kartu_stok.kode
      ")
    ->from("inv_kartu_stok")
    ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
    ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
    ->where("tanggal", "<", strtotime($params['bulan_awal']));

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $db->orderBy("tanggal ASC");
    $getStokLalu = $db->findAll();
    // Select stoknya - END
    $allStok = array_merge($stokRentang, $getStokLalu);

    $arrayStok = [];
    $saldo_beli_all = $saldo_jual_all = $saldo_retur_beli_all = 0;
    foreach ($allStok as $key => $value) {
      $arrayStok[$value->inv_m_barang_id]['bulan'][$value->bulan]['format_bulan'] = date("M y", strtotime($value->bulan . '-01'));
      @$arrayStok[$value->inv_m_barang_id]['bulan'][$value->bulan]['masuk'] += $value->jumlah_masuk;

      @$arrayStok[$value->inv_m_barang_id]['keluar']  += $value->jumlah_keluar;
      @$arrayStok[$value->inv_m_barang_id]['masuk']   += $value->jumlah_masuk;

      if($value->jenis_kas == 'masuk' && $value->harga_masuk > 0){
        $arrayStok[$value->inv_m_barang_id]['bulan'][$value->bulan]['harga_masuk'][] = $value->harga_masuk;
      }

      // All saldo
      if($value->trans_tipe == 'inv_penjualan_id')
        $saldo_jual_all += $value->jumlah_keluar;

      if($value->trans_tipe == 'inv_retur_penjualan_id'){
        $saldo_jual_all -= $value->jumlah_masuk;
      }

      if($value->trans_tipe == 'inv_stok_opname_id' && $value->jenis_kas == 'masuk')
        $saldo_beli_all += $value->jumlah_masuk;

      if($value->trans_tipe == 'inv_pembelian_id')
        $saldo_beli_all += $value->jumlah_masuk;

      if($value->trans_tipe == 'inv_retur_pembelian_id')
        $saldo_retur_beli_all += $value->jumlah_keluar;
    }

    // Get Stok Sebelumnya - END

    // Pengurangan Stok Perbulan
    foreach ($arrayStok as $key => $value) {
      $jumlah_keluar = $value['keluar'];
      ksort($value['bulan']);
      foreach ($value['bulan'] as $key2 => $value2) {

        if($value2['masuk'] > 0){
          if($value2['masuk'] > $jumlah_keluar){
            $arrayStok[$key]['bulan'][$key2]['keluar']  = $jumlah_keluar;
            $arrayStok[$key]['bulan'][$key2]['sisa']    = $value2['masuk'] - $jumlah_keluar;
            $jumlah_keluar -= $arrayStok[$key]['bulan'][$key2]['keluar'];

          } else if($value2['masuk'] == $jumlah_keluar){
            $arrayStok[$key]['bulan'][$key2]['keluar']  = $value2['masuk'];
            $arrayStok[$key]['bulan'][$key2]['sisa']    = 0;
            $jumlah_keluar -= $arrayStok[$key]['bulan'][$key2]['keluar'];

          } else if($value2['masuk'] < $jumlah_keluar){
            $arrayStok[$key]['bulan'][$key2]['keluar']  = $value2['masuk'];
            $arrayStok[$key]['bulan'][$key2]['sisa']    = 0;
            $jumlah_keluar -= $arrayStok[$key]['bulan'][$key2]['keluar'];

          }
        }

        if(empty($arrayStok[$key]['bulan'][$key2]['sisa']))
          $arrayStok[$key]['bulan'][$key2]['sisa']    = 0;

        if(isset($value2['harga_masuk'])){
          $arrayStok[$key]['bulan'][$key2]['harga_masuk_avg'] = array_sum($value2['harga_masuk']) / count($value2['harga_masuk']);
          $arrayStok[$key]['bulan'][$key2]['harga_masuk_avg'] = number_format($arrayStok[$key]['bulan'][$key2]['harga_masuk_avg'], 2, '.', '');
        } else {
          $arrayStok[$key]['bulan'][$key2]['harga_masuk_avg'] = 0;
        }

        $arrayStok[$key]['bulan'][$key2]['saldo_rp'] = $arrayStok[$key]['bulan'][$key2]['harga_masuk_avg'] * $arrayStok[$key]['bulan'][$key2]['sisa'];
        $arrayStok[$key]['bulan'][$key2]['saldo_rp'] = number_format($arrayStok[$key]['bulan'][$key2]['saldo_rp'], 2, ".", '');
        @$arrayStok[$key]['saldo_rp']                += $arrayStok[$key]['bulan'][$key2]['saldo_rp'];

      }
      krsort($arrayStok[$key]['bulan']);
      $arrayStok[$key]['saldo_akhir'] = $value['masuk'] - $value['keluar'];
    }

    // Pengurangan Stok Perbulan - END

    // Get Barang
    $db->select("
      inv_m_barang.id,
      inv_m_barang.nama
      ")
    ->from("inv_m_barang")
    ->join("LEFT JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
    ->where("inv_m_kategori.is_dijual", "=", "ya")
    ->andWhere("inv_m_barang.is_deleted", "=", 0);

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("id", "=", $params['barang']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }
    $db->orderBy("inv_m_barang.nama ASC");
    $getBarang = $db->findAll();
    // Get Barang - ENd

    $listBarang   = [];
    $totalFooter  = [];
    $totalAll     = [];
    foreach ($getBarang as $key => $value) {
      $listBarang[$key] = (array)$value;

      foreach ($listBulan as $key2) {
        if(!empty($arrayStok[$value->id]['bulan'][$key2])){
          // Total Footer
          $totalFooter[$key2]['format_bulan']       = '';
          @$totalFooter[$key2]['sisa']              += $arrayStok[$value->id]['bulan'][$key2]['sisa'];
          @$totalFooter[$key2]['harga_masuk_avg']   = '';
          @$totalFooter[$key2]['saldo_rp']          += $arrayStok[$value->id]['bulan'][$key2]['saldo_rp'];
          // Total Footer - END

          $listBarang[$key]['detail'][$key2] = $arrayStok[$value->id]['bulan'][$key2];

          unset($arrayStok[$value->id]['bulan'][$key2]);
        } else {
          if( $key2 == 'saldo_awal' && !empty($arrayStok[$value->id]['bulan']) ){
            $listBarang[$key]['detail'][$key2] = array_shift($arrayStok[$value->id]['bulan']);

            // Total Footer

            $totalFooter[$key2]['format_bulan']       = '';
            @$totalFooter[$key2]['sisa']              += $listBarang[$key]['detail'][$key2]['sisa'];
            @$totalFooter[$key2]['harga_masuk_avg']   = '';
            @$totalFooter[$key2]['saldo_rp']          += $listBarang[$key]['detail'][$key2]['saldo_rp'];
            // Total Footer - END

          } else {
            $listBarang[$key]['detail'][$key2] = [
              'sisa'            => 0,
              'harga_masuk_avg' => 0,
              'format_bulan'    => '-',
            ];
          }
        }
      }

      if(!empty($arrayStok[$value->id])) {
        $listBarang[$key]['saldo_akhir']  = $arrayStok[$value->id]['saldo_akhir'];
        $listBarang[$key]['saldo_rp']     = $arrayStok[$value->id]['saldo_rp'];
        @$totalAll['saldo_akhir']         += $arrayStok[$value->id]['saldo_akhir'];
        @$totalAll['saldo_rp']            += $arrayStok[$value->id]['saldo_rp'];
      }
    }

    // Footer
    krsort($totalFooter);
    $temp_saldo_awal = array_shift($totalFooter);
    $totalFooter['9999-12'] = $temp_saldo_awal;
    // pd($totalFooter);
    // $totalFooter['9999-12'] = [
    //   'sisa'            => 0,
    //   'harga_masuk_avg' => "",
    //   'format_bulan'    => '',
    //   'saldo_rp'        => 0,
    // ];
    // Footer - END

    $data = [
      'lokasi'                    => 'PT. AMAK FIRDAUS UTOMO',
      'periode'                   => date("F Y", strtotime($params['bulan_akhir'])),
      'list_bulan'                => $arr_month,
      'total_qty_awal'            => $saldo_awal_all,
      'total_qty_beli'            => $saldo_beli_all,
      'total_qty_jual'            => $saldo_jual_all,
      'total_qty_akhir'           => 0,
      'total_qty_retur_pembelian' => $saldo_retur_beli_all,
      'total_qty_akhir_retur'     => ($saldo_awal_all + ($saldo_beli_all-$saldo_retur_beli_all)) - $saldo_jual_all,
      'total_footer'              => $totalFooter,
      'total_all'                 => $totalAll,
    ];

// pd([
//   $listBulan,
//   $listBarang
// ]);

    $no=0;
    $listBarangFinal=[];
    foreach ($listBarang as $key => $value) {
      $listBarangFinal[$no] = $value;
      if(!empty($arrayStok[$value['id']])){
        $panjangArray = count($arrayStok[$value['id']]['bulan']);

        if($panjangArray == 1){
          foreach ($arrayStok[ $value['id'] ]['bulan'] as $key2 => $value2) {
            $no++;
            $listBarangFinal[$no]['detail'][$key2] = $value2;
          }
        } else {
          // pd($arrayStok[$value['id']]);
          $template=[
            'sisa'            => 0,
            'harga_masuk_avg' => 0,
            'format_bulan'    => '-',
          ];

          foreach ($listBulan as $bulan) {
            $no++;
            $listBarangFinal[$no]['detail'][$bulan] = $template;
          }

          foreach ($arrayStok[$value['id']]['bulan'] as $key2 => $value2) {
            if($value2['sisa'] > 0){
              $no++;
              $listBarangFinal[$no]['detail'][$key2] = $value2;
            }
          }
        }
      } else {
        $no++;
      }
    }

    if (isset($params['is_export']) && $params['is_export'] == 1) {
        ob_start();
        $xls = PHPExcel_IOFactory::load("format_excel/template_stok_barang_dagang_harga.xlsx");

        // get the first worksheet
        $sheet = $xls->getSheet(0);
        $sheet->getCell('A3')->setValue( date('F Y', strtotime( $params['bulan_akhir'] )) );
        $index = 6;

        $list_abjad = ['B', 'C', 'D', 'E', 'F', 'G','H', 'I', 'J', 'K', 'L', 'M'];
        $urutan_abjad = 0;
        foreach ($data['list_bulan'] as $key => $value) {
          foreach ($value as $value2) {
            $sheet->getCell( $list_abjad[$urutan_abjad] . '5')->setValue( $value2 );
            $urutan_abjad++;
          }
        }

        foreach ($listBarang as $key => $value) {
          $urutan_abjad=0;
          $sheet->getCell( 'A' . $index)->setValue( $value['nama'] );
          foreach ($value['detail'] as $key2 => $value2) {
            $sheet->getCell( $list_abjad[$urutan_abjad] . $index)->setValue( $value2['sisa'] );
            $urutan_abjad++;
            $sheet->getCell( $list_abjad[$urutan_abjad] . $index)->setValue( $value2['format_bulan'] );
            $urutan_abjad++;
            $sheet->getCell( $list_abjad[$urutan_abjad] . $index)->setValue( $value2['harga_masuk_avg'] );
            $urutan_abjad++;
            $sheet->getCell( $list_abjad[$urutan_abjad] . $index)->setValue( isset($value2['saldo_rp']) ? $value2['saldo_rp'] : 0 );
            $urutan_abjad++;
          }

          $sheet->getCell( 'N' . $index)->setValue( $value['saldo_akhir'] );
          $sheet->getCell( 'O' . $index)->setValue( $value['saldo_rp'] );
          $index++;
        }

        $sheet->getCell( 'A' . $index)->setValue( 'Total' );
        $urutan_abjad=0;
        foreach ($data['total_footer'] as $key => $value) {
          $sheet->getCell( $list_abjad[$urutan_abjad] . $index)->setValue( isset($value['sisa']) ? $value['sisa'] : 0 );
          $urutan_abjad++;
          $sheet->getCell( $list_abjad[$urutan_abjad] . $index)->setValue( '' );
          $urutan_abjad++;
          $sheet->getCell( $list_abjad[$urutan_abjad] . $index)->setValue( '' );
          $urutan_abjad++;
          $sheet->getCell( $list_abjad[$urutan_abjad] . $index)->setValue( isset($value['saldo_rp']) ? $value['saldo_rp'] : 0 );
          $urutan_abjad++;
        }
        $sheet->getCell( 'N' . $index)->setValue( $data['total_all']['saldo_akhir'] );
        $sheet->getCell( 'O' . $index)->setValue( $data['total_all']['saldo_rp'] );

        $sheet->getStyle('D6:D' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('E6:E' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('H6:H' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('I6:I' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('L6:L' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('M6:M' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('O6:O' . $index)->getNumberFormat()->setFormatCode("#,##0.00");

        $sheet->getStyle("A" . 6 . ":O" . $index)->applyFromArray(
          array(
              'borders' => array(
                  'allborders' => array(
                      'style' => PHPExcel_Style_Border::BORDER_THIN,
                  )
              )
          )
        );

        $borderBot = array(
            'borders' => array(
                'allborders' => array(
                    'style' => PHPExcel_Style_Border::BORDER_THIN,
                )
            )
        );

        $index += 2;

        $sheet->getCell( 'J' . $index)->setValue( 'S. AWAL' );
        $sheet->getCell( 'K' . $index)->setValue( $data['total_qty_awal'] );
        $index++;

        $sheet->getCell( 'J' . $index)->setValue( 'BELI' );
        $sheet->getCell( 'K' . $index)->setValue( $data['total_qty_beli'] );
        $index++;

        $sheet->getCell( 'K' . $index)->setValue( $data['total_qty_awal'] + $data['total_qty_beli'] );
        $index++;

        $sheet->getCell( 'J' . $index)->setValue( 'JUAL' );
        $sheet->getCell( 'K' . $index)->setValue( $data['total_qty_jual'] );
        $index++;

        $sheet->getCell( 'K' . $index)->setValue( $data['total_qty_akhir'] );
        $index++;

        $sheet->getCell( 'J' . $index)->setValue( 'RETUR' );
        $sheet->getCell( 'K' . $index)->setValue( $data['total_qty_retur_pembelian'] );
        $index++;

        $sheet->getCell( 'J' . $index)->setValue( 'S.AKHIR' );
        $sheet->getCell( 'K' . $index)->setValue( $data['total_qty_akhir_retur'] );
        $index++;

        $writer = new PHPExcel_Writer_Excel2007($xls);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"STOK BARANG DAGANGAN (HARGA) ". date("F Y", strtotime( $params['bulan_akhir'] )) .".xlsx\"");
        ob_end_clean();
        $writer->save('php://output');
        exit;

    } elseif (isset($params['is_print']) && $params['is_print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/harga_barang_dagang.html", [
            'data'      => $data,
            'detail'    => $listBarang,
            'css'       => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, [
          'data'    => $data,
          'detail'  => $listBarang]);
    }
});
