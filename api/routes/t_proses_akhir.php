<?php

$app->get('/t_proses_akhir/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("
      inv_proses_akhir.*,
      FROM_UNIXTIME(inv_proses_akhir.created_at, '%Y-%m-%d') as dibuat_pada
      ")
    ->from("inv_proses_akhir");

    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'is_deleted') {
                $db->where("is_deleted", '=', $val);
            } else {
                $db->where($key, 'like', $val);
            }
        }
    }

    if (isset($params['sort'])) {
        $sort = $params['sort'];
        if (isset($params['order'])) {
            if ($params['order'] == "false") {
                $sort .= " ASC";
            } else {
                $sort .= " DESC";
            }
        }
        $db->orderBy($sort);
    }

    /** Set limit */
    if (isset($params['limit']) && !empty($params['limit'])) {
        $db->limit($params['limit']);
    }
    /** Set offset */
    if (isset($params['offset']) && !empty($params['offset'])) {
        $db->offset($params['offset']);
    }
    $models = $db->orderBy("id DESC")->findAll();
    $totalItem = $db->count();

    foreach ($models as $key => $value) {
        $models[$key]->id               = (int) $value->id;
        $models[$key]->tanggal_awal     = date("Y-m-d", strtotime($value->tanggal_awal));
        $models[$key]->tanggal_akhir    = date("Y-m-d", strtotime($value->tanggal_akhir));
    }

    return successResponse($response, [
        'list'        => $models,
        'totalItems'  => $totalItem,
    ]);
});

