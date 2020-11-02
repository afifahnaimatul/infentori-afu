<?php

date_default_timezone_set('Asia/Jakarta');

function validasi($data, $custom = array()) {
    $validasi = array(
        'lokasi'    => 'required',
        'total'     => 'required',
        'customer'  => 'required',
    );

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->get('/t_pembayaran_piutang/view', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select("
      acc_bayar_piutang_det.*,
      acc_bayar_piutang_det.status as status_bayar,
      FROM_UNIxtime(acc_bayar_piutang_det.tanggal, '%Y-%m-%d') as tanggal_bayar,
      FROM_UNIxtime(inv_penjualan.tanggal, '%Y-%m-%d') as tanggal,
      inv_penjualan.is_ppn,
      inv_penjualan.no_surat_jalan,
      inv_penjualan.acc_m_lokasi_id,
      inv_penjualan.acc_m_kontak_id,
      inv_penjualan.status,
      inv_penjualan.piutang,
      inv_m_faktur_pajak.nomor as nomor_fp,
      acc_m_akun.id acc_m_akun_id,
      acc_m_akun.kode acc_m_akun_kode,
      acc_m_akun.nama acc_m_akun_nama,
      inv_penjualan.inv_m_faktur_pajak_id
    ")
    ->from("acc_bayar_piutang_det")
    ->leftJoin("inv_penjualan", "inv_penjualan.id= acc_bayar_piutang_det.inv_penjualan_id")
    ->leftJoin("acc_m_akun", "acc_m_akun.id= acc_bayar_piutang_det.m_akun_id")
    ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
    ->where("acc_bayar_piutang_det.acc_bayar_piutang_id", "=", $params['id']);
    $db->orderBy("acc_bayar_piutang_det.id DESC");
    $models = $db->findAll();

    $listBayarPiutang = [];
    foreach ($models as $val) {
      $listBayarPiutang[$val->inv_penjualan_id]['id']               = $val->acc_bayar_piutang_id;
      $listBayarPiutang[$val->inv_penjualan_id]['inv_penjualan_id'] = $val->inv_penjualan_id;
      $listBayarPiutang[$val->inv_penjualan_id]['status']           = $val->status_bayar;
      $listBayarPiutang[$val->inv_penjualan_id]['tanggal']          = $val->tanggal;
      $listBayarPiutang[$val->inv_penjualan_id]['is_pelunasan']     = $val->is_pelunasan;
      $listBayarPiutang[$val->inv_penjualan_id]['nomor_fp']         = $val->nomor_fp;

      $listBayarPiutang[$val->inv_penjualan_id]['sisa'] = $val->piutang;
      // if($val->status_bayar != 'terposting'){
      // }else{
      //   $listBayarPiutang[$val->inv_penjualan_id]['sisa'] = $val->is_pelunasan == 1 ? 0: $val->sisa - ($val->bayar - $val->sisa_pelunasan);
      // }

      // Get list Bayar
      if( !empty($val->acc_m_akun_id) ){
        $val->m_akun_id = [
          'id'      => $val->acc_m_akun_id,
          'kode'    => $val->acc_m_akun_kode,
          'nama'    => $val->acc_m_akun_nama,
        ];
      }
      $listBayarPiutang[$val->inv_penjualan_id]['listBayar'][]      = [
        'id'              => $val->id,
        'm_akun_id'       => $val->m_akun_id,
        'tanggal'         => $val->tanggal_bayar,
        'bayar'           => $val->bayar,
        'sisa_pelunasan'  => $val->sisa_pelunasan,
        'is_pelunasan'    => $val->is_pelunasan,
      ];

      usort($listBayarPiutang[$val->inv_penjualan_id]['listBayar'], function($a, $b) {
          return $a['id'] <=> $b['id'];
      });
    }
    $listBayarPiutang = array_values($listBayarPiutang);

    return successResponse($response, ['list' => $listBayarPiutang, 'akun_piutang' => []]);
});

$app->get('/t_pembayaran_piutang/getJurnal', function ($request, $response) {
    $param = $request->getParams();
    $db = $this->db;

    if (isset($param['id']) && !empty($param['id'])) {
        $db->select("acc_trans_detail.*, acc_m_akun.nama as namaAkun, acc_m_akun.kode as kodeAkun")
                ->from("acc_trans_detail")
                ->leftJoin("acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
                ->orderBy("tanggal, acc_trans_detail.id ASC")
                ->where('reff_type', '=', 'acc_bayar_piutang')->where('reff_id', '=', $param['id']);
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

$app->get('/t_pembayaran_piutang/getListPiutang', function ($request, $response) {
    $params   = $request->getParams();
    $db       = $this->db;

    $db->select("
      inv_penjualan.id as inv_penjualan_id,
      FROM_UNIxtime(inv_penjualan.tanggal, '%Y-%m-%d') as tanggal,
      inv_penjualan.is_ppn,
      inv_penjualan.no_surat_jalan,
      inv_penjualan.acc_m_lokasi_id,
      inv_penjualan.status,
      inv_penjualan.piutang,
      inv_penjualan.piutang as sisa,
      inv_m_faktur_pajak.nomor as nomor_fp,
      inv_penjualan.inv_m_faktur_pajak_id
      ")
    ->from("inv_penjualan")
    ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
    ->where("inv_penjualan.acc_m_lokasi_id", "=", $params['lokasi_id'])
    ->andWhere("inv_penjualan.acc_m_kontak_id", "=", $params['customer_id'])
    ->andWhere("inv_penjualan.status", "!=", 'Lunas')
    ->andWhere("inv_penjualan.inv_proses_akhir_id", ">", 0);

    $model = $db->findAll();

    foreach ($model as $val) {
        $db->select("sum(acc_bayar_piutang_det.bayar - acc_bayar_piutang_det.sisa_pelunasan) as bayar")
        ->from("acc_bayar_piutang_det")
        ->leftJoin("inv_penjualan", "inv_penjualan.id = acc_bayar_piutang_det.inv_penjualan_id")
        ->where("acc_bayar_piutang_det.status", "=", "terposting")
        ->andWhere("acc_bayar_piutang_det.inv_penjualan_id", "=", $val->inv_penjualan_id)
        ->groupBy("acc_bayar_piutang_det.inv_penjualan_id");
        $bayar = $db->find();

        $val->sisa = $val->piutang - (isset($bayar->bayar) ? $bayar->bayar : 0);
        $val->listBayar = [];
    }

    return successResponse($response, [
      'list'          => $model,
      'akun_piutang'  => []
    ]);
});

$app->get('/t_pembayaran_piutang/index', function ($request, $response) {
    $params = $request->getParams();
    $sort = "acc_bayar_piutang.tanggal DESC";
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 20;

    $db = $this->db;
    $db->select("
            acc_bayar_piutang.*,
            acc_m_kontak.nama as namaCus,
            acc_m_kontak.kode as kodeCus,
            acc_m_user.nama as namaUser,
            kas.nama as namaKas,
            kas.kode as kodeKas,
            denda.nama as namaDenda,
            denda.kode as kodeDenda,
            acc_m_lokasi.nama as namaLokasi,
            acc_m_lokasi.kode as kodeLokasi
        ")
            ->from('acc_bayar_piutang')
            ->join('left join', 'acc_m_kontak', 'acc_m_kontak.id = acc_bayar_piutang.m_kontak_id')
            ->join('left join', 'acc_m_user', 'acc_m_user.id = acc_bayar_piutang.created_by')
            ->join('left join', 'acc_m_akun kas', 'kas.id = acc_bayar_piutang.m_akun_id')
            ->join('left join', 'acc_m_akun denda', 'denda.id = acc_bayar_piutang.m_akun_denda_id')
            ->join('left join', 'acc_m_lokasi', 'acc_m_lokasi.id = acc_bayar_piutang.m_lokasi_id')
            ->orderBy($sort);
//        ->where("acc_pemasukan.is_deleted", "=", 0);

    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);

        foreach ($filter as $key => $val) {
            if ($key == 'is_deleted') {
                $db->where("acc_bayar_piutang.is_deleted", '=', $val);
            } else {
                $db->where($key, 'LIKE', $val);
            }
        }
    }

    /** Set supplier */
    if (isset($params['m_customer_id']) && !empty($params['m_customer_id'])) {
        $db->where("acc_m_kontak.id", "=", $params['m_customer_id']);
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
        $val->customer          = ['id' => $val->m_kontak_id, 'nama' => $val->namaCus, 'kode' => $val->kodeCus];
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

$app->post('/t_pembayaran_piutang/save', function ($request, $response) {
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

    $kode = generateNoTransaksi("afu_pembayaran_piutang", 0);
    $insert['m_kontak_id']  = $data['form']['customer']['id'];
    $insert['m_lokasi_id']  = $data['form']['lokasi']['id'];
    // $insert['m_akun_id']    = $data['form']['akun']['id'];
    $insert['tanggal']      = date("Y-m-d h:i:s", strtotime($data['form']['tanggal']));
    $insert['total']        = $data['form']['total'];
    $insert['status']       = $data['form']['status'];
    $insert['keterangan']   = (isset($data['form']['keterangan']) && !empty($data['form']['keterangan']) ? $data['form']['keterangan'] : '');
    $insert['tgl_mulai']    = isset($data['form']['startDate']) ? $data['form']['startDate'] : null;
    $insert['tgl_selesai']  = isset($data['form']['endDate']) ? $data['form']['endDate'] : null;

    if (isset($data['form']['id']) && !empty($data['form']['id'])) {
        $insert['kode'] = $data['form']['kode'];
        $model = $sql->update("acc_bayar_piutang", $insert, ["id" => $data['form']['id']]);
    } else {
        $insert['kode'] = $kode;
        $model = $sql->insert("acc_bayar_piutang", $insert);
    }

    /*delete detail*/
    $deletedetail       = $sql->delete("acc_bayar_piutang_det", ["acc_bayar_piutang_id" => $model->id]);
    $deletetransdetail  = $sql->delete("acc_trans_detail", ["reff_id" => $model->id, "reff_type" => "acc_bayar_piutang"]);

    if (!empty($params['detail'])) {
        foreach ($params['detail'] as $val) {
          foreach ($val['listBayar'] as $key2 => $value2) {
            $detail['sisa_pelunasan'] = $value2['bayar'] - $val['sisa'];
            if( ($value2['bayar'] > 0 || $detail['sisa_pelunasan'] != 0) && !empty($value2['m_akun_id']['id']) ){
              $detail['kode']                 = $model->kode;
              $detail['acc_bayar_piutang_id'] = $model->id;
              $detail['inv_penjualan_id']     = $val['inv_penjualan_id'];
              $detail['tanggal']              = strtotime($value2['tanggal']);
              $detail['m_akun_id']            = $value2['m_akun_id']['id'];
              $detail['sisa']                 = $val['sisa'];
              $detail['bayar']                = $value2['bayar'];
              $detail['status']               = $model->status;
              $detail['created_at']           = $model->created_at;
              $detail['catatan']              = $val['nomor_fp'];

              if( $key2 == (sizeof($val['listBayar'])-1) ){
                $detail['is_pelunasan']         = !empty($val['check']) ? $val['check'] : 0;
                $detail['sisa_pelunasan']       = $value2['bayar'] - $val['sisa'];
                $detail['sisa_pelunasan']       = !empty($val['check']) && $val['check'] == 1 ? $detail['sisa_pelunasan'] : 0;
              } else {
                $detail['sisa_pelunasan']       = 0;
              }

              $sql->insert("acc_bayar_piutang_det", $detail);
              $val['sisa'] -= $value2['bayar'];
            }
          }

          if($model->status == "terposting") {
            if ( ($val['sisa'] - $val['bayar'] - $detail['sisa_pelunasan']) <= 0 ||  $detail['is_pelunasan'] == 1 ) {
              $sql->run("UPDATE inv_penjualan SET status = 'lunas' WHERE id = {$val['inv_penjualan_id']} ");
            }
          }
          // End of foreach 2
        }
        // End of foreach
    }

    if ($data['form']['status'] == "terposting") {
      $paramsJurnal = [
        'reff_type'   => 'acc_bayar_piutang',
        'reff_id'     => $model->id,
      ];

      simpanJurnal($paramsJurnal);
    }

    // if(!empty($data['jurnal'])){
    //   foreach ($data['jurnal'] as $key => $val) {
    //     $data['jurnal'][$key]['m_akun_id'] = $val['akun']['id'];
    //     $data['jurnal'][$key]['kode'] = $model->kode;
    //     $data['jurnal'][$key]['tanggal'] = date("Y-m-d", strtotime($data['form']['tanggal']));
    //     $data['jurnal'][$key]['reff_type'] = "acc_bayar_piutang";
    //     $data['jurnal'][$key]['reff_id'] = $model->id;
    //     $data['jurnal'][$key]['m_kontak_id'] = $model->m_kontak_id;
    //     $data['jurnal'][$key]['m_lokasi_id'] = $model->m_lokasi_id;
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

$app->post('/t_pembayaran_piutang/saveJurnal', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;
//    print_r($data);die();
    $model = $db->find("select * from acc_bayar_piutang where id = '" . $data['id'] . "'");
    if (isset($model->id)) {
        $db->delete("acc_trans_detail", ["reff_type" => "acc_bayar_piutang", "reff_id" => $model->id]);
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
                $transDetail[$key]['keterangan'] = 'Pembayaran Piutang (' . $model->kode . ')';
            }
            insertTransDetail($transDetail);
        }
    }

    return successResponse($response, []);
});

$app->post('/t_pembayaran_piutang/delete', function ($request, $response) {

    $data = $request->getParams();
    $db = $this->db;


    $model = $db->delete("acc_bayar_piutang", ['id' => $data['id']]);
    $model = $db->delete("acc_bayar_piutang_det", ['acc_bayar_piutang_id' => $data['id']]);
    $model = $db->delete("acc_trans_detail", ["reff_type" => "acc_bayar_piutang", "reff_id" => $data['id']]);
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

$app->get('/t_pembayaran_piutang/print', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $data = $db->select("acc_bayar_piutang.*, acc_m_lokasi.nama as namaLokasi, acc_m_kontak.nama as namaKontak, acc_m_akun.nama as namaAkun")
                    ->from("acc_bayar_piutang")
                    ->join("JOIN", "acc_m_lokasi", "acc_m_lokasi.id = acc_bayar_piutang.m_lokasi_id")
                    ->join("JOIN", "acc_m_kontak", "acc_m_kontak.id = acc_bayar_piutang.m_kontak_id")
                    ->join("JOIN", "acc_m_akun", "acc_m_akun.id = acc_bayar_piutang.m_akun_id")
                    ->where("acc_bayar_piutang.id", "=", $params['id'])->find();
    $arr = $db->select("acc_bayar_piutang_det.*, inv_penjualan.kode as kodePiutang")
            ->from("acc_bayar_piutang_det")
            ->join("JOIN", "inv_penjualan", "inv_penjualan.id = acc_bayar_piutang_det.inv_penjualan_id")
            ->where("acc_bayar_piutang_id", "=", $data->id)
            ->where("bayar", ">", 0)
            ->findAll();


    $data->sisa_bayar = 0;
    $data->invoice = [];
    foreach ($arr as $key => $val) {
        $val->sisa_bayar = $val->sisa - $val->bayar;
        $data->sisa_bayar += intval($val->sisa_bayar);
        $data->invoice[] = $val->kodePiutang;
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

$app->post('/t_pembayaran_piutang/unpost', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    // Update status pembayaran piutang
    $model = $db->update("acc_bayar_piutang_det", ['status' => 'draft'], ['acc_bayar_piutang_id' => $data['id']]);
    $model = $db->update("acc_bayar_piutang", ['status' => 'draft'], ['id' => $data['id']]);

    // Update status penjualan
    $getPenjualan = $db->select("inv_penjualan_id")
      ->from("acc_bayar_piutang_det")
      ->where("acc_bayar_piutang_id", "=", $data['id'])
      ->groupBy("inv_penjualan_id")
      ->findAll();

    if( !empty($getPenjualan) ){
      foreach ($getPenjualan as $key => $value) {
        $db->update("inv_penjualan", ["status" => 'belum lunas'], ['id' => $value->inv_penjualan_id]);
      }
    }

    // Hapus jurnal
    $paramsHJU = [
      'reff_type'   => 'acc_bayar_piutang',
      'reff_id'     => $data['id'],
    ];
    hapusJurnalUmum($paramsHJU);

    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

// Versi 2
// Get list FP yg belum Lunas
$app->get('/t_pembayaran_piutang/getListFP', function ($request, $response) {
    $params   = $request->getParams();
    $db       = $this->db;

    $db->select("
      inv_penjualan.id as inv_penjualan_id,
      FROM_UNIxtime(inv_penjualan.tanggal, '%Y-%m-%d') as tanggal,
      inv_penjualan.is_ppn,
      inv_penjualan.no_surat_jalan,
      inv_penjualan.acc_m_lokasi_id,
      inv_penjualan.status,
      inv_penjualan.piutang,
      inv_penjualan.piutang as sisa,
      inv_m_faktur_pajak.nomor as nomor_fp,
      inv_penjualan.inv_m_faktur_pajak_id,
      sum(acc_bayar_piutang_det.bayar - acc_bayar_piutang_det.sisa_pelunasan) as bayar
      ")
    ->from("inv_penjualan")
    ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
    ->join("LEFT JOIN", "acc_bayar_piutang_det", "inv_penjualan.id = acc_bayar_piutang_det.inv_penjualan_id AND acc_bayar_piutang_det.status != 'draft'")
    ->where("inv_penjualan.acc_m_lokasi_id", "=", $params['lokasi_id'])
    ->andWhere("inv_penjualan.acc_m_kontak_id", "=", $params['customer_id'])
    ->andWhere("inv_penjualan.status", "!=", 'Lunas')
    ->andWhere("inv_penjualan.inv_proses_akhir_id", ">", 0);

    $db->groupBy("inv_penjualan.id");
    $model = $db->findAll();

    foreach ($model as $val) {
        $val->sisa = $val->piutang - (isset($bayar->bayar) ? $bayar->bayar : 0);
        $val->listBayar = [];
    }

    return successResponse($response, [
      'list'          => $model,
      'akun_piutang'  => []
    ]);
});

$app->post('/t_pembayaran_piutang/save_v2', function ($request, $response) {
    $params   = $request->getParams();
    $data     = $params;
    $sql      = $this->db;
    $validasi = validasi($data['form']);

    if ($validasi !== true) {
      return unprocessResponse($response, $validasi);
    }

    $kode = generateNoTransaksi("afu_pembayaran_piutang", 0);
    $insert['m_kontak_id']  = $data['form']['customer']['id'];
    $insert['m_lokasi_id']  = $data['form']['lokasi']['id'];
    $insert['tanggal']      = date("Y-m-d h:i:s", strtotime($data['form']['tanggal']));
    $insert['total']        = $data['form']['total_bayar'];
    $insert['status']       = $data['form']['status'];
    $insert['keterangan']   = (isset($data['form']['keterangan']) && !empty($data['form']['keterangan']) ? $data['form']['keterangan'] : '');

    if (isset($data['form']['id']) && !empty($data['form']['id'])) {
        $insert['kode'] = $data['form']['kode'];
        $model = $sql->update("acc_bayar_piutang", $insert, ["id" => $data['form']['id']]);
    } else {
        $insert['kode'] = $kode;
        $model = $sql->insert("acc_bayar_piutang", $insert);
    }

    /*delete detail*/
    $deletedetail       = $sql->delete("acc_bayar_piutang_det", ["acc_bayar_piutang_id" => $model->id]);
    $deleteRekening     = $sql->delete("acc_bayar_piutang_rek", ["acc_bayar_piutang_id" => $model->id]);
    $deletetransdetail  = $sql->delete("acc_trans_detail", ["reff_id" => $model->id, "reff_type" => "acc_bayar_piutang"]);

    if (!empty($params['listRekeningKoran'])) {
        foreach ($params['listRekeningKoran'] as $key => $val) {
          // simpan rekening koran
          $data_rek['no_transaksi']           = $val['no_transaksi'];
          $data_rek['acc_bayar_piutang_id']   = $model->id;
          $data_rek['m_akun_id']              = $val['m_akun_id']['id'];
          $data_rek['tanggal']                = date("Y-m-d", strtotime($val['tanggal']));
          $data_rek['nominal']                = $val['bayar'];
          $data_rek['sisa']                   = $val['bayar'] - $val['total_bayar'];
          $data_rek['keterangan']             = $val['keterangan'];

          $insert_rek = $sql->insert("acc_bayar_piutang_rek", $data_rek);

          // Simpan list pembayaran FP
          foreach ($val['listFP'] as $key2 => $value2) {
            $data_fp['kode']                      = $model->kode;
            $data_fp['acc_bayar_piutang_id']      = $model->id;
            $data_fp['acc_bayar_piutang_rek_id']  = $insert_rek->id;
            $data_fp['inv_penjualan_id']          = $value2['faktur']['inv_penjualan_id'];
            $data_fp['m_akun_id']                 = $val['m_akun_id']['id'];
            $data_fp['sisa']                      = $value2['sisa_pembayaran'];
            $data_fp['bayar']                     = $value2['bayar'];
            $data_fp['status']                    = $model->status;
            $data_fp['tanggal']                   = strtotime($val['tanggal']);
            $data_fp['is_pelunasan']              = !empty($value2['is_pelunasan']) ? $value2['is_pelunasan'] : 0;
            $data_fp['sisa_pelunasan']            = $value2['sisa_pembayaran'];

            $insert_piutang_det = $sql->insert("acc_bayar_piutang_det", $data_fp);

            if($model->status == "terposting") {
              if( $value2['sisa_pelunasan'] == 0 || ( !empty($value2['is_pelunasan']) && $value2['is_pelunasan'] == 1) ) {
                $sql->run("UPDATE inv_penjualan SET status = 'lunas' WHERE id = {$value2['faktur']['inv_penjualan_id']} ");
              }
            }
          }

        }
    }

    // Simpan jurnal
    // if ($data['form']['status'] == "terposting") {
    //   $paramsJurnal = [
    //     'reff_type'   => 'acc_bayar_piutang',
    //     'reff_id'     => $model->id,
    //   ];
    //   simpanJurnal($paramsJurnal);
    // }

    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Data Gagal Di Simpan']);
    }
});


$app->get('/t_pembayaran_piutang/view_v2', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    // Get list rekening koran
    $db->select("
      acc_bayar_piutang_rek.*,
      acc_bayar_piutang_rek.tanggal as tanggal_bayar,
      acc_m_akun.id acc_m_akun_id,
      acc_m_akun.kode acc_m_akun_kode,
      acc_m_akun.nama acc_m_akun_nama
    ")
    ->from("acc_bayar_piutang_rek")
    ->leftJoin("acc_m_akun", "acc_m_akun.id= acc_bayar_piutang_rek.m_akun_id")
    ->where("acc_bayar_piutang_rek.acc_bayar_piutang_id", "=", $params['id']);
    $db->orderBy("acc_bayar_piutang_rek.id ASC");
    $getRekoran = $db->findAll();

    $db->select("
      acc_bayar_piutang_det.*,
      acc_bayar_piutang_det.status as status_bayar,
      FROM_UNIxtime(acc_bayar_piutang_det.tanggal, '%Y-%m-%d') as tanggal_bayar,
      FROM_UNIxtime(inv_penjualan.tanggal, '%Y-%m-%d') as tanggal,
      inv_penjualan.is_ppn,
      inv_penjualan.no_surat_jalan,
      inv_penjualan.acc_m_lokasi_id,
      inv_penjualan.acc_m_kontak_id,
      inv_penjualan.status,
      inv_penjualan.piutang,
      inv_m_faktur_pajak.nomor as nomor_fp,
      inv_penjualan.inv_m_faktur_pajak_id,
      inv_penjualan.piutang - sum(COALESCE(bayar_piutang.bayar, 0)) as piutang
    ")
    ->from("acc_bayar_piutang_det")
    ->leftJoin("inv_penjualan", "inv_penjualan.id= acc_bayar_piutang_det.inv_penjualan_id")
    ->join("LEFT JOIN", "acc_bayar_piutang_det as bayar_piutang", "inv_penjualan.id = bayar_piutang.inv_penjualan_id AND bayar_piutang.status != 'draft'")
    ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
    ->where("acc_bayar_piutang_det.acc_bayar_piutang_id", "=", $params['id']);

    $db->groupBy("inv_penjualan.id");
    $db->orderBy("acc_bayar_piutang_det.id DESC");
    $models = $db->findAll();

    $listFP = [];
    foreach ($models as $key => $value) {
      $listFP[$value->acc_bayar_piutang_rek_id][] = $value;
    }

    foreach ($getRekoran as $key => $value) {
      $getRekoran[$key]->bayar = $value->nominal;
      $getRekoran[$key]->m_akun_id = [
        'id'        => $value->acc_m_akun_id,
        'kode'      => $value->acc_m_akun_kode,
        'nama'      => $value->acc_m_akun_nama,
      ];

      foreach ($listFP[$value->id] as $key2 => $value2) {
        $data_val['id']               = $value2->id;
        $data_val['inv_penjualan_id'] = $value2->inv_penjualan_id;
        $data_val['status']           = $value2->status;
        $data_val['tanggal']          = $value2->tanggal_bayar;
        $data_val['is_pelunasan']     = $value2->is_pelunasan;
        $data_val['sisa']             = $value2->piutang;
        $data_val['bayar']            = $value2->bayar;
        $data_val['faktur']           = [
          'inv_penjualan_id'      => $value2->inv_penjualan_id,
          'inv_m_faktur_pajak_id' => $value2->inv_m_faktur_pajak_id,
          'nomor_fp'              => $value2->nomor_fp,
          'sisa'                  => $value2->piutang,
          'piutang'               => $value2->piutang,
          'tanggal'               => $value2->tanggal,
        ];

        $getRekoran[$key]->listFP[]   = $data_val;
      }
    }

    return successResponse($response, ['list' => $getRekoran, 'akun_piutang' => []]);
});


// Versi 2 - end
