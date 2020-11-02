<?php

$app->get("/l_rekap_pembelian/laporan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    //bulan
    $params['bulan_awal'] = $params['bulan_awal'] . "-01";
    $params['bulan_akhir'] = date("Y-m-t", strtotime($params['bulan_akhir']));

    //nama bulan
    $list_month = $listBulan = [];
    $time = strtotime($params['bulan_awal']);
    $last = date('F Y', strtotime($params['bulan_akhir']));
    do {
        $month = date('F Y', $time);
        $listBulan[] = date("Y-m", $time);
        $list_month[] = $month;

        $time = strtotime('+1 month', $time);
    } while ($month != $last);

    //deklarasi bulan per barang
    $arr_tes = [];
    foreach ($list_month as $key => $val) {
        $arr_tes[$val] = [];
    }

    $db->select("
      inv_m_barang_id,
      jumlah as jumlah_masuk,
      FROM_UNIXTIME(inv_pembelian.tanggal, '%M %Y') as tanggal,
      inv_pembelian.tanggal as tgl_lengkap,
      harga as harga_beli,
      inv_pembelian_det.diskon
    ")
    ->from("inv_pembelian_det")
    ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_pembelian_det.inv_m_barang_id")
    ->join("JOIN", "inv_m_kategori", "inv_m_barang.inv_m_kategori_id = inv_m_kategori.id")
    ->join("JOIN", "inv_pembelian", "inv_pembelian.id= inv_pembelian_det.inv_pembelian_id")
    ->customWhere("FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') >= '{$params['bulan_awal']}'", "AND")
    ->customWhere("FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') <= '{$params['bulan_akhir']}'", "AND")
    ->where("inv_pembelian.is_ppn", "=", $params['is_ppn'])
    ->where('inv_m_kategori.jenis_barang', '=', 'pembelian');

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_barang.inv_m_kategori_id", "=", $params['kategori']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("inv_pembelian.acc_m_lokasi_id", "=", $params['lokasi']);
    }

    $db->customWhere('inv_pembelian.inv_proses_akhir_id > 0', 'AND');

    $stok = $db->findAll();

    $arr = [];
    foreach ($stok as $key => $val) {
        if (!isset($arr[$val->inv_m_barang_id])) {
            $arr[$val->inv_m_barang_id] = $arr_tes;
        }
    }

    $totalSemua = $totalPerBulan = $total_akhir = $total_akhir_rp = [];

    foreach ($stok as $key => $val) {
        if (isset($total_akhir[$val->inv_m_barang_id])) {
            $total_akhir[$val->inv_m_barang_id] += $val->jumlah_masuk;
            $total_akhir_rp[$val->inv_m_barang_id] += ($val->jumlah_masuk * $val->harga_beli) - $val->diskon;
        } else {
            $total_akhir[$val->inv_m_barang_id] = $val->jumlah_masuk;
            $total_akhir_rp[$val->inv_m_barang_id] = ($val->jumlah_masuk * $val->harga_beli) - $val->diskon;
        }

        if (isset($arr[$val->inv_m_barang_id][$val->tanggal]['jumlah_masuk'])) {
            $arr[$val->inv_m_barang_id][$val->tanggal]['jumlah_masuk'] += $val->jumlah_masuk;
            $arr[$val->inv_m_barang_id][$val->tanggal]['total'] += $val->harga_beli * $val->jumlah_masuk - $val->diskon;
        } else {
            $arr[$val->inv_m_barang_id][$val->tanggal] = (array) $val;
            $arr[$val->inv_m_barang_id][$val->tanggal]['total'] = $val->harga_beli * $val->jumlah_masuk - $val->diskon;
        }

        // Total perbulan
        $bulanLengkap = date("Y-m", $val->tgl_lengkap);
        @$totalPerBulan[$bulanLengkap]['total']   += ($val->harga_beli * $val->jumlah_masuk) - $val->diskon;
        @$totalPerBulan[$bulanLengkap]['kwt']     += $val->jumlah_masuk;

        @$totalSemua['total']   += ($val->harga_beli * $val->jumlah_masuk) - $val->diskon;
        @$totalSemua['kwt']     += $val->jumlah_masuk;
        // Total perbulan - END
    }

    $db->select("inv_m_barang.*")
            ->from("inv_m_barang")
            ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->where("inv_m_kategori.jenis_barang", "=", "pembelian")
            ->where("is_pakai", "=", 0)
            ->where("inv_m_barang.is_deleted", "=", 0);

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori_id", "=", $params['kategori']);
    }

    $barang = $db->findAll();

    $index      = 0;
    $arr_hasil  = [];

    foreach ($barang as $key => $val) {
        $val->no              = $key + 1;
        $val->detail          = isset($arr[$val->id]) ? $arr[$val->id] : $arr_tes;
        $val->total_akhir     = isset($total_akhir[$val->id]) ? $total_akhir[$val->id] : 0;
        $val->total_akhir_rp  = isset($total_akhir_rp[$val->id]) ? $total_akhir_rp[$val->id] : 0;

        $arr_hasil[$index] = (array) $val;
        $index = $index + 1;
    }

    $arr_month = [];
    foreach ($list_month as $key => $val) {
        $arr_month[$val] = [
            'Beli ' . $val . ' (kwt)', 'Beli ' . $val . ' (rp)',
        ];

        // Beri default value

    }
    foreach ($listBulan as $key => $value) {
      // code...
      if( !isset($totalPerBulan[$value]) ){
        $totalPerBulan[$value] = [
          'kwt'     => 0,
          'total'   => 0,
        ];
      }
    }

    $totalPerBulanDet = [];
    ksort($totalPerBulan);

    foreach ($totalPerBulan as $key => $value) {
      $totalPerBulanDet[] = $value['kwt'];
      $totalPerBulanDet[] = $value['total'];
    }
    
    $data = [
        'periode'       => strtoupper(date("F Y", strtotime($params['bulan_awal'])) . " - " . date("F Y", strtotime($params['bulan_akhir']))),
        'lokasi'        => isset($params['lokasi_nama']) && !empty($params['lokasi_nama']) ? strtoupper($params['lokasi_nama']) : "PT. AMAK FIRDAUS UTOMO",
        'kategori'      => isset($params['kategori_nama']) && !empty($params['kategori_nama']) ? strtoupper($params['kategori_nama']) : "SEMUA KATEGORI",
        'list_bulan'    => $arr_month,
        'totalSemua'    => $totalSemua,
        'totalPerBulan' => $totalPerBulanDet
    ];

    $detail = $arr_hasil;

    if (isset($params['is_export']) && $params['is_export'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/rekap_pembelian.html", [
            'data'    => $data,
            'detail'  => $detail,
            'css'     => modulUrl() . '/assets/css/style.css',
        ]);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"Rekap Pembelian (" . $data['periode'] . ").xls\"");
        echo $content;
    } elseif (isset($params['is_print']) && $params['is_print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/rekap_pembelian_fix.html", [
            'data'    => $data,
            'detail'  => $detail,
            'css'     => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, [
          'data'          => $data,
          'detail'        => $detail,
          'totalSemua'    => $totalSemua,
          'totalPerBulan' => $totalPerBulanDet
        ]);
    }
});
