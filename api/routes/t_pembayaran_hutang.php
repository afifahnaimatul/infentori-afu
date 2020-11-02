<?php

date_default_timezone_set('Asia/Jakarta');

function validasi($data, $custom = array()) {
    $validasi = array(
        'tanggal'   => 'required',
        'akun'      => 'required',
        'lokasi'    => 'required',
        'total'     => 'required',
        'supplier'  => 'required',
        // 'm_akun_denda_id' => 'required',
        // 'm_unker_id'   => 'required',
    );

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

/**
 * get view
 */
$app->get('/t_pembayaran_hutang/view', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select("
      inv_pembelian.*,
      acc_bayar_hutang_det.*,
      FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') as tanggal,
      acc_bayar_hutang_det.status as status_bayar,
      FROM_UNIxtime(acc_bayar_hutang_det.tanggal, '%Y-%m-%d') as tanggal_bayar,
      acc_m_akun.id acc_m_akun_id,
      acc_m_akun.kode acc_m_akun_kode,
      acc_m_akun.nama acc_m_akun_nama,
      inv_m_faktur_pajak.nomor as nomor_fp
    ")
    ->from("acc_bayar_hutang_det")
    ->leftJoin("inv_pembelian", "inv_pembelian.id= acc_bayar_hutang_det.inv_pembelian_id")
    ->leftJoin("acc_m_akun", "acc_m_akun.id= acc_bayar_hutang_det.m_akun_id")
    ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_pembelian.inv_m_faktur_pajak_id = inv_m_faktur_pajak.id")
    ->where("acc_bayar_hutang_det.acc_bayar_hutang_id", "=", $params['id']);
    $db->orderBy("acc_bayar_hutang_det.id DESC");
    $models = $db->findAll();

    $listBayarHutang = [];
    foreach ($models as $val) {
      $listBayarHutang[$val->inv_pembelian_id]['id']               = $val->acc_bayar_hutang_id;
      $listBayarHutang[$val->inv_pembelian_id]['inv_pembelian_id'] = $val->inv_pembelian_id;
      $listBayarHutang[$val->inv_pembelian_id]['status']           = $val->status_bayar;
      $listBayarHutang[$val->inv_pembelian_id]['tanggal']          = $val->tanggal;
      $listBayarHutang[$val->inv_pembelian_id]['is_pelunasan']     = $val->is_pelunasan;
      $listBayarHutang[$val->inv_pembelian_id]['nomor_fp']         = $val->nomor_fp;

      $listBayarHutang[$val->inv_pembelian_id]['sisa'] = $val->hutang;
      // if($val->status_bayar != 'terposting'){
      // }else{
      //   $listBayarHutang[$val->inv_pembelian_id]['sisa'] = $val->is_pelunasan == 1 ? 0: $val->sisa - ($val->bayar - $val->sisa_pelunasan);
      // }
        // $val->sisa  = $val->sisa - ($val->bayar - $val->sisa_pelunasan);

      if( !empty($val->acc_m_akun_id) ){
        $val->m_akun_id = [
          'id'      => $val->acc_m_akun_id,
          'kode'    => $val->acc_m_akun_kode,
          'nama'    => $val->acc_m_akun_nama,
        ];
      }

      $listBayarHutang[$val->inv_pembelian_id]['listBayar'][]      = [
        'id'              => $val->id,
        'm_akun_id'       => $val->m_akun_id,
        'tanggal'         => $val->tanggal_bayar,
        'bayar'           => $val->bayar,
        'sisa_pelunasan'  => $val->sisa_pelunasan,
        'is_pelunasan'    => $val->is_pelunasan,
      ];

      usort($listBayarHutang[$val->inv_pembelian_id]['listBayar'], function($a, $b) {
          return $a['id'] <=> $b['id'];
      });
    }

    $listBayarHutang = array_values($listBayarHutang);

    return successResponse($response, [
      'list'        => $listBayarHutang,
      'akun_hutang' => []
    ]);
});

/*
 * get jurnal
 */
$app->get('/t_pembayaran_hutang/getJurnal', function ($request, $response) {
    $param = $request->getParams();
    $db = $this->db;

    if (isset($param['id']) && !empty($param['id'])) {
        $db->select("acc_trans_detail.*, acc_m_akun.nama as namaAkun, acc_m_akun.kode as kodeAkun")
                ->from("acc_trans_detail")
                ->leftJoin("acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
                ->orderBy("tanggal, acc_trans_detail.id ASC")
                ->where('reff_type', '=', 'acc_bayar_hutang')->where('reff_id', '=', $param['id']);
        $model = $db->findAll();

        $debit = $kredit = 0;
        foreach ($model as $key => $val) {

            if ($val->debit != 0) {
                $val->tipe = "debit";
            } else {
                $val->tipe = "kredit";
            }

            $val->akun = [
                'id' => $val->m_akun_id,
                'kode' => $val->kodeAkun,
                'nama' => $val->namaAkun,
            ];


            $debit += $val->debit;
            $kredit += $val->kredit;
        }

        $total['totalDebit'] = $debit;
        $total['totalKredit'] = $kredit;

        return successResponse($response, ["total" => $total, "detail" => $model]);
    }

    return unprocessResponse($response, ["total" => [], "detail" => []]);
});

/** Ambil data supplier yang belum dihapus */
$app->get('/t_pembayaran_hutang/getSupplier', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;
    $search = $params['val'];
    $models = $db->select("*")
            ->from("m_supplier")
            ->where("is_deleted", "=", 0)
            ->customWhere('nama LIKE "%' . $search . '%"', 'AND')
            ->limit(50)
            ->findAll();

    return successResponse($response, ['list' => $models]);
});


/**
 * get list hutang
 */
$app->get('/t_pembayaran_hutang/getListHutang', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select("
      inv_pembelian.id as inv_pembelian_id,
      FROM_UNIxtime(inv_pembelian.tanggal, '%Y-%m-%d') as tanggal,
      inv_pembelian.no_invoice,
      inv_pembelian.pib,
      inv_pembelian.total,
      inv_pembelian.total_edit,
      inv_pembelian.is_import,
      inv_pembelian.is_ppn,
      inv_pembelian.hutang,
      inv_pembelian.ppn_edit,
      inv_pembelian.ppn,
      inv_m_faktur_pajak.nomor as nomor_fp
    ")
    ->from("inv_pembelian")
    ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_pembelian.inv_m_faktur_pajak_id = inv_m_faktur_pajak.id")
    ->where("inv_pembelian.acc_m_lokasi_id", "=", $params['lokasi_id'])
    ->andWhere("inv_pembelian.acc_m_kontak_id", "=", $params['supplier_id'])
    ->andWhere("inv_pembelian.status", "!=", 'lunas')
    ->andWhere("inv_pembelian.status", "!=", 'draft')
    ->groupBy("inv_pembelian.id");
    $model = $db->findAll();

    foreach ($model as $val) {
        $db->select("sum((acc_bayar_hutang_det.bayar - acc_bayar_hutang_det.sisa_pelunasan)) as bayar")
                ->from("acc_bayar_hutang_det")
                ->leftJoin("inv_pembelian", "inv_pembelian.id = acc_bayar_hutang_det.inv_pembelian_id")
                ->where("acc_bayar_hutang_det.status", "=", "terposting")
                ->andWhere("acc_bayar_hutang_det.inv_pembelian_id", "=", $val->inv_pembelian_id)
                ->groupBy("acc_bayar_hutang_det.inv_pembelian_id");
        $bayar = $db->find();

        $val->sisa      = $val->hutang - (isset($bayar->bayar) ? $bayar->bayar : 0);
        $val->listBayar = [];
    }

    return successResponse($response, [
      'list'          => $model,
    ]);
});

$app->get('/t_pembayaran_hutang/index', function ($request, $response) {
    $params = $request->getParams();
    $sort = "acc_bayar_hutang.tanggal DESC";
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 20;

    $db = $this->db;
    $db->select("
      acc_bayar_hutang.*,
      acc_m_kontak.nama as namaSup,
      acc_m_kontak.kode as kodeSup,
      acc_m_user.nama as namaUser,
      kas.nama as namaKas,
      kas.kode as kodeKas,
      denda.nama as namaDenda,
      denda.kode as kodeDenda,
      acc_m_lokasi.nama as namaLokasi,
      acc_m_lokasi.kode as kodeLokasi
    ")
    ->from('acc_bayar_hutang')
    ->join('left join', 'acc_m_kontak', 'acc_m_kontak.id = acc_bayar_hutang.m_kontak_id')
    ->join('left join', 'acc_m_user', 'acc_m_user.id = acc_bayar_hutang.created_by')
    ->join('left join', 'acc_m_akun kas', 'kas.id = acc_bayar_hutang.m_akun_id')
    ->join('left join', 'acc_m_akun denda', 'denda.id = acc_bayar_hutang.m_akun_denda_id')
    ->join('left join', 'acc_m_lokasi', 'acc_m_lokasi.id = acc_bayar_hutang.m_lokasi_id')
    ->orderBy($sort);
//        ->where("acc_pemasukan.is_deleted", "=", 0);

    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);

        foreach ($filter as $key => $val) {
            if ($key == 'is_deleted') {
                $db->where("acc_kasbon.is_deleted", '=', $val);
            } else {
                $db->where($key, 'LIKE', $val);
            }
        }
    }

    /** Set supplier */
    if (isset($params['m_supplier_id']) && !empty($params['m_supplier_id'])) {
        $db->where("acc_m_kontak.id", "=", $params['m_supplier_id']);
    }

    /** Set limit */
    if (isset($params['limit']) && !empty($params['limit'])) {
        $db->limit($params['limit']);
    }

    /** Set offset */
    if (isset($params['offset']) && !empty($params['offset'])) {
        $db->offset($params['offset']);
    }

    $models = $db->findAll();
    $totalItem = $db->count();

    foreach ($models as $val) {
        $val->supplier          = ['id' => $val->m_kontak_id, 'nama' => $val->namaSup, 'kode' => $val->kodeSup];
        $val->akun              = ['id' => $val->m_akun_id, 'nama' => $val->namaKas, 'kode' => $val->kodeKas];
        $val->akun_denda        = ['id' => $val->m_akun_denda_id, 'nama' => $val->namaDenda, 'kode' => $val->kodeDenda];
        $val->lokasi            = ['id' => $val->m_lokasi_id, 'nama' => $val->namaLokasi, 'kode' => $val->kodeLokasi];
        $val->status            = ucfirst($val->status);
        $val->tanggal_formated  = date("d-m-Y", strtotime($val->tanggal));
        $val->created_at        = date("d-m-Y H:i", $val->created_at);
    }

    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
        'base_url' => str_replace('api/', '', config('SITE_URL'))
    ]);
});

