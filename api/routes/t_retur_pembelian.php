<?php

$app->get('/t_retur_pembelian/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("inv_retur_pembelian.kode as kode_retur,
      inv_retur_pembelian.total as total_retur,
      inv_retur_pembelian.id,
      inv_retur_pembelian.acc_m_lokasi_id,
      inv_retur_pembelian.tanggal as tanggal_retur,
      inv_retur_pembelian.rusak,
      inv_retur_pembelian.catatan,
      inv_retur_pembelian.alasan,
      inv_retur_pembelian.created_at,
      inv_retur_pembelian.status,
      inv_retur_pembelian.inv_pembelian_id,
      m_user.nama as pembuat,
      inv_pembelian.kode as kode_pembelian,
      inv_pembelian.tanggal,
      inv_pembelian.total,
      inv_pembelian.cash
    ")
    ->from("inv_retur_pembelian")
    ->join("LEFT JOIN", "m_user", "m_user.id = inv_retur_pembelian.created_by")
    ->join("LEFT JOIN", "inv_pembelian", "inv_pembelian.id = inv_retur_pembelian.inv_pembelian_id");

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

    $allLokasi = getAllData($db, 'acc_m_lokasi', 'id');

    foreach ($models as $key => $value) {
        $pembelian = $db->select("
          inv_pembelian.*,
          inv_m_faktur_pajak.nomor
          ")
        ->from("inv_pembelian")
        ->join("LEFT JOIN", "inv_m_faktur_pajak", "
          inv_m_faktur_pajak.id = inv_pembelian.inv_m_faktur_pajak_id AND
          inv_m_faktur_pajak.jenis_faktur = 'pembelian'
          ")
        ->where("inv_pembelian.id", "=", $value->inv_pembelian_id)
        ->find();

        $models[$key]->grand_total = $value->total_retur;
        $models[$key]->acc_m_lokasi_id = isset($allLokasi[$value->acc_m_lokasi_id]) ? $allLokasi[$value->acc_m_lokasi_id] : [];
        $models[$key]->stok = 0;
        $models[$key]->inv_pembelian_id = $pembelian;
    }

    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
    ]);
});
$app->post('/t_retur_pembelian/save', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $data['form']['acc_m_lokasi_id'] = !empty($data['form']['acc_m_lokasi_id']['id']) ? $data['form']['acc_m_lokasi_id']['id'] : null;
    $data['form']['inv_pembelian_id'] = isset($data['form']['inv_pembelian_id']) ? $data['form']['inv_pembelian_id']['id'] : null;
    $data['form']['m_akun_biaya_id'] = isset($data['form']['m_akun_biaya_id']) ? $data['form']['m_akun_biaya_id']['id'] : null;
    $data['form']['m_akun_id'] = isset($data['form']['m_akun_id']) ? $data['form']['m_akun_id']['id'] : null;
    $data['form']['tanggal'] = strtotime(date("d-m-Y", strtotime($data['form']['tanggal_retur'])));
    $data['form']['kode'] = !empty($data['form']['kode_retur']) ? $data['form']['kode_retur'] : null;
    $data['form']['total'] = !empty($data['form']['grand_total']) ? $data['form']['grand_total'] : 0;

    if (isset($data['form']["id"])) {
        $model = $db->update("inv_retur_pembelian", $data['form'], array('id' => $data['form']['id']));
    } else {
        $data['form']['status'] = 'berhasil';
        $model = $db->insert("inv_retur_pembelian", $data['form']);
    }

  if (!empty($data['detail'])) {
      $db->delete('inv_retur_pembelian_det', ['inv_retur_pembelian_id' => $model->id]);

      foreach ($data['detail'] as $val) {
          unset($val['id']);
          $val['inv_retur_pembelian_id'] = $model->id;
          $harga_pokok = $val['inv_m_barang_id']['harga_pokok'];
          $val['inv_m_barang_id'] = !empty($val['inv_m_barang_id']['id']) ? $val['inv_m_barang_id']['id'] : null;

          $db->insert("inv_retur_pembelian_det", $val);

          /* PENGISIAN KARTU STOK */
          if ($val['jumlah_retur'] > 0) {
              $dataa = array(
                "kode"              => $model->kode,
                "inv_m_barang_id"   => $val['inv_m_barang_id'],
                "catatan"           => 'Retur Pembelian',
                "jumlah_keluar"     => $val['jumlah_retur'],
                "harga_keluar"      => $val['harga_retur'],
                "trans_id"          => $model->id,
                "trans_tipe"        => 'inv_retur_pembelian_id',
                "hpp"               => $val['harga_retur'],
                "jenis_kas"         => 'keluar',
                "acc_m_lokasi_id"   => $data['form']['acc_m_lokasi_id'],
                "tanggal"           => strtotime(date("Y-m-d H:i:s")),
              );
              $insertKartuStok = $db->insert("inv_kartu_stok", $dataa);
          }
      }
  }

  // Insert Jurnal
  if($model->status != 'draft'){
    $paramsJurnal = [
      'reff_type'   => 'inv_retur_pembelian',
      'reff_id'     => $model->id,
    ];

    simpanJurnal($paramsJurnal);
  }

  // Jurnal Akuntansi
  // $transDetail = [];
  // if (!empty($data['listJurnal'])) {
  //     $db->delete('acc_trans_detail', ['reff_id' => $model->id, 'reff_type' => 'inv_retur_pembelian']);
  //
  //     foreach ($data['listJurnal'] as $key => $value) {
  //         $transDetail[$key]['m_lokasi_id'] = $model->acc_m_lokasi_id;
  //         $transDetail[$key]['m_akun_id'] = $value['akun']['id'];
  // //                $transDetail[$key]['m_kontak_id'] = $model->acc_m_kontak_id;
  //         $transDetail[$key]['debit'] = $value['debit'];
  //         $transDetail[$key]['kredit'] = $value['kredit'];
  //         $transDetail[$key]['kode'] = $model->kode;
  //         $transDetail[$key]['keterangan'] = $value['keterangan'];
  //         $transDetail[$key]['reff_id'] = $model->id;
  //         $transDetail[$key]['tanggal'] = date("Y-m-d", $model->tanggal);
  //         $transDetail[$key]['reff_type'] = "inv_retur_pembelian";
  //         $transDetail[$key]['m_lokasi_jurnal_id'] = $model->acc_m_lokasi_id;
  //     }
  //     insertTransDetail($transDetail);
  // }
  // Jurnal Akuntansi - End
  return successResponse($response, $model);
});

