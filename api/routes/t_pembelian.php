<?php

$app->get('/t_pembelian/getLastData', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

//    $data = $db->find("SELECT inv_penjualan.*, acc_m_lokasi.nama as namaLokasi, acc_m_lokasi.kode as kodeLokasi FROM inv_penjualan JOIN acc_m_lokasi ON acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id ORDER BY id DESC");

    $data = isset($_SESSION['user']['temp2']) ? $_SESSION['user']['temp2'] : null;

    return successResponse($response, $data);
});

$app->get('/t_pembelian/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    date_default_timezone_set("Asia/Jakarta");

    if (isset($params['bulan']) && !empty($params['bulan'])) {
        $bulan_awal = date("Y-m-d", strtotime($params['bulan']));
        $bulan_akhir = date("Y-m-t", strtotime($params['bulan']));
    }

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 20;

    $db->select("
      inv_pembelian.*,
      m_user.nama as pembuat,
      inv_po_pembelian.id as id_po,
      inv_po_pembelian.kode as kode_po,
      inv_m_faktur_pajak.id as id_faktur,
      inv_m_faktur_pajak.nomor as nomor_faktur,
      acc_m_akun.kode as kodeAkun,
      acc_m_akun.nama as namaAkun
      ")
      ->from("inv_pembelian")
      ->JOIN("JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_pembelian.acc_m_kontak_id")
      ->join("LEFT JOIN", "m_user", "m_user.id = inv_pembelian.created_by")
      ->join("LEFT JOIN", "inv_po_pembelian", "inv_po_pembelian.id = inv_pembelian.inv_po_pembelian_id")
      ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_pembelian.inv_m_faktur_pajak_id = inv_m_faktur_pajak.id")
      ->join("LEFT JOIN", "acc_m_akun", "acc_m_akun.id= inv_pembelian.acc_m_akun_id");

    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'is_deleted') {
                $db->where("is_deleted", '=', $val);
            } else if ($key == 'status_fp') {
                $db->where('inv_pembelian.status', '!=', $val);
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

    // View berdasarkan bulan / belum proses akhir
    if( (empty($filter['is_import']) || $filter['is_import'] == 0) && empty($filter['jenis_pembelian']) ){
      $db->andWhere("inv_pembelian.jenis_pembelian", "!=", "hutang");
      if( empty($params['bulan']) ){
        $db->customWhere("inv_pembelian.inv_proses_akhir_id IS NULL", "AND");
      } else {
        $bulan_awal   = date("Y-m-01", strtotime($params['bulan']));
        $bulan_akhir  = date("Y-m-t", strtotime($params['bulan']));
        $db->customWhere("FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') >= '" . $bulan_awal . "'", "AND");
        $db->customWhere("FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') <= '" . $bulan_akhir . "'", "AND");
      }
    }
    // View berdasarkan bulan / belum proses akhir - END

    $models = $db->orderBy("acc_m_kontak.nama, inv_pembelian.tanggal, inv_m_faktur_pajak.nomor")->findAll();
    $totalItem = $db->count();

    $allLokasi = getAllData($db, 'acc_m_lokasi', 'id');

    $getBayarHutang = getBayarHutang($models);
    // pd($getBayarHutang);
    foreach ($models as $key => $value) {
        $models[$key]->tanggal          = date("Y-m-d", $value->tanggal);
        $models[$key]->tanggal_formated = date("d M Y", strtotime($value->tanggal));
        $models[$key]->grand_total      = $value->total;
        $models[$key]->acc_m_lokasi_id  = isset($allLokasi[$value->acc_m_lokasi_id]) ? $allLokasi[$value->acc_m_lokasi_id] : [];
        $models[$key]->acc_m_akun_id    = isset($value->acc_m_akun_id) ? ['id' => $value->acc_m_akun_id, 'kode' => $value->kodeAkun, 'nama' => $value->namaAkun] : [];

        $models[$key]->terbayar         = !empty($getBayarHutang[$value->id]) ? $getBayarHutang[$value->id] : 0;
        $models[$key]->hutang           -= $models[$key]->terbayar;

        if (isset($value->id_po)) {
            $models[$key]->inv_po_pembelian_id = [
                "id"    => $value->id_po,
                "kode"  => $value->kode_po
            ];
        }

        if (isset($value->id_faktur)) {
            $models[$key]->inv_m_faktur_pajak_id = [
                "id"    => $value->id_faktur,
                "nomor" => $value->nomor_faktur
            ];
        }

        if (isset($value->acc_m_kontak_id)) {
            $a = $db->find("SELECT * FROM acc_m_kontak WHERE id = {$value->acc_m_kontak_id}");
            $a->acc_m_akun_id = !empty($a->acc_m_akun_id) ? $db->find("SELECT * FROM acc_m_akun WHERE id = {$a->acc_m_akun_id}") : [];
            $supp = $a;
        } else {
            $supp = [];
        }
        $models[$key]->acc_m_kontak_id = !empty($supp) ? $supp : [];
        $models[$key]->stok = 0;

        $pib = explode("-", $value->pib);
        foreach ($pib as $keys => $values) {
            $var = 'form' . ($keys + 1);
            $models[$key]->$var = (string) $values;
        }
    }

    // Hitung total DPP Pembelian
    $db->select("SUM(total) as total_dpp")
      ->from("inv_pembelian");

    if (isset($params['filter'])) {
      $filter = (array) json_decode($params['filter']);

      if( !empty($filter['inv_pembelian.is_ppn']) ){
        $db->andWhere("inv_pembelian.is_ppn", "=", $filter['inv_pembelian.is_ppn']);
      }

      if( !empty($filter['is_import']) ){
        $db->andWhere("inv_pembelian.is_import", "=", $filter['is_import']);
      } else {
        $db->andWhere("inv_pembelian.is_import", "=", 0);
      }

    }

    if( !empty($params['bulan']) ){
      $db->customWhere("FROM_UNIXTIME(tanggal, '%Y-%m-%d') >= '" . $bulan_awal . "'", "AND")
      ->customWhere("FROM_UNIXTIME(tanggal, '%Y-%m-%d') <= '" . $bulan_akhir . "'", "AND");
    } else {
      $db->customWhere("inv_proses_akhir_id IS NULL", "AND");
    }

    $totalDpp = $db->find();
    // Hitung total DPP Pembelian - ENd

    return successResponse($response, [
        'list' => $models,
        'totalDpp' => !empty($totalDpp->total_dpp) ? $totalDpp->total_dpp : 0,
        'totalItems' => $totalItem,
    ]);
});