$app->post('/t_pembayaran_hutang/save', function ($request, $response) {
    $params   = $request->getParams();
    $data     = $params;
    $sql      = $this->db;
    $validasi = validasi($data['form']);

    if ($validasi !== true) {
      return unprocessResponse($response, $validasi);
    }

    if( !empty($params['detail']) ){
      foreach ($params['detail'] as $key => $value) {
        foreach ($value['listBayar'] as $key2 => $value2) {
          if( $value2['bayar'] > 0 && empty($value2['m_akun_id']) ){
            return unprocessResponse($response, ['Harap pilih Akun Keluar Dari!']);
          }
        }
      }
    }

    $kode = generateNoTransaksi("afu_pembayaran_hutang", 0);
    $insert['m_kontak_id']  = $data['form']['supplier']['id'];
    $insert['m_lokasi_id']  = $data['form']['lokasi']['id'];
    $insert['tanggal']      = date("Y-m-d h:i:s", strtotime($data['form']['tanggal']));
    $insert['total']        = $data['form']['total'];
    $insert['status']       = $data['form']['status'];
    $insert['keterangan']   = (isset($data['form']['keterangan']) && !empty($data['form']['keterangan']) ? $data['form']['keterangan'] : '');
    $insert['tgl_mulai']    = isset($data['form']['startDate']) ? $data['form']['startDate'] : null;
    $insert['tgl_selesai']  = isset($data['form']['endDate']) ? $data['form']['endDate'] : null;
    // $insert['m_akun_id']    = $data['form']['akun']['id'];

    if (isset($data['form']['id']) && !empty($data['form']['id'])) {
        $insert['kode'] = $data['form']['kode'];
        $model = $sql->update("acc_bayar_hutang", $insert, ["id" => $data['form']['id']]);
    } else {
        $insert['kode'] = $kode;
        $model = $sql->insert("acc_bayar_hutang", $insert);
    }

    $deletedetail       = $sql->delete("acc_bayar_hutang_det", ["acc_bayar_hutang_id" => $model->id]);
    $deletetransdetail  = $sql->delete("acc_trans_detail", ["reff_id" => $model->id, "reff_type" => "acc_bayar_hutang"]);

    if (!empty($params['detail'])) {
        foreach ($params['detail'] as $val) {
          foreach ($val['listBayar'] as $key2 => $value2) {

            $detail['sisa_pelunasan'] = $value2['bayar'] - $val['sisa'];
            if( ($value2['bayar'] > 0 || $detail['sisa_pelunasan'] != 0) && !empty($value2['m_akun_id']['id']) ){
              $detail['kode']                 = $model->kode;
              $detail['acc_bayar_hutang_id']  = $model->id;
              $detail['inv_pembelian_id']     = $val['inv_pembelian_id'];
              $detail['tanggal']              = strtotime($value2['tanggal']);
              $detail['m_akun_id']            = $value2['m_akun_id']['id'];
              $detail['sisa']                 = $val['sisa'];
              $detail['bayar']                = $value2['bayar'];
              $detail['status']               = $model->status;
              $detail['created_at']           = $model->created_at;
              $detail['is_pelunasan']         = !empty($val['check']) ? $val['check'] : 0;

              if( $key2 == (sizeof($val['listBayar'])-1) ){
                $detail['is_pelunasan']         = !empty($val['check']) ? $val['check'] : 0;
                $detail['sisa_pelunasan']       = $value2['bayar'] - $val['sisa'];
                $detail['sisa_pelunasan']       = !empty($val['check']) && $val['check'] == 1 ? $detail['sisa_pelunasan'] : 0;
              } else {
                $detail['sisa_pelunasan']       = 0;
              }

              // Catatan : berisi FP / No Invoice / PIB
              $catatanPembelian               = '';
              $catatanPembelian = isset($val['no_invoice']) && $val['no_invoice'] != '' ? $val['no_invoice'] : $catatanPembelian;
              $catatanPembelian = isset($val['nomor_fp']) && $val['nomor_fp'] != '' ? $val['nomor_fp'] : $catatanPembelian;
              $catatanPembelian = isset($val['is_import']) && $val['is_import'] == 1 ? $val['pib'] : $catatanPembelian;
              $detail['catatan']              = $catatanPembelian;

              $sql->insert("acc_bayar_hutang_det", $detail);
              $val['sisa'] -= $value2['bayar'];
            }

            // Update sisa hutang pembelian
            if ($data['form']['status'] == "terposting") {
              if( $detail['is_pelunasan'] == 1 || $detail['sisa'] - ($detail['bayar'] - $detail['sisa_pelunasan']) <= 0 ){
                $sql->run("UPDATE inv_pembelian SET status = 'lunas' WHERE id = {$val['inv_pembelian_id']} ");
              }
            }
          } // end of foreach 2
        } // end of foreach 1

      if ($data['form']['status'] == "terposting") {
        $paramsJurnal = [
          'reff_type'   => 'acc_bayar_hutang',
          'reff_id'     => $model->id,
        ];

        simpanJurnal($paramsJurnal);
      }
    }

    // if(!empty($data['jurnal'])){
    //   foreach ($data['jurnal'] as $key => $val) {
    //     $data['jurnal'][$key]['m_akun_id']      = $val['akun']['id'];
    //     $data['jurnal'][$key]['kode']           = $model->kode;
    //     $data['jurnal'][$key]['tanggal']        = date("Y-m-d", strtotime($data['form']['tanggal']));
    //     $data['jurnal'][$key]['reff_type']      = "acc_bayar_hutang";
    //     $data['jurnal'][$key]['reff_id']        = $model->id;
    //     $data['jurnal'][$key]['m_kontak_id']    = $model->m_kontak_id;
    //     $data['jurnal'][$key]['m_lokasi_id']    = $model->m_lokasi_id;
    //   }
    //
    //   if ($data['form']['status'] == "terposting") {
    //     insertTransDetail($data['jurnal']);
    //   }
    // }

    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Data Gagal Di Simpan']);
    }
});