$app->get('/t_retur_pembelian/getAll', function ($request, $response) {
    $db = $this->db;

    $db->select("*")
            ->from("inv_pembelian");

    $models = $db->findAll();

    successResponse($response, $models);
});

$app->get('/t_retur_pembelian/cariFP', function ($request, $response) {
    $db     = $this->db;
    $params = $request->getParams();

    $db->select("
      inv_pembelian.*,
      inv_m_faktur_pajak.nomor
    ")
    ->from("inv_pembelian")
    ->join("LEFT JOIN", "inv_m_faktur_pajak", "
      inv_m_faktur_pajak.id = inv_pembelian.inv_m_faktur_pajak_id AND
      inv_m_faktur_pajak.jenis_faktur = 'pembelian'
      ");

    if( !empty($params['cari']) ){
      $db->where("inv_m_faktur_pajak.nomor", "LIKE", $params['cari']);
    }
    $db->where("inv_m_faktur_pajak.nomor", "!=", "");
    $db->orderBy('inv_m_faktur_pajak.nomor ASC')
    ->limit('20');

    $models = $db->findAll();

    successResponse($response, $models);
});

// function generateKode($db) {
//     $cekKode = $db->select('*')
//             ->from("inv_retur_pembelian")
//             ->where("kode", "!=", "")
//             ->orderBy('kode DESC')
//             ->find();

//     if ($cekKode) {
//         $kode_terakhir = $cekKode->kode;
//     } else {
//         $kode_terakhir = 0;
//     }

//     $kodeReturPembelian = (substr($kode_terakhir, -5) + 1);
//     $kode = substr('00000' . $kodeReturPembelian, strlen($kodeReturPembelian));
//     $kode = 'RB' . date("y") . "/" . $kode;

//     return $kode;
// }

$app->get('/t_retur_pembelian/getPembelian', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $pembelian = $db->select("
     inv_pembelian.kode as kode_pembelian,
     inv_pembelian.acc_m_lokasi_id,
     inv_pembelian.acc_m_kontak_id,
     inv_pembelian.acc_m_akun_id,
     inv_pembelian.acc_m_akun_biaya_id,
     inv_pembelian.tanggal,
     inv_pembelian.total,
     inv_pembelian.cash,
     inv_pembelian.id as inv_pembelian_id")
            ->from("inv_pembelian")
            ->where("inv_pembelian.id", "=", $params['id'])
            ->find();

    if (isset($pembelian->acc_m_kontak_id)) {
        $acc_m_kontak_id = $db->find("SELECT * FROM acc_m_kontak WHERE id = $pembelian->acc_m_kontak_id");
    }
    $pembelian->acc_m_kontak_id = !empty($acc_m_kontak_id) ? $acc_m_kontak_id : [];

    if (isset($pembelian->acc_m_lokasi_id)) {
        $acc_m_lokasi_id = $db->find("SELECT * FROM acc_m_lokasi WHERE id = $pembelian->acc_m_lokasi_id");
    }
    $pembelian->acc_m_lokasi_id = !empty($acc_m_lokasi_id) ? $acc_m_lokasi_id : [];

    $pembelian->inv_pembelian_id = $params;
    // $pembelian->kode_retur = generateKode($db);

    $detail = $db->select("
    inv_pembelian_det.*,
    inv_m_barang.id as inv_m_barang_id,
    inv_m_barang.nama,
    inv_m_barang.kode,
    inv_m_satuan.nama as nama_satuan,
    inv_m_barang.type_barcode,
    inv_m_barang.harga_pokok,
    inv_m_kategori.nama as nama_kategori,
      inv_m_kategori.akun_pembelian_id,
      inv_m_kategori.akun_penjualan_id,
      inv_m_kategori.akun_hpp_id,
    akun_pembelian.kode as kodePembelian,
      akun_pembelian.nama as namaPembelian,
      akun_penjualan.kode as kodePenjualan,
      akun_penjualan.nama as namaPenjualan,
      akun_hpp.kode as kodeHpp,
      akun_hpp.nama as namaHpp")
            ->from("inv_pembelian_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_pembelian_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->join('LEFT JOIN', "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->join("left join", "acc_m_akun akun_pembelian", "akun_pembelian.id= inv_m_kategori.akun_pembelian_id")
            ->join("left join", "acc_m_akun akun_penjualan", "akun_penjualan.id = inv_m_kategori.akun_penjualan_id")
            ->join("left join", "acc_m_akun akun_hpp", "akun_hpp.id= inv_m_kategori.akun_hpp_id")
            ->where("inv_pembelian_det.inv_pembelian_id", "=", $params['id'])
            ->findAll();

    $result = [];
    foreach ($detail as $key => $value) {
        $result[$key] = (array) $value;
        $stok = getStok($db, $value->inv_m_barang_id, $pembelian->acc_m_lokasi_id->id);



        $result[$key]['inv_m_barang_id'] = [
            'id' => $value->inv_m_barang_id,
            'kode' => $value->kode,
            'nama' => $value->nama,
            'nama_satuan' => $value->nama_satuan,
            'stok' => $stok,
            'type_barcode' => $value->type_barcode,
            'harga_pokok' => $value->harga_pokok,
            'akun_pembelian_id' => !empty($value->akun_pembelian_id) ? ["id" => $value->akun_pembelian_id, "nama" => $value->namaPembelian, "kode" => $value->kodePembelian] : [],
            'akun_penjualan_id' => !empty($value->akun_penjualan_id) ? ["id" => $value->akun_penjualan_id, "nama" => $value->namaPenjualan, "kode" => $value->kodePenjualan] : [],
            'akun_hpp_id' => !empty($value->akun_hpp_id) ? ["id" => $value->akun_hpp_id, "nama" => $value->namaHpp, "kode" => $value->kodeHpp] : [],
        ];

        $result[$key]['subtotal'] = $value->jumlah * $value->harga;
        $result[$key]['stok'] = $stok;
        $result[$key]['harga_retur'] = $value->harga;
        $result[$key]['jumlah_retur'] = 0;
        $result[$key]['subtotal'] = 0;
    }

    return successResponse($response, ['form' => $pembelian, 'detail' => $result]);
});
$app->get('/t_retur_pembelian/getDetail', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $detail = $db->select("
      inv_retur_pembelian_det.*,
      inv_m_barang.id as inv_m_barang_id,
      inv_m_barang.nama,
      inv_m_barang.kode,
      inv_m_satuan.nama as nama_satuan,
      inv_m_barang.type_barcode,
      inv_m_barang.harga_pokok
      ")
            ->from("inv_retur_pembelian_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_retur_pembelian_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->where("inv_retur_pembelian_det.inv_retur_pembelian_id", "=", $params['id'])
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
            'stok' => $stok,
            'type_barcode' => $value->type_barcode,
            'harga_pokok' => $value->harga_pokok
        ];

        $subharga = ($value->jumlah_retur * $value->harga_retur);
        $result[$key]['subtotal'] = $subharga;
        $result[$key]['stok'] = $stok;
    }

    return successResponse($response, ['detail' => $result]);
});

$app->get('/t_retur_pembelian/getJurnal', function ($request, $response) {
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
            ->where("acc_trans_detail.reff_type", "=", 'inv_retur_pembelian')
            ->findAll();

    $result = [];
    foreach ($trans_detail as $key => $value) {

        $value->akun = ['id' => $value->m_akun_id, 'nama' => $value->namaAkun, 'kode' => $value->kodeAkun];

        $result[$key] = (array) $value;
    }

    return successResponse($response, ['list' => $result]);
});
