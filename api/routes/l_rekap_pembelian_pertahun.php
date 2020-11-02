<?php

$app->get("/l_rekap_pembelian_pertahun/laporan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $params['tanggal_awal'] = $params['tahun'] . "-01-01";
    $params['tanggal_akhir'] = $params['tahun'] . "-12-31";

    $arr_bulan = [];
    for ($m = 1; $m <= 12; $m++) {
        $month = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
        $arr_bulan[] = $month;
    }

    $db->select("inv_m_kategori.*")
            ->from("inv_m_kategori")
            ->where("jenis_barang", '=', 'pembelian')
            ->where("is_deleted", "=", 0)
            ->groupBy("inv_m_kategori.nama");
    $kategori = $db->findAll();

    $kategori = getChildFlat($kategori, 0);

//    echo json_encode($kategori);
//    die;

    $db->select("inv_m_barang.nama")
            ->from("inv_pembelian")
            ->join("JOIN", "inv_pembelian_det", "inv_pembelian_det.inv_pembelian_id = inv_pembelian.id")
            ->join("JOIN", "inv_m_barang", "inv_pembelian_det.inv_m_barang_id = inv_m_barang.id")
            ->where("inv_pembelian.is_import", "=", 1)
            ->where("inv_pembelian.is_ppn", "=", $params['is_ppn'])
            ->where("inv_pembelian.status", "!=", 'draft')
            ->customWhere("inv_pembelian.tanggal >= '" . strtotime($params['tanggal_awal']) . "' AND inv_pembelian.tanggal <= '" . strtotime($params['tanggal_akhir']) . "'", "AND")
            ->groupBy("inv_m_barang.nama")
            ->orderBy("inv_m_barang.nama, inv_pembelian_det.id");

    $barang = $db->findAll();

    $arr_kategori = [];
    $parent_id = 0;
    $no = 0;

    $before_parent_id = 0;
    $before_no = 0;

    foreach ($kategori as $key => $val) {
        $val->nama = ($val->level == 0 ? '' : str_repeat("---", $val->level)) . $val->nama;
        foreach ($arr_bulan as $k => $v) {
            $arr_kategori[$val->nama]['detail'][$v] = [];
        }
        $arr_kategori[$val->nama]['detail']['Jumlah'] = [];
        $arr_kategori[$val->nama]['is_parent'] = $val->is_parent;
        $arr_kategori[$val->nama]['parent_id'] = $val->parent_id;
        if ($val->parent_id != $parent_id) {
            if ($val->parent_id == $before_parent_id) {
                $parent_id = $before_parent_id;
                $no = $before_no + 1;
                $arr_kategori[$val->nama]['no'] = $no;
            } else {
                $before_parent_id = $parent_id;
                $before_no = $no;
                $parent_id = $val->parent_id;
                $no = 1;
                $arr_kategori[$val->nama]['no'] = $no;
            }

//            echo "asdsad".$before_parent_id;
        } else {
            $no += 1;
            $arr_kategori[$val->nama]['no'] = $no;
        }
    }

    foreach ($arr_bulan as $k => $v) {
        $arr_kategori['Jumlah']['detail'][$v] = [];
    }
    $arr_kategori['Jumlah']['detail']['Jumlah'] = [];

    $arr_barang = [];
    foreach ($barang as $key => $val) {
        foreach ($arr_bulan as $k => $v) {
            $arr_barang[$val->nama][$v] = [];
        }
        $arr_barang[$val->nama]['Jumlah'] = [];
    }

    foreach ($arr_bulan as $k => $v) {
        $arr_barang['Jumlah'][$v] = [];
    }
    $arr_barang['Jumlah']['Jumlah'] = [];

    $biaya = [
        "Bea Masuk" => "bea_masuk",
        "PPN" => "ppn",
        "PPH Pasal 22" => "pph22",
        "Denda Pabean" => "denda_pabean",
        "Biaya Pelabuhan (PPN)" => "pelabuhan_ppn",
        "Biaya Pelabuhan (Non PPN)" => "pelabuhan_non_ppn",
    ];

    $arr_biaya = [];
    foreach ($biaya as $key => $val) {
        foreach ($arr_bulan as $k => $v) {
            $arr_biaya[$key][$v] = [];
        }
        $arr_biaya[$key]['Jumlah'] = [];
    }

    foreach ($arr_bulan as $k => $v) {
        $arr_biaya['Jumlah'][$v] = [];
    }
    $arr_biaya['Jumlah']['Jumlah'] = [];

    $arr = $arr_kategori;
    $arr2 = $arr_barang;
    $arr3 = $arr_biaya;

    // GET stok setahun
    $db->select("
        inv_pembelian_det.inv_m_barang_id,
        inv_pembelian_det.jumlah as jumlah_masuk,
        inv_pembelian_det.subtotal_edit,
        inv_pembelian_det.diskon,
        FROM_UNIXTIME(inv_pembelian.tanggal, '%M') as tanggal,
        inv_m_barang.nama as barang,
        inv_m_barang.inv_m_kategori_id,
        inv_pembelian_det.harga as harga_beli,
        inv_m_kategori.nama as kategori,
        inv_m_kategori.level as level_kategori,
        inv_pembelian.is_import,
        inv_pembelian.bea_masuk,
        inv_pembelian.ppn_edit,
        inv_pembelian.ppn,
        inv_pembelian.pph22,
        inv_pembelian.denda_pabean,
        inv_pembelian.pelabuhan_ppn,
        inv_pembelian.pelabuhan_non_ppn")
            ->from("inv_pembelian")
            ->join("LEFT JOIN", "inv_pembelian_det", "inv_pembelian.id = inv_pembelian_det.inv_pembelian_id")
            ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_pembelian_det.inv_m_barang_id")
            ->join("JOIN", "inv_m_kategori", "inv_m_barang.inv_m_kategori_id = inv_m_kategori.id")
            ->andWhere("inv_pembelian.tanggal", "<=", strtotime($params['tanggal_akhir']))
            ->andWhere("inv_pembelian.tanggal", ">=", strtotime($params['tanggal_awal']))
            ->andWhere("inv_m_kategori.jenis_barang", "=", "pembelian")
            ->andWhere("inv_pembelian.is_ppn", "=", $params['is_ppn'])
            ->andWhere("inv_pembelian.status", "!=", "draft");

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->andWhere("inv_pembelian.acc_m_lokasi_id", "=", $params['lokasi']);
    }

    $kartu_stok = $db->findAll();
    $kartu_stok_import = $db->andWhere("inv_pembelian.is_import", "=", 1)->findAll();
    // GET stok setahun - END

    // GET Retur Pembelian
    $db->select("
      SUM((inv_retur_pembelian.total)) as total,
      SUM((inv_retur_pembelian.ppn)) as ppn,
      FROM_UNIXTIME(inv_retur_pembelian.tanggal, '%M') as tanggal
    ")
    ->from("inv_retur_pembelian")
    ->join("LEFT JOIN", "inv_pembelian", "inv_pembelian.id = inv_retur_pembelian.inv_pembelian_id")
    ->andWhere("inv_retur_pembelian.tanggal", "<=", strtotime($params['tanggal_akhir']))
    ->andWhere("inv_retur_pembelian.tanggal", ">=", strtotime($params['tanggal_awal']))
    ->andWhere("inv_pembelian.is_ppn", "=", $params['is_ppn'])
    ->andWhere("inv_retur_pembelian.status", "!=", "draft");

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->andWhere("inv_pembelian.acc_m_lokasi_id", "=", $params['lokasi']);
    }

    $db->groupBy("MONTH(FROM_UNIXTIME(inv_retur_pembelian.tanggal))");

    $getReturPembelian = $db->findAll();

    $returPembelian=[];
    foreach ($getReturPembelian as $key => $value) {
      @$returPembelian[$value->tanggal]['total']  += $value->total;
      @$returPembelian[$value->tanggal]['ppn']    += $value->ppn;
      @$returPembelian['Jumlah']['total']         += $value->total;
      @$returPembelian['Jumlah']['ppn']           += $value->ppn;
    }
    // GET Retur Pembelian - END

    foreach ($kartu_stok as $key => $val) {
        if ($val->is_import == 0) {
            $val->kategori = ($val->level_kategori == 0 ? '' : str_repeat("---", $val->level_kategori)) . $val->kategori;

            @$arr[$val->kategori]['detail'][$val->tanggal]['total'] += !empty($val->subtotal_edit) ? $val->subtotal_edit - $val->diskon : floatval($val->jumlah_masuk) * floatval($val->harga_beli) - $val->diskon;
            @$arr[$val->kategori]['detail']['Jumlah']['total']      += !empty($val->subtotal_edit) ? $val->subtotal_edit - $val->diskon : floatval($val->jumlah_masuk) * floatval($val->harga_beli) - $val->diskon;

            @$arr['Jumlah']['detail'][$val->tanggal]['total']       += !empty($val->subtotal_edit) ? $val->subtotal_edit - $val->diskon : floatval($val->jumlah_masuk) * floatval($val->harga_beli) - $val->diskon;
            @$arr['Jumlah']['detail']['Jumlah']['total']            += !empty($val->subtotal_edit) ? $val->subtotal_edit - $val->diskon : floatval($val->jumlah_masuk) * floatval($val->harga_beli) - $val->diskon;
        }
    }

    foreach ($arr as $kat => $det) {
      foreach ($det['detail'] as $key => $bulan) {
        if(empty($bulan['total'])){
          $arr[$kat]['detail'][$key]['total']     = 0;
          $arr[$kat]['detail'][$key]['ppn']       = 0;
        } else {
          $arr[$kat]['detail'][$key]['ppn']         = floatval($bulan['total'] * 10/100);
        }
      }

      // PPN Per kategori
      $arr[$kat]['detail']['Jumlah']['ppn'] = floatval($arr[$kat]['detail']['Jumlah']['total'] * 10/100);
    }

    $footer = [];
    foreach ($arr['Jumlah']['detail'] as $key => $value) {
      $footer['Jumlah_retur']['detail'][$key]['total']     = isset($returPembelian[$key]['total']) ? $returPembelian[$key]['total'] : 0;
      $footer['Jumlah_retur']['detail'][$key]['ppn']       = isset($returPembelian[$key]['ppn']) ? $returPembelian[$key]['ppn'] : 0;
      $footer['Jumlah_after']['detail'][$key]['total']     = $value['total'] - $footer['Jumlah_retur']['detail'][$key]['total'];
      $footer['Jumlah_after']['detail'][$key]['ppn']       = $value['ppn'] - $footer['Jumlah_retur']['detail'][$key]['ppn'];
    }

    foreach ($kartu_stok_import as $key => $val) {
        if (isset($arr2[$val->barang][$val->tanggal]['total'])) {
            $arr2[$val->barang][$val->tanggal]['total'] += !empty($val->subtotal_edit) ? $val->subtotal_edit : floatval($val->jumlah_masuk) * floatval($val->harga_beli);
            $arr2[$val->barang]['Jumlah']['total'] += !empty($val->subtotal_edit) ? $val->subtotal_edit : floatval($val->jumlah_masuk) * floatval($val->harga_beli);
        } else {
            $arr2[$val->barang][$val->tanggal]['total'] = !empty($val->subtotal_edit) ? $val->subtotal_edit : floatval($val->jumlah_masuk) * floatval($val->harga_beli);
            $arr2[$val->barang]['Jumlah']['total'] = !empty($val->subtotal_edit) ? $val->subtotal_edit : floatval($val->jumlah_masuk) * floatval($val->harga_beli);
        }

        if (isset($arr2['Jumlah'][$val->tanggal]['total'])) {
            $arr2['Jumlah'][$val->tanggal]['total'] += !empty($val->subtotal_edit) ? $val->subtotal_edit : floatval($val->jumlah_masuk) * floatval($val->harga_beli);
        } else {
            $arr2['Jumlah'][$val->tanggal]['total'] = !empty($val->subtotal_edit) ? $val->subtotal_edit : floatval($val->jumlah_masuk) * floatval($val->harga_beli);
        }

        if (isset($arr2['Jumlah']['Jumlah']['total'])) {
            $arr2['Jumlah']['Jumlah']['total'] += !empty($val->subtotal_edit) ? $val->subtotal_edit : floatval($val->jumlah_masuk) * floatval($val->harga_beli);
        } else {
            $arr2['Jumlah']['Jumlah']['total'] = !empty($val->subtotal_edit) ? $val->subtotal_edit : floatval($val->jumlah_masuk) * floatval($val->harga_beli);
        }
    }

    // Mencari Akumulasi Biaya Tambahan
    $db->select("
        FROM_UNIXTIME(inv_pembelian.tanggal, '%M') as tanggal,
        SUM(inv_pembelian_det_biaya.bea_masuk) as bea_masuk,
        SUM(inv_pembelian_det_biaya.ppn) as ppn,
        SUM(inv_pembelian_det_biaya.pph22) as pph22,
        SUM(inv_pembelian_det_biaya.denda_pabean) as denda_pabean,
        inv_pembelian.pelabuhan_ppn,
        inv_pembelian.pelabuhan_non_ppn")
            ->from("inv_pembelian")
            ->JOIN("LEFT JOIN", "inv_pembelian_det_biaya", "inv_pembelian.id = inv_pembelian_det_biaya.inv_pembelian_id")
//            ->where("inv_pembelian.is_ppn", "=", $params['is_ppn'])
            ->andWhere("inv_pembelian.tanggal", "<=", strtotime($params['tanggal_akhir']))
            ->andWhere("inv_pembelian.tanggal", ">=", strtotime($params['tanggal_awal']))
            ->andWhere("inv_pembelian.status", "!=", "draft");

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->andWhere("inv_pembelian.acc_m_lokasi_id", "=", $params['lokasi']);
    }
    $listBiaya = $db->andWhere("inv_pembelian.is_import", "=", 1)->groupBy("inv_pembelian.id")->findAll();

    foreach ($listBiaya as $key => $val) {
        foreach ($biaya as $k => $v) {
            if (isset($arr3[$k][$val->tanggal]['total'])) {
                $arr3[$k][$val->tanggal]['total'] += floatval($val->{$v});
                $arr3[$k]['Jumlah']['total'] += floatval($val->{$v});
            } else {
                $arr3[$k][$val->tanggal]['total'] = floatval($val->{$v});
                $arr3[$k]['Jumlah']['total'] = floatval($val->{$v});
            }

            if (isset($arr3['Jumlah'][$val->tanggal]['total'])) {
                $arr3['Jumlah'][$val->tanggal]['total'] += floatval($val->{$v});
            } else {
                $arr3['Jumlah'][$val->tanggal]['total'] = floatval($val->{$v});
            }

            if (isset($arr3['Jumlah']['Jumlah']['total'])) {
                $arr3['Jumlah']['Jumlah']['total'] += floatval($val->{$v});
            } else {
                $arr3['Jumlah']['Jumlah']['total'] = floatval($val->{$v});
            }
        }
    }

    $data = [
        'tahun'   => $params['tahun'],
        'lokasi'  => isset($params['lokasi_nama']) ? $params['lokasi_nama'] : "PT. AMAK FIRDAUS UTOMO",
        'bulan'   => $arr_bulan,
    ];

    $detail = [
        'kategori'  => $arr,
        'barang'    => $arr2,
        'biaya'     => $arr3,
        'footer'    => $footer,
    ];

    if (isset($params['is_export']) && $params['is_export'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/rekap_pembelian_pertahun.html", [
            'data'    => $data,
            'detail'  => $detail,
            'css'     => modulUrl() . '/assets/css/style.css',
        ]);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"Rekap Pembelian Pertahun (" . $data['tahun'] . ").xls\"");
        echo $content;
    } elseif (isset($params['is_print']) && $params['is_print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/rekap_pembelian_pertahun.html", [
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