$app->post('/t_pembayaran_hutang/saveJurnal', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;
//    print_r($data);die();
    $model = $db->find("select * from acc_bayar_hutang where id = '" . $data['id'] . "'");
    if (isset($model->id)) {
        $db->delete("acc_trans_detail", ["reff_type" => "acc_bayar_hutang", "reff_id" => $model->id]);
        if (isset($data['detail']) && !empty($data['detail'])) {
            $transDetail = [];
            foreach ($data['detail'] as $key => $val) {
                $m_akun_id = isset($val['akun']['id']) ? $val['akun']['id'] : 0;
                $transDetail[$key]['m_kontak_id'] = $val['m_kontak_id'];
                $transDetail[$key]['m_akun_id'] = $val['akun']['id'];
                $transDetail[$key]['tanggal'] = date("Y-m-d", strtotime($val['tanggal']));
                $transDetail[$key]['debit'] = $val['debit'];
                $transDetail[$key]['kredit'] = $val['kredit'];
                $transDetail[$key]['reff_type'] = "acc_bayar_hutang";
                $transDetail[$key]['reff_id'] = $model->id;
                $transDetail[$key]['kode'] = $model->kode;
                $transDetail[$key]['keterangan'] = 'Pembayaran Hutang (' . $model->kode . ')';
            }
            insertTransDetail($transDetail);
        }
    }

    return successResponse($response, []);
});