$app->post('/t_proses_akhir/trash', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $model = $db->update("inv_m_jenis", ['is_deleted' => $data['is_deleted']], array('id' => $data['id']));
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

function validasi($data, $custom = array()) {
    $validasi = array(
//        'jenis' => 'required'
    );
    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->post('/t_proses_akhir/save', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    date_default_timezone_set('Asia/Jakarta');

    if (validasi($params) !== true) {
        return unprocessResponse($response, $validasi);
    }

    try {
        $params['kode'] = generateNoTransaksi('proses akhir', 0);

//        OLD CODE
//        $penjualan = $db->find("SELECT tanggal FROM inv_penjualan WHERE inv_proses_akhir_id IS NULL ORDER BY id ASC LIMIT 1");
//        $pembelian = $db->find("SELECT tanggal FROM inv_pembelian WHERE inv_proses_akhir_id IS NULL ORDER BY id ASC LIMIT 1");
//
//        $tanggal_awal = $db->find("SELECT tanggal_akhir FROM inv_proses_akhir ORDER BY id ASC LIMIT 1");
//        $tanggal_awal = $tanggal_awal->tanggal_akhir;
//
//        if (!empty($penjualan) || !empty($pembelian)) {
//            if ($penjualan->tanggal > $pembelian->tanggal) {
//                $tanggal_awal = date("Y-m-d", $penjualan->tanggal);
//            } else {
//                $tanggal_awal = date("Y-m-d", $pembelian->tanggal);
//            }
//        }
//
//        $params['tanggal_awal'] = $tanggal_awal;
//        $params['tanggal_akhir'] = date("Y-m-d");
//
//        $dpp = $db->select("COALESCE(SUM(total), 0) as total")->from("inv_penjualan")->customWhere("inv_proses_akhir_id IS NULL")->find();
//        $params['dpp_penjualan'] = $dpp->total;
//
//        $model = $db->insert("inv_proses_akhir", $params);
//        $update = $db->run("UPDATE inv_penjualan SET inv_proses_akhir_id = '{$model->id}' WHERE inv_proses_akhir_id IS NULL");
//        $update = $db->run("UPDATE inv_pembelian SET inv_proses_akhir_id = '{$model->id}' WHERE inv_proses_akhir_id IS NULL");
//        END OLD CODE

        $params['tanggal_awal'] = date("Y-m", strtotime($params['tanggal'])) . "-01";
        $params['tanggal_akhir'] = date("Y-m-t", strtotime($params['tanggal']));

        $cek_data = $db->select("id")
                ->from("inv_proses_akhir")
                ->where("tanggal_awal", "=", $params['tanggal_awal'])
                ->where("tanggal_akhir", "=", $params['tanggal_akhir'])
                ->find();

        if ($cek_data) {
            return unprocessResponse($response, ['Periode ini sudah ditutup']);
            die;
        }

        $dpp = $db->select("COALESCE(SUM(total), 0) as total")
                ->from("inv_penjualan")
                ->where("tanggal", ">=", strtotime($params['tanggal_awal']))
                ->where("tanggal", "<=", strtotime($params['tanggal_akhir']))
                ->customWhere("inv_proses_akhir_id IS NULL", "AND")
                ->find();
        $params['dpp_penjualan'] = $dpp->total;

        $model = $db->insert("inv_proses_akhir", $params);

        $endDate = strtotime(date("Y-m-01", strtotime("+1 month" . $model->tanggal_awal)));

        $update_penjualan = $db->run("UPDATE inv_penjualan
          SET inv_proses_akhir_id = '{$model->id}'
          WHERE tanggal >= '" . strtotime($model->tanggal_awal) . "'
          AND tanggal < '" . $endDate . "'
          ");
        $update_pembelian = $db->run("UPDATE inv_pembelian
          SET inv_proses_akhir_id = '{$model->id}'
          WHERE tanggal >= '" . strtotime($model->tanggal_awal) ."'
          AND tanggal < '" . $endDate . "'
          ");

        // Jika berhasil simpan proses akhir, simpan jurnalnya
        if($model){
          $db->select("*")
          ->from("acc_m_lokasi")
          ->orderBy('acc_m_lokasi.kode_parent')
          ->where("parent_id", "=", 0)
          ->where("is_deleted", "=", 0)
          ->orderBy("id ASC");
          $lokasi = $db->find();

          $paramsJU = [
              'tanggal'       => date("Y-m-d"),
              'm_lokasi_id'   => [
                  'id'    => $lokasi->id,
                  'kode'  => $lokasi->kode
              ],
              'm_kontak_id'   => '',
              'total_debit'   => $params['listJurnal'][0]['debit'],
              'total_kredit'  => $params['listJurnal'][0]['debit'],
              'keterangan'    => $params['listJurnal'][0]['keterangan'],
              'detail'        => $params['listJurnal'],
              'reff_type'     => $params['listJurnal'][0]['reff_type'],
              'reff_id'       => $model->id,
          ];

          // Simpan jurnal umum
          simpanJurnalUmum($paramsJU);
        }

        return successResponse($response, $model);
    } catch (Exception $e) {
        return unprocessResponse($response, ['Terjadi kesalahan pada server!']);
    }
});

$app->post('/t_proses_akhir/getSummary', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    date_default_timezone_set('Asia/Jakarta');

    try {
      $params['tanggal_awal'] = date("Y-m", strtotime($params['tanggal'])) . "-01";

      $db->select("COALESCE(SUM(total), 0) as total")
        ->from("inv_penjualan")
        ->where("MONTH(FROM_UNIXTIME(tanggal))", "=", date("m", strtotime($params['tanggal_awal'])) )
        ->where("YEAR(FROM_UNIXTIME(tanggal))", "=", date("Y", strtotime($params['tanggal_awal'])) )
        ->customWhere("inv_proses_akhir_id IS NULL", "AND");
      $dpp_penjualan = $db->find();

      $db->select("COALESCE(SUM(total), 0) as total")
        ->from("inv_pembelian")
        ->where("MONTH(FROM_UNIXTIME(tanggal))", "=", date("m", strtotime($params['tanggal_awal'])) )
        ->where("YEAR(FROM_UNIXTIME(tanggal))", "=", date("Y", strtotime($params['tanggal_awal'])) )
        ->customWhere("inv_proses_akhir_id IS NULL", "AND");
        $db->andWhere("inv_pembelian.is_ppn", "=", 1);
        $db->andWhere("inv_pembelian.is_import", "=", 0);
      $dpp_pembelian = $db->find();
      // Get Jurnal Beban
      $paramsJurnal = [
        'bulan' => $params['tanggal_awal'],
      ];
      $getJurnal = getJurnalProsesAkhir($paramsJurnal);

      return successResponse($response, [
        'penjualan'   => !empty($dpp_penjualan) ? $dpp_penjualan->total : 0,
        'pembelian'   => !empty($dpp_pembelian) ? $dpp_pembelian->total : 0,
        'jurnal'      => $getJurnal,
      ]);
    } catch (Exception $e) {
        return unprocessResponse($response, [$e]);
    }
});

$app->get('/t_proses_akhir/generateJurnal', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $jurnalParams = $params;
    $jurnal = getJurnalProsesAkhir($jurnalParams);

    pd(['dari function', $jurnal]);

    date_default_timezone_set('Asia/Jakarta');
    try {
      $params['bulan_akhir']    = date("Y-m-01", strtotime($params['bulan']. " -1 Month") );
      $params['bulan_awal']     = date("Y-m-t", strtotime($params['bulan']) );
      $bulan_terpilih           = date("Y-m", strtotime($params['bulan']) );
      $listHPP = $listKategori  = [];

      /*
        Kamu harus dapatkan Stoknya pada bulan itu dan Harga nya
        Harga rata-rata itu adalah HPP nya...
        Trus lakkukan pengurangan pada stok dan ambil HPP nya.......
      */

      // Get Stok Masuk & Keluar dalam rentang 2 bulan
      $db->select("
        FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
        tanggal,
        inv_m_barang_id,
        inv_m_kategori.id as inv_m_kategori_id,
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

      $db->orderBy("tanggal ASC");
      $stokRentang = $db->findAll();
      // Get Stok Masuk & Keluar - END

      // Select stoknya
      $db->select("
        FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
        tanggal,
        inv_m_barang_id,
        inv_m_kategori.id as inv_m_kategori_id,
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
        $arrayStok[$value->inv_m_barang_id]['inv_m_kategori_id']                      = $value->inv_m_kategori_id;
        $arrayStok[$value->inv_m_barang_id]['bulan'][$value->bulan]['format_bulan']   = date("M y", strtotime($value->bulan . '-01'));
        $arrayStok[$value->inv_m_barang_id]['bulan_keluar']                           = $value->bulan;
        @$arrayStok[$value->inv_m_barang_id]['bulan'][$value->bulan]['masuk']         += $value->jumlah_masuk;

        @$arrayStok[$value->inv_m_barang_id]['keluar']  += $value->jumlah_keluar;
        @$arrayStok[$value->inv_m_barang_id]['masuk']   += $value->jumlah_masuk;

        if($value->jenis_kas == 'masuk' && $value->harga_masuk > 0){
          $arrayStok[$value->inv_m_barang_id]['bulan'][$value->bulan]['harga_masuk'][] = $value->harga_masuk;
        }
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

              if( $arrayStok[$key]['bulan'][$key2]['keluar'] > 0 ){
                $listHPP[$value['inv_m_kategori_id']][$value['bulan_keluar']][$key][] = [
                    'jumlah'          => $arrayStok[$key]['bulan'][$key2]['keluar'],
                    'inv_m_barang_id' => $key,
                    'bulan_stok'    => $key2,
                  ];
              }

              $jumlah_keluar -= $arrayStok[$key]['bulan'][$key2]['keluar'];

            } else if($value2['masuk'] == $jumlah_keluar){
              $arrayStok[$key]['bulan'][$key2]['keluar']  = $value2['masuk'];
              $arrayStok[$key]['bulan'][$key2]['sisa']    = 0;

              if( $arrayStok[$key]['bulan'][$key2]['keluar'] > 0 ){
                $listHPP[$value['inv_m_kategori_id']][$value['bulan_keluar']][$key][] = [
                    'jumlah'          => $arrayStok[$key]['bulan'][$key2]['keluar'],
                    'inv_m_barang_id' => $key,
                    'bulan_stok'    => $key2,
                  ];
              }

              $jumlah_keluar -= $arrayStok[$key]['bulan'][$key2]['keluar'];

            } else if($value2['masuk'] < $jumlah_keluar){
              $arrayStok[$key]['bulan'][$key2]['keluar']  = $value2['masuk'];
              $arrayStok[$key]['bulan'][$key2]['sisa']    = 0;

              if( $arrayStok[$key]['bulan'][$key2]['keluar'] > 0 ){
                $listHPP[$value['inv_m_kategori_id']][$value['bulan_keluar']][$key][] = [
                    'jumlah'          => $arrayStok[$key]['bulan'][$key2]['keluar'],
                    'inv_m_barang_id' => $key,
                    'bulan_stok'    => $key2,
                  ];
              }

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

      // list kategori + akun
      $db->select("
      inv_m_kategori.id,
      inv_m_kategori.akun_pembelian_id,
      inv_m_kategori.akun_penjualan_id,
      inv_m_kategori.akun_hpp_id,
      inv_m_kategori.akun_persediaan_brg_id
      ")
      ->from("inv_m_kategori");
      $getAkunKategori = $db->findAll();

      $listAkunKategori = [];
      foreach ($getAkunKategori as $key => $value) {
        $listAkunKategori[$value->id] = [
          'akun_pembelian_id'       => $value->akun_pembelian_id,
          'akun_penjualan_id'       => $value->akun_penjualan_id,
          'akun_hpp_id'             => $value->akun_hpp_id,
          'akun_persediaan_brg_id'  => $value->akun_persediaan_brg_id,
        ];
      }
      // list kategori + akun - END

      // Inisiasi HPP
      $detJurnal = $totalPerkategori = [];
      foreach ($listHPP as $key => $value) { // kategori
        foreach ($value as $key2 => $value2) { // bulan
          foreach ($value2 as $key3 => $value3) { // barang
            foreach ($value3 as $key4 => $value4) { // list barang
              // Inisiasi HPP
              // kategori, bulan, barang, index
              $listHPP[$key][$key2][$key3][$key4]['hpp'] = isset($arrayStok[$key3]['bulan'][$value4['bulan_stok']]['harga_masuk_avg']) ? $arrayStok[$key3]['bulan'][$value4['bulan_stok']]['harga_masuk_avg'] :  0;

              // total per kategori
              @$totalPerkategori[$key] += $listHPP[$key][$key2][$key3][$key4]['hpp'] * $value4['jumlah'];
            }
          }
        }
      }

      $totalAllKategori = 0;
      foreach ($listAkunKategori as $key => $value) {
        $totalAllKategori += isset($totalPerkategori[$key]) ? $totalPerkategori[$key] : 0;
        // Debit
        if(isset($totalPerkategori[$key]) && isset($totalPerkategori[$key]) > 0){
          $m_akun_id = $value['akun_hpp_id'];
          if( !empty($detJurnal[$value['akun_hpp_id']]) ){
            @$detJurnal[$value['akun_hpp_id']]['debit']    += isset($totalPerkategori[$key]) ? $totalPerkategori[$key] : 0;
          } else {
            $detJurnal[$value['akun_hpp_id']]['debit']       = isset($totalPerkategori[$key]) ? $totalPerkategori[$key] : 0;
            $detJurnal[$value['akun_hpp_id']]['kredit']      = 0;
            $detJurnal[$value['akun_hpp_id']]['type']        = 'debit';
            $detJurnal[$value['akun_hpp_id']]['tanggal']     = $params['bulan_awal'];
            $detJurnal[$value['akun_hpp_id']]['m_lokasi_id'] = 1;
            $detJurnal[$value['akun_hpp_id']]['m_akun_id']   = $value['akun_hpp_id'];
            $detJurnal[$value['akun_hpp_id']]['keterangan']  = 'Tutup Periode ' . date("F Y", strtotime($params['bulan_awal']));
            $detJurnal[$value['akun_hpp_id']]['kode']        = 'Tutup Periode';
            $detJurnal[$value['akun_hpp_id']]['reff_type']   = 'inv_proses_akhir';
            $detJurnal[$value['akun_hpp_id']]['reff_id']     = 1;
          }

          // Kredit
          $m_akun_id = $value['akun_persediaan_brg_id'];
          if( !empty($detJurnal[$value['akun_persediaan_brg_id']]) ){
            @$detJurnal[$value['akun_persediaan_brg_id']]['kredit']     += isset($totalPerkategori[$key]) ? $totalPerkategori[$key] : 0;
          } else {
            $detJurnal[$value['akun_persediaan_brg_id']]['debit']       = 0;
            $detJurnal[$value['akun_persediaan_brg_id']]['kredit']      = isset($totalPerkategori[$key]) ? $totalPerkategori[$key] : 0;
            $detJurnal[$value['akun_persediaan_brg_id']]['type']        = 'kredit';
            $detJurnal[$value['akun_persediaan_brg_id']]['tanggal']     = $params['bulan_awal'];
            $detJurnal[$value['akun_persediaan_brg_id']]['m_lokasi_id'] = 1;
            $detJurnal[$value['akun_persediaan_brg_id']]['m_akun_id']   = $value['akun_persediaan_brg_id'];
            $detJurnal[$value['akun_persediaan_brg_id']]['keterangan']  = 'Tutup Periode ' . date("F Y", strtotime($params['bulan_awal']));
            $detJurnal[$value['akun_persediaan_brg_id']]['kode']        = 'Tutup Periode';
            $detJurnal[$value['akun_persediaan_brg_id']]['reff_type']   = 'inv_proses_akhir';
            $detJurnal[$value['akun_persediaan_brg_id']]['reff_id']     = 1;
          }

        }
      }

      foreach ($detJurnal as $key => $value) {
        $m_akun_nama = $db->find("SELECT nama FROM acc_m_akun WHERE id=" . $value['m_akun_id']);
        $detJurnal[$key]['m_akun_nama'] = !empty($m_akun_nama->nama) ? $m_akun_nama->nama : '';
      }
pd([
  $detJurnal,
  $totalPerkategori,
  $listHPP,
  $arrayStok,
]);
      return successResponse($response, [
        'penjualan' => !empty($dpp_penjualan) ? $dpp_penjualan->total : 0,
        'pembelian' => !empty($dpp_pembelian) ? $dpp_pembelian->total : 0,
      ]);
    } catch (Exception $e) {
        return unprocessResponse($response, ['Terjadi kesalahan pada server!']);
    }
});