function getBayarHutang($params=[]){
  $db = config('DB');
  $db = new Cahkampung\Landadb($db['db']);

  if(empty($params)){
    return [];
  }

  $listPembelianID=[];
  foreach ($params as $key => $value) {
    $listPembelianID[]=$value->id;
  }
  $db->select("
    acc_bayar_hutang_det.inv_pembelian_id,
    sum(acc_bayar_hutang_det.bayar-acc_bayar_hutang_det.sisa_pelunasan) as bayar
  ")
  ->from("acc_bayar_hutang_det")
  ->leftJoin("inv_pembelian", "inv_pembelian.id = acc_bayar_hutang_det.inv_pembelian_id")
  ->where("acc_bayar_hutang_det.status", "=", "terposting")
  ->customWhere("acc_bayar_hutang_det.inv_pembelian_id IN(".implode(",",$listPembelianID).")", "AND")
  ->groupBy("acc_bayar_hutang_det.inv_pembelian_id");
  $getPembayaran = $db->findAll();

  $listPembayaran = [];
  if( !empty($getPembayaran) ){
    foreach ($getPembayaran as $key => $value) {
      $listPembayaran[$value->inv_pembelian_id] = $value->bayar;
    }
    // pd([$listPembayaran, $getPembayaran,$listPembelianID]);
  }

  return $listPembayaran;
}

$app->post('/t_pembelian/trash', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $model = $db->update("inv_m_barang", ['is_deleted' => $data['is_deleted']], array('id' => $data['id']));
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

function generateKode($db) {
    $cekKode = $db->select('*')
            ->from("inv_pembelian")
            ->where("kode", "!=", "")
            ->orderBy('kode DESC')
            ->find();

    if ($cekKode) {
        $kode_terakhir = $cekKode->kode;
    } else {
        $kode_terakhir = 0;
    }

    $kodeTerakhir = (substr($kode_terakhir, -5) + 1);
    $kode = substr('00000' . $kodeTerakhir, strlen($kodeTerakhir));
    $kode = 'B' . date("y") . "/" . $kode;

    return $kode;
}

$app->get('/t_pembelian/getKode', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    return successResponse($response, ['kode' => generateKode($db)]);
});

function validasi($data, $custom = array()) {
    $validasi = array(
        'acc_m_lokasi_id' => 'required',
        'acc_m_kontak_id' => 'required',
        'tanggal'         => 'required',
        'jatuh_tempo'     => 'required',
            // 'no_invoice' => 'required',
        // 'acc_m_akun_id'   => 'required',
    );

    GUMP::set_field_name("acc_m_kontak_id", "Supplier");
    GUMP::set_field_name("acc_m_lokasi", "Lokasi");
    GUMP::set_field_name("jatuh_tempo", "Tanggal Jatuh Tempo");
    GUMP::set_field_name("acc_m_akun_id", "Akun Masuk");
    GUMP::set_field_name("inv_m_faktur_pajak_id", "Faktur Pajak");

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->post('/t_pembelian/save', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $validasi = !empty($params['form']['is_ppn']) ? validasi($params['form'], ['inv_m_faktur_pajak_id' => 'required']) : validasi($params['form']);
    $validasi = !empty($params['form']['is_import']) ? validasi($params['form']) : validasi($params['form']);

    if ($validasi !== true) {
        return unprocessResponse($response, $validasi);
    }
    try {

        $kode = generateNoTransaksi("pembelian", $params['form']['acc_m_lokasi_id']['kode']);

        $params['form']['no_urut'] = (empty($kode)) ? 1 : ((int) substr($kode, -5));

        $params['form']['acc_m_lokasi_id']        = !empty($params['form']['acc_m_lokasi_id']['id']) ? $params['form']['acc_m_lokasi_id']['id'] : NULL;
        $params['form']['acc_m_kontak_id']        = !empty($params['form']['acc_m_kontak_id']['id']) ? $params['form']['acc_m_kontak_id']['id'] : NULL;
        $params['form']['inv_po_pembelian_id']    = !empty($params['form']['inv_po_pembelian_id']) ? $params['form']['inv_po_pembelian_id'] : NULL;
        $params['form']['acc_m_akun_biaya_id']    = !empty($params['form']['m_akun_biaya_id']) ? $params['form']['m_akun_biaya_id'] : null;
        $params['form']['acc_m_akun_potongan_id'] = !empty($params['form']['m_akun_potongan_id']) ? $params['form']['m_akun_potongan_id'] : null;
        $params['form']['acc_m_akun_id']          = !empty($params['form']['acc_m_akun_id']) ? $params['form']['acc_m_akun_id']['id'] : null;
        $params['form']['potongan']               = isset($params['form']['potongan']) ? $params['form']['potongan'] : 0;
        $params['form']['diskon']                 = isset($params['form']['diskon']) ? $params['form']['diskon'] : 0;
        $params['form']['biaya_lain']             = isset($params['form']['biaya_lain']) ? $params['form']['biaya_lain'] : 0;
        $params['form']['is_import']              = isset($params['form']['is_import']) ? $params['form']['is_import'] : 0;
        $params['form']['inv_m_faktur_pajak_id']  = !empty($params['form']['inv_m_faktur_pajak_id']['id']) ? $params['form']['inv_m_faktur_pajak_id']['id'] : NULL;
        $params['form']['hutang']                 = $params['form']['hutang'];

        if ($params['form']['biaya_lain'] > 0 && $params['form']['potongan'] > 0) {
            $validasi = validasi($params['form'], ['m_akun_biaya_id' => 'required', 'm_akun_potongan_id' => 'required']);
        } else if ($params['form']['biaya_lain'] > 0 && $params['form']['potongan'] == 0) {
            $validasi = validasi($params['form'], ['m_akun_biaya_id' => 'required']);
        } else if ($params['form']['biaya_lain'] == 0 && $params['form']['potongan'] > 0) {
            $validasi = validasi($params['form'], ['m_akun_potongan_id' => 'required']);
        } else {
            $validasi = validasi($params['form']);
        }

        // Jika saldo hutang
        if ($params['form']['jenis_pembelian'] == 'hutang') {
            $validasi = validasi($params['form'], ['acc_m_akun_id' => 'required']);
        }
        // Jika saldo hutang - end

        if ($validasi !== true) {
            return unprocessResponse($response, $validasi);
        }

        if ($params['detail'] == 0) {
            $params['form']['tipe'] = 'Hutang';
        } else {
            $params['form']['tipe'] = 'Pembelian';
        }

        if ($params['form']['status'] == 'draft') {
            $params['form']['status'] = 'draft';
        } else if ($params['form']['hutang'] > 0) {
            $params['form']['status'] = 'belum lunas';
        } else {
            $params['form']['status'] = 'lunas';
        }

        if ($params['form']['is_import'] == 1) {
//            $params['form']['bea_masuk'] = isset($params['form']['bea_masuk']) ? $params['form']['bea_masuk'] : 0;
//            $params['form']['ppn'] = isset($params['form']['ppn']) ? $params['form']['ppn'] : 0;
//            $params['form']['pph22'] = isset($params['form']['pph22']) ? $params['form']['pph22'] : 0;
//            $params['form']['denda_pabean'] = isset($params['form']['denda_pabean']) ? $params['form']['denda_pabean'] : 0;
            $params['form']['pelabuhan_ppn'] = isset($params['form']['pelabuhan_ppn']) ? $params['form']['pelabuhan_ppn'] : 0;
            $params['form']['pelabuhan_non_ppn'] = isset($params['form']['pelabuhan_non_ppn']) ? $params['form']['pelabuhan_non_ppn'] : 0;
//            $params['form']['ntpn'] = isset($params['form']['ntpn']) ? $params['form']['ntpn'] : null;
//            $params['form']['tanggal_ntpn'] = isset($params['form']['tanggal_ntpn']) ? date("Y-m-d", strtotime($params['form']['tanggal_ntpn'])) : null;
        } else {
//            unset($params['form']['bea_masuk']);
//            unset($params['form']['ppn']);
//            unset($params['form']['pph22']);
//            unset($params['form']['denda_pabean']);
            unset($params['form']['pelabuhan_ppn']);
            unset($params['form']['pelabuhan_non_ppn']);
        }

        $tanggal = $params['form']['tanggal'];
        $params['form']['tanggal'] = strtotime($params['form']['tanggal']);
        $params['form']['jatuh_tempo'] = strtotime($params['form']['jatuh_tempo']);
        $params['form']['total'] = !empty($params['form']['grand_total']) ? $params['form']['grand_total'] : 0;

        if (isset($params['form']["id"])) {
            $model = $db->update("inv_pembelian", $params['form'], array('id' => $params['form']['id']));
            $deleteLama = $db->delete('inv_pembelian_det', ['inv_pembelian_id' => $model->id]);
            $deleteFaktur = $db->delete('inv_pembelian_det_faktur', ['inv_pembelian_id' => $model->id]);
            $deleteBiaya = $db->delete('inv_pembelian_det_biaya', ['inv_pembelian_id' => $model->id]);
        } else {
            $params['form']['kode'] = $kode;
            $model = $db->insert("inv_pembelian", $params['form']);

//            $_SESSION['user']['temp2']['depo'] = $lokasi;
            $_SESSION['user']['temp2']['tanggal'] = $tanggal;
        }

        //Hutang
        if ($params['form']['hutang'] > 0) {

        }
        //Hutang - End
        // Detail Pembelian
        if (!empty($params['detail'])) {

            foreach ($params['detail'] as $val) {
                if (!empty($val['inv_m_barang_id']['id'])) {
                    /** SIMPAN DETAIL PEMBELIAN */
                    $val['inv_pembelian_id'] = $model->id;
                    $val['is_pakai'] = $val['inv_m_barang_id']['is_pakai'];
                    $val['inv_m_barang_nama_id'] = isset($val['inv_m_barang_id']['inv_m_barang_nama_id']) && !empty($val['inv_m_barang_id']['inv_m_barang_nama_id']) ? $val['inv_m_barang_id']['inv_m_barang_nama_id'] : null;
                    $val['inv_m_barang_id'] = $val['inv_m_barang_id']['id'];
                    $detail = $db->insert("inv_pembelian_det", $val);

                    /* PENGISIAN KARTU STOK */
                    if ($params['form']['status'] != 'draft' && $val['is_pakai'] == 0) {
                        $kartu = [
                            "kode" => $model->kode,
                            "inv_m_barang_id" => $val['inv_m_barang_id'],
                            "catatan" => 'pembelian',
                            "jumlah_masuk" => $val['jumlah'],
                            "harga_masuk" => $val['harga'] - ($val['diskon'] / $val['jumlah']),
                            "trans_tipe" => 'inv_pembelian_id',
                            "trans_id" => $model->id,
                            "jenis_kas" => 'masuk',
                            "stok" => $val['jumlah'],
                            "acc_m_lokasi_id" => $params['form']['acc_m_lokasi_id'],
                            "tanggal" => $params['form']['tanggal'],
                        ];
                        $insertKartuStok = $db->insert("inv_kartu_stok", $kartu);
                    }
                }
            }
        }
        // Detail Pembelian - End
        // Detail Faktur Pajak Bea Pelabuhan
        if (!empty($params['detailFP']) && $model->is_import == 1) {
            foreach ($params['detailFP'] as $key => $value) {
                if (isset($value['faktur']) && !empty($value['faktur'])) {
                    $dataFP = [
                        'inv_pembelian_id'      => $model->id,
                        'inv_m_faktur_pajak_id' => $value['faktur']['id'],
                        'type'                  => 'ppn'
                    ];

                    $insertFP = $db->insert("inv_pembelian_det_faktur", $dataFP);
                }
            }
        }
        if (!empty($params['detailFP2']) && $model->is_import == 1) {
            foreach ($params['detailFP2'] as $key => $value) {
                if (isset($value['acc_m_kontak_id']) && !empty($value['acc_m_kontak_id'])) {
                    $dataFP = [
                        'inv_pembelian_id'  => $model->id,
                        'no_invoice'        => $value['no_invoice'],
                        'tanggal'           => date("Y-m-d", strtotime($value['tanggal'])),
                        'acc_m_kontak_id'   => $value['acc_m_kontak_id']['id'],
                        'total'             => $value['total'],
                        'type'              => 'non ppn'
                    ];

                    $insertFP = $db->insert("inv_pembelian_det_faktur", $dataFP);
                }
            }
        }

        if (!empty($params['detailBiaya']) && $model->is_import == 1) {
            foreach ($params['detailBiaya'] as $key => $value) {
                $dataBiaya = [
                    'inv_pembelian_id'  => $model->id,
                    'bea_masuk'         => isset($value['bea_masuk']) && !empty($value['bea_masuk']) ? $value['bea_masuk'] : 0,
                    'ppn'               => isset($value['ppn']) && !empty($value['ppn']) ? $value['ppn'] : 0,
                    'pph22'             => isset($value['pph22']) && !empty($value['pph22']) ? $value['pph22'] : 0,
                    'denda_pabean'      => isset($value['denda_pabean']) && !empty($value['denda_pabean']) ? $value['denda_pabean'] : 0,
                    'ntpn'              => isset($value['ntpn']) && !empty($value['ntpn']) ? $value['ntpn'] : null,
                    'tanggal_ntpn'      => isset($value['tanggal_ntpn']) && !empty($value['tanggal_ntpn']) ? date("Y-m-d", strtotime($value['tanggal_ntpn'])) : null,
                ];

                $insertBiaya = $db->insert("inv_pembelian_det_biaya", $dataBiaya);
            }
        }
        // Detail Faktur Pajak Bea Pelabuhan - End
        // Jurnal Akuntansi
        // $transDetail = [];
        // if (!empty($params['listJurnal']) && $params['form']['status'] != 'draft') {
        //     $db->delete('acc_trans_detail', ['reff_id' => $model->id, 'reff_type' => 'inv_pembelian']);
        //
        //     foreach ($params['listJurnal'] as $key => $value) {
        //         $transDetail[$key]['m_lokasi_id'] = $model->acc_m_lokasi_id;
        //         $transDetail[$key]['m_akun_id'] = $value['akun']['id'];
        //         $transDetail[$key]['m_kontak_id'] = $model->acc_m_kontak_id;
        //         $transDetail[$key]['debit'] = $value['debit'];
        //         $transDetail[$key]['kredit'] = $value['kredit'];
        //         $transDetail[$key]['kode'] = $model->kode;
        //         $transDetail[$key]['keterangan'] = $value['keterangan'];
        //         $transDetail[$key]['reff_id'] = $model->id;
        //         $transDetail[$key]['tanggal'] = date("Y-m-d", $model->tanggal);
        //         $transDetail[$key]['reff_type'] = "inv_pembelian";
        //         $transDetail[$key]['m_lokasi_jurnal_id'] = $model->acc_m_lokasi_id;
        //     }
        //     insertTransDetail($transDetail);
        // }
        // Jurnal Akuntansi - End
        if($model->status != 'draft'){
          $paramsJurnal = [
            'reff_type'   => 'inv_pembelian',
            'reff_id'     => $model->id,
          ];

          simpanJurnal($paramsJurnal);
        }


        return successResponse($response, $model);
    } catch (Exception $e) {
        return unprocessResponse($response, ['Terjadi kesalahan pada server! ' . $e->getMessage()]);
    }
});

$app->get('/t_pembelian/getPoPembelian', function ($request, $response) {
    $db = $this->db;

    $model = $db->select("inv_po_pembelian.*")
            ->from("inv_po_pembelian")
            ->join("left join", "inv_pembelian", "inv_pembelian.inv_po_pembelian_id = inv_po_pembelian.id")
            ->where("inv_po_pembelian.status", "=", "approved")
            ->customWhere("inv_pembelian.id is null", "AND")
            ->findAll();

    return successResponse($response, $model);
});

$app->get('/t_pembelian/getPengajuanPembelian', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select("inv_po_pembelian.id as inv_po_pembelian_id,
        inv_po_pembelian.kode,
        inv_po_pembelian.acc_m_kontak_id,
        inv_po_pembelian.acc_m_lokasi_id,
        inv_po_pembelian.tanggal,
        inv_po_pembelian.catatan,
        inv_po_pembelian.total,
        inv_po_pembelian.status,
        m_user.nama as pembuat")
            ->from("inv_po_pembelian")
            ->join("left join", "m_user", "m_user.id = inv_po_pembelian.created_by")
            ->where("inv_po_pembelian.id", "=", $params['id']);

    $model = $db->find();

    $allLokasi = getAllData($db, 'acc_m_lokasi', 'id');

    $model->grand_total = $model->total;
    $model->acc_m_lokasi_id = isset($allLokasi[$model->acc_m_lokasi_id]) ? $allLokasi[$model->acc_m_lokasi_id] : [];

    if (isset($model->acc_m_lokasi_id)) {
        $supp = $db->select("*")
                ->from("acc_m_kontak")
                ->where("id", "=", $model->acc_m_kontak_id)
                ->find();
    } else {
        $supp = [];
    }

    if (isset($model->inv_po_pembelian_id)) {
        $po_pembelian = $db->select("*")
                ->from("inv_po_pembelian")
                ->where("id", "=", $model->inv_po_pembelian_id)
                ->find();
    } else {
        $po_pembelian = [];
    }

    $model->acc_m_kontak_id = !empty($supp) ? $supp : [];
    $model->inv_po_pembelian_id = !empty($po_pembelian) ? $po_pembelian : [];
    $model->stok = 0;

    return successResponse($response, $model);
});

$app->get('/t_pembelian/getPengajuanDetail', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $detail = $db->select("
    inv_po_pembelian_det.*,
    inv_m_barang.id as inv_m_barang_id,
    inv_m_barang.nama,
    inv_m_barang.kode,
    inv_m_satuan.nama as nama_satuan")
            ->from("inv_po_pembelian_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_po_pembelian_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->where("inv_po_pembelian_det.inv_po_pembelian_id", "=", $params['id'])
            ->findAll();


    foreach ($detail as $key => $val) {
        unset($val->id);
        $stok = getStok($db, $val->inv_m_barang_id, $params['acc_m_lokasi_id']);
        $val->inv_m_barang_id = [
            'id' => $val->inv_m_barang_id,
            'kode' => $val->kode,
            'nama' => $val->nama,
            'nama_satuan' => $val->nama_satuan,
            'stok' => $stok,
        ];

        $subharga = ($val->jumlah * $val->harga);
        $val->subtotal = $subharga;
        $val->stok = $stok;
    }

    return successResponse($response, $detail);
});

$app->get('/t_pembelian/getDetail', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $detail = $db->select("
      inv_pembelian_det.*,
      inv_m_barang.id as inv_m_barang_id,
      COALESCE(inv_m_barang_nama.nama, inv_m_barang.nama) as nama,
      inv_m_barang.kode,
      inv_m_barang.is_pakai,
      inv_m_satuan.nama as nama_satuan,
      inv_m_kategori.akun_pembelian_id,
      inv_m_kategori.akun_persediaan_brg_id,
      inv_m_kategori.akun_penjualan_id,
      inv_m_kategori.akun_hpp_id,
      akun_pembelian.kode as kodePembelian,
      akun_pembelian.nama as namaPembelian,
      akun_persediaan_brg.kode as kodePersediaan,
      akun_persediaan_brg.nama as namaPersediaan,
      akun_penjualan.kode as kodePenjualan,
      akun_penjualan.nama as namaPenjualan,
      akun_hpp.kode as kodeHpp,
      akun_hpp.nama as namaHpp
      ")
            ->from("inv_pembelian_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_pembelian_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_barang_nama", "inv_m_barang_nama.id = inv_pembelian_det.inv_m_barang_nama_id")
            ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->join('LEFT JOIN', "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->join("left join", "acc_m_akun akun_pembelian", "akun_pembelian.id= inv_m_kategori.akun_pembelian_id")
            ->join("left join", "acc_m_akun akun_penjualan", "akun_penjualan.id = inv_m_kategori.akun_penjualan_id")
            ->join("left join", "acc_m_akun akun_hpp", "akun_hpp.id= inv_m_kategori.akun_hpp_id")
            ->join("left join", "acc_m_akun akun_persediaan_brg", "akun_persediaan_brg.id= inv_m_kategori.akun_persediaan_brg_id")
            ->where("inv_pembelian_det.inv_pembelian_id", "=", $params['id'])
            ->orderBy("inv_pembelian_det.id")
            ->findAll();

    $result = [];
    foreach ($detail as $key => $value) {
        $result[$key] = (array) $value;
        $stok = getStok($db, $value->inv_m_barang_id, $params['acc_m_lokasi_id']);
        $result[$key]['inv_m_barang_id'] = [
            'id' => $value->inv_m_barang_id,
            'kode' => $value->kode,
            'nama' => $value->nama,
            'nama_satuan' => $value->nama_satuan,
            'inv_m_barang_nama_id' => $value->inv_m_barang_nama_id,
            'stok' => $stok,
            'is_pakai' => $value->is_pakai,
            'akun_pembelian_id' => !empty($value->akun_pembelian_id) ? ["id" => $value->akun_pembelian_id, "nama" => $value->namaPembelian, "kode" => $value->kodePembelian] : [],
            'akun_penjualan_id' => !empty($value->akun_penjualan_id) ? ["id" => $value->akun_penjualan_id, "nama" => $value->namaPenjualan, "kode" => $value->kodePenjualan] : [],
            'akun_hpp_id' => !empty($value->akun_hpp_id) ? ["id" => $value->akun_hpp_id, "nama" => $value->namaHpp, "kode" => $value->kodeHpp] : [],
            'akun_persediaan_brg_id' => !empty($value->akun_persediaan_brg_id) ? ["id" => $value->akun_persediaan_brg_id, "nama" => $value->namaPersediaan, "kode" => $value->kodePersediaan] : [],
        ];

        if ($value->diskon == 0) {
            $result[$key]['diskon_persen'] = 0;
        } else {
            $result[$key]['diskon_persen'] = round(($value->diskon / $value->harga) * 100, 1);
        }

        $subharga = ($value->jumlah * $value->harga) - ($value->jumlah * $value->diskon);
        $result[$key]['subtotal'] = $subharga;
        $result[$key]['stok'] = $stok;
    }

    return successResponse($response, ['detail' => $result]);
});

$app->get('/t_pembelian/getJurnal', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $trans_detail = $db->select("
      acc_trans_detail.*,
      acc_m_kontak.nama as namaKontak,
      acc_m_kontak.kode as kodeKontak,
      acc_m_lokasi.nama as namaLokasi,
      acc_m_lokasi.kode as kodeLokasi,
      acc_m_akun.nama as namaAkun,
      acc_m_akun.kode as kodeAkun
      ")
            ->from("acc_trans_detail")
            ->join('LEFT JOIN', "acc_m_kontak", "acc_m_kontak.id = acc_trans_detail.m_kontak_id")
            ->join('LEFT JOIN', "acc_m_lokasi", "acc_m_lokasi.id = acc_trans_detail.m_lokasi_id")
            ->join('LEFT JOIN', "acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
            ->where("acc_trans_detail.reff_id", "=", $params['id'])
            ->where("acc_trans_detail.reff_type", "=", 'inv_pembelian')
            ->findAll();

    $result = [];
    foreach ($trans_detail as $key => $value) {

        $value->akun = ['id' => $value->m_akun_id, 'nama' => $value->namaAkun, 'kode' => $value->kodeAkun];

        $result[$key] = (array) $value;
    }

    return successResponse($response, ['list' => $result]);
});

$app->post('/t_pembelian/delete', function ($request, $response) {

    $params = $request->getParams();
    $db = $this->db;

    try {
        $db->delete('inv_pembelian', ['id' => $params['id']]);
        $db->delete('inv_pembelian_det', ['inv_pembelian_id' => $params['id']]);
        return successResponse($response, []);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->post('/t_pembelian/unpost', function ($request, $response) {

    $params = $request->getParams();
    $db = $this->db;

    try {
        $delete = $db->delete('inv_kartu_stok', ['trans_id' => $params['id'], 'trans_tipe' => 'inv_pembelian_id']);
        $update = $db->update('inv_pembelian', ['status' => 'draft'], ['id' => $params['id']]);

        $paramsHJU = [
          'reff_type'   => 'inv_pembelian',
          'reff_id'     => $params['id'],
        ];
        hapusJurnalUmum($paramsHJU);

        // $jurnal_umum = $db->find("SELECT id FROM acc_jurnal WHERE reff_type='inv_pembelian' AND reff_id='".$params['id']."'");
        // if(!empty($jurnal_umum)){
        //   $db->delete('acc_jurnal', ['id' => $jurnal_umum->id] );
        //   $db->delete('acc_jurnal_det', ['acc_jurnal_id' => $jurnal_umum->id] );
        // }
        //
        // $db->delete('acc_trans_detail', [ 'reff_type' =>'inv_pembelian', 'reff_id'=>$params['id'] ] );

        return successResponse($response, []);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->get('/t_pembelian/getFakturPelabuhan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    try {
        $model = $db->select("
        inv_m_faktur_pajak.*,
        acc_m_kontak.kode as kontak_kode,
        acc_m_kontak.id as kontak_id,
        acc_m_kontak.nama as kontak_nama
      ")
                ->from("inv_m_faktur_pajak")
                ->leftJoin("acc_m_kontak", "acc_m_kontak.id = inv_m_faktur_pajak.acc_m_kontak_id")
                ->join("LEFT JOIN", "inv_pembelian_det_faktur", "inv_m_faktur_pajak.id = inv_m_faktur_pajak_id")
                ->customWhere("inv_pembelian_det_faktur.inv_m_faktur_pajak_id IS NULL")
                ->andWhere("inv_m_faktur_pajak.is_deleted", "=", 0)
                ->andWhere("inv_m_faktur_pajak.jenis_faktur", "=", 'pelabuhan')
                ->findAll();

        if (!empty($model)) {
            foreach ($model as $key => $value) {
                $model[$key]->acc_m_kontak_id = [
                    'id' => $value->kontak_id,
                    'kode' => $value->kontak_kode,
                    'nama' => $value->kontak_nama
                ];

                if ($value->jenis_ppn == 1) {
                    $model[$key]->grandtotal = $value->ppn;
                } else if ($value->jenis_ppn == 10) {
                    $model[$key]->grandtotal = $value->dpp;
                }
            }
        }

        return successResponse($response, $model);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->get('/t_pembelian/getDetailPajak', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    try {
        $model = $db->select("
            inv_pembelian.*,
        inv_m_faktur_pajak.nomor,
        acc_m_kontak.kode as kontak_kode,
        acc_m_kontak.id as kontak_id,
        acc_m_kontak.nama as kontak_nama,
        acc_m_kontak.is_ppn
      ")
                ->from("inv_pembelian_det_faktur")
                ->leftJoin("inv_pembelian", "inv_pembelian.id = inv_pembelian_det_faktur.inv_m_faktur_pajak_id")
                ->leftJoin("inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_pembelian.inv_m_faktur_pajak_id")
                ->leftJoin("acc_m_kontak", "acc_m_kontak.id = inv_pembelian.acc_m_kontak_id")
                ->where("inv_pembelian_det_faktur.inv_pembelian_id", "=", $params['id'])
                ->andWhere("inv_m_faktur_pajak.is_deleted", "=", 0)
                ->andWhere("inv_pembelian_det_faktur.type", "=", 'ppn')
                ->findAll();

        $a = [];
        if (!empty($model)) {

            foreach ($model as $key => $value) {
                $model[$key]->acc_m_kontak_id = [
                    'id' => $value->kontak_id,
                    'kode' => $value->kontak_kode,
                    'nama' => $value->kontak_nama,
                    'is_ppn' => $value->is_ppn
                ];
                $model[$key]->inv_m_faktur_pajak_id = [
                    'id' => $value->inv_m_faktur_pajak_id,
                    'nomor' => $value->nomor,
                ];

//                $model[$key]->faktur  = $model[$key];
//                if ($value->jenis_ppn == 1) {
//                    $model[$key]->grandtotal = $value->ppn;
//                } else if ($value->jenis_ppn == 10) {
//                    $model[$key]->grandtotal = $value->dpp;
//                }
                $a[$key] = (array) $model[$key];
                $a[$key]['faktur'] = $model[$key];
            }
        }

        $model2 = $db->select("inv_pembelian_det_faktur.*,
                acc_m_kontak.kode as kontak_kode,
                acc_m_kontak.id as kontak_id,
                acc_m_kontak.nama as kontak_nama,
                acc_m_kontak.is_ppn")
                ->from("inv_pembelian_det_faktur")
                ->leftJoin("acc_m_kontak", "acc_m_kontak.id = inv_pembelian_det_faktur.acc_m_kontak_id")
                ->where("inv_pembelian_det_faktur.inv_pembelian_id", "=", $params['id'])
                ->where("inv_pembelian_det_faktur.type", "=", 'non ppn')
                ->findAll();

        $b = [];
        foreach ($model2 as $key => $value) {
            $model2[$key]->acc_m_kontak_id = [
                'id' => $value->kontak_id,
                'kode' => $value->kontak_kode,
                'nama' => $value->kontak_nama,
                'is_ppn' => $value->is_ppn
            ];
            $b[$key] = (array) $model2[$key];
        }

        return successResponse($response, ['jasa1' => $a, 'jasa2' => $b]);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->get('/t_pembelian/getDetailBiaya', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    try {
        $model = $db->select("
            inv_pembelian_det_biaya.*
      ")
                ->from("inv_pembelian_det_biaya")
                ->where("inv_pembelian_det_biaya.inv_pembelian_id", "=", $params['id'])
                ->findAll();

        return successResponse($response, $model);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->get('/t_pembelian/getSupplierAll', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    try {
      $db->select("acc_m_kontak.*")
      ->from("acc_m_kontak")
      ->where("acc_m_kontak.is_deleted", "=", 0)
      ->andWhere("acc_m_kontak.type", "!=", 'customer');

      if( !empty($params['nama']) ){
        $db->customWhere("nama LIKE '%". $params['nama'] ."%'", "AND");
      }

      $db->limit(20);
      $model = $db->findAll();

        return successResponse($response, $model);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});