$app->post('/t_pembayaran_hutang/delete', function ($request, $response) {

    $data = $request->getParams();
    $db = $this->db;


    $model = $db->delete("acc_bayar_hutang", ['id' => $data['id']]);
    $model = $db->delete("acc_bayar_hutang_det", ['acc_bayar_hutang_id' => $data['id']]);
    $model = $db->delete("acc_trans_detail", ["reff_type" => "acc_bayar_hutang", "reff_id" => $data['id']]);
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

$app->get('/t_pembayaran_hutang/print', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $data = $db->select("acc_bayar_hutang.*, acc_m_lokasi.nama as namaLokasi, acc_m_kontak.nama as namaKontak, acc_m_akun.nama as namaAkun")
                    ->from("acc_bayar_hutang")
                    ->join("JOIN", "acc_m_lokasi", "acc_m_lokasi.id = acc_bayar_hutang.m_lokasi_id")
                    ->join("JOIN", "acc_m_kontak", "acc_m_kontak.id = acc_bayar_hutang.m_kontak_id")
                    ->join("JOIN", "acc_m_akun", "acc_m_akun.id = acc_bayar_hutang.m_akun_id")
                    ->where("acc_bayar_hutang.id", "=", $params['id'])->find();
    $arr = $db->select("acc_bayar_hutang_det.*, inv_pembelian.kode as kodeHutang")
            ->from("acc_bayar_hutang_det")
            ->join("JOIN", "inv_pembelian", "inv_pembelian.id = acc_bayar_hutang_det.inv_pembelian_id")
            ->where("acc_bayar_hutang_id", "=", $data->id)
            ->where("bayar", ">", 0)
            ->findAll();


    $data->sisa_bayar = 0;
    $data->invoice = [];
    foreach ($arr as $key => $val) {
        $val->sisa_bayar = $val->sisa - $val->bayar;
        $data->sisa_bayar += intval($val->sisa_bayar);
        $data->invoice[] = $val->kodeHutang;
    }

    $data->invoice = implode(", ", $data->invoice);
    $data->user = $_SESSION['user']['nama'];
    $data->terbilang = terbilang($data->total);

//    echo "<pre>", print_r($data), "</pre>";
//    echo "<pre>", print_r($arr), "</pre>";
//    die;



    $view = twigViewPath();
    if ($params['tipe'] == "voucher") {
        $content = $view->fetch('laporan/voucherHutang.html', [
            "data" => $data,
            "detail" => $arr,
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
    } else {
        $content = $view->fetch('laporan/kwitansiHutang.html', [
            "data" => $data,
            "detail" => $arr,
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
    }

    echo $content;
    echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
});

$app->post('/t_pembayaran_hutang/unpost', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    // Update status pembayaran piutang
    $model = $db->update("acc_bayar_hutang_det", ['status' => 'draft'], ['acc_bayar_hutang_id' => $data['id']]);
    $model = $db->update("acc_bayar_hutang", ['status' => 'draft'], ['id' => $data['id']]);

    // Update status penjualan
    $getPenjualan = $db->select("inv_pembelian_id")
      ->from("acc_bayar_hutang_det")
      ->where("acc_bayar_hutang_id", "=", $data['id'])
      ->groupBy("inv_pembelian_id")
      ->findAll();

    if( !empty($getPenjualan) ){
      foreach ($getPenjualan as $key => $value) {
        $db->update("inv_pembelian", ["status" => 'belum lunas'], ['id' => $value->inv_pembelian_id]);
      }
    }

    // Hapus jurnal
    $paramsHJU = [
      'reff_type'   => 'acc_bayar_hutang',
      'reff_id'     => $data['id'],
    ];
    hapusJurnalUmum($paramsHJU);

    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});
