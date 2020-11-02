<?php

$app->get('/t_retur_penjualan/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("inv_retur_penjualan.kode as kode_retur,
    inv_retur_penjualan.total as total_retur,
    inv_retur_penjualan.id,
    inv_retur_penjualan.acc_m_lokasi_id,
    inv_retur_penjualan.acc_m_kontak_id,
    inv_retur_penjualan.m_akun_id,
    inv_retur_penjualan.m_akun_biaya_id,
    inv_retur_penjualan.no_faktur_pajak,
    inv_retur_penjualan.no_nota,
    inv_retur_penjualan.tanggal as tanggal_retur,
    inv_retur_penjualan.tanggal_penjualan,
    inv_retur_penjualan.tanggal_nota,
    inv_retur_penjualan.lokasi,
    inv_retur_penjualan.rusak,
    inv_retur_penjualan.catatan,
    inv_retur_penjualan.alasan,
    inv_retur_penjualan.created_at,
    inv_retur_penjualan.status,
    inv_retur_penjualan.inv_penjualan_id,
    inv_retur_penjualan.ppn,
    m_user.nama as pembuat,
    acc_m_akun.nama as nama_akun,
    acc_m_akun.kode as kode_akun")
            ->from("inv_retur_penjualan")
            ->join("LEFT JOIN", "m_user", "m_user.id = inv_retur_penjualan.created_by")
            ->join("LEFT JOIN", "acc_m_akun", "acc_m_akun.id = inv_retur_penjualan.m_akun_id");

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
        // $penjualan = $db->select("inv_penjualan.*, inv_m_faktur_pajak.nomor")
        //         ->from("inv_penjualan")
        //         ->join("JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
        //         ->where("inv_penjualan.id", "=", $value->inv_penjualan_id)
        //         ->find();

        // $models[$key]->inv_penjualan_id = $penjualan;
        // $models[$key]->acc_m_lokasi_id = isset($allLokasi[$value->acc_m_lokasi_id]) ? $allLokasi[$value->acc_m_lokasi_id] : [];
        // $models[$key]->acc_m_lokasi_id_retur = isset($allLokasi[$value->acc_m_lokasi_id_retur]) ? $allLokasi[$value->acc_m_lokasi_id_retur] : [];

        $customer = $db->select("id, nik, nama, npwp, alamat, kota")
            ->from("acc_m_kontak")
            ->where("id", "=", $value->acc_m_kontak_id)
            ->find();

        $models[$key]->acc_m_kontak_id = isset($customer) ? $customer : [];
        $models[$key]->m_akun_id = [
            "id" => $value->m_akun_id,
            "nama" => $value->nama_akun,
            "kode" => $value->kode_akun
        ];
        $models[$key]->grand_total = $value->total_retur + $value->ppn;
        $models[$key]->sub_total = $value->total_retur;
        $models[$key]->stok = 0;

        $models[$key]->form1 = substr($models[$key]->no_faktur_pajak, 0, 3);
        $models[$key]->form2 = substr($models[$key]->no_faktur_pajak, 4, 3);
        $models[$key]->form3 = substr($models[$key]->no_faktur_pajak, 8, 2);
        $models[$key]->form4 = substr($models[$key]->no_faktur_pajak, 11, 8);

        // Format tanggal
        $models[$key]->tanggal_retur      = date("Y-m-d", $value->tanggal_retur);
        $models[$key]->tanggal_penjualan  = date("Y-m-d", $value->tanggal_penjualan);
        $models[$key]->tanggal_nota       = date("Y-m-d", $value->tanggal_nota);
    }

    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
    ]);
});
$app->post('/t_retur_penjualan/save', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    try {
        $data['form']['acc_m_lokasi_id']      = !empty($data['form']['acc_m_lokasi_id']['id']) ? $data['form']['acc_m_lokasi_id']['id'] : 1;
        $data['form']['acc_m_lokasi_id_retur'] = !empty($data['form']['acc_m_lokasi_id_retur']['id']) ? $data['form']['acc_m_lokasi_id_retur']['id'] : 1;
        $data['form']['tanggal_penjualan']    = strtotime(date("d-m-Y", strtotime($data['form']['tanggal_penjualan'])));
        $data['form']['inv_penjualan_id']     = isset($data['form']['inv_penjualan_id']['id']) ? $data['form']['inv_penjualan_id']['id'] : null;
        $data['form']['m_akun_biaya_id']      = isset($data['form']['m_akun_biaya_id']) ? $data['form']['m_akun_biaya_id']['id'] : null;
        $data['form']['acc_m_kontak_id']      = isset($data['form']['acc_m_kontak_id']) ? $data['form']['acc_m_kontak_id']['id'] : null;
        $data['form']['tanggal_nota']         = strtotime(date("d-m-Y", strtotime($data['form']['tanggal_nota'])));
        $data['form']['m_akun_id']            = isset($data['form']['m_akun_id']) ? $data['form']['m_akun_id']['id'] : null;
        $data['form']['tanggal']              = strtotime(date("d-m-Y", strtotime($data['form']['tanggal_retur'])));
        $data['form']['total']                = !empty($data['form']['grand_total']) ? $data['form']['grand_total'] - $data['form']['ppn'] : 0;
        $data['form']['kode']                 = !empty($data['form']['kode_retur']) ? $data['form']['kode_retur'] : generateNoTransaksi('retur_penjualan', 0);
        $data['form']['ppn']                  = !empty($data['form']['ppn']) ? $data['form']['ppn'] : 0;

        if (!empty($data['form']['form1']) && !empty($data['form']['form2']) && !empty($data['form']['form3']) && !empty($data['form']['form4'])) {
            $data['form']['no_faktur_pajak'] = $data['form']['form1'] . '.' . $data['form']['form2'] . '-' . $data['form']['form3'] . '.' . $data['form']['form4'];
        } else {
            $data['form']['no_faktur_pajak'] = null;
        }

        if (isset($data['form']["id"])) {
            $model = $db->update("inv_retur_penjualan", $data['form'], array('id' => $data['form']['id']));
        } else {
            $model = $db->insert("inv_retur_penjualan", $data['form']);
        }

        $lokasi = 1;
        $lokasi_retur = 1;

        $hapus_kartu_stok = $db->delete("inv_kartu_stok", [
          'trans_tipe'  => 'inv_retur_penjualan_id',
          'trans_id'    => $model->id
        ]);

        if (!empty($data['detail'])) {
            $db->delete('inv_retur_penjualan_det', ['inv_retur_penjualan_id' => $model->id]);
            foreach ($data['detail'] as $val) {
                unset($val['id']);
                $harga_pokok = $val['inv_m_barang_id']['harga_pokok'];
                $val['inv_retur_penjualan_id'] = $model->id;
                $val['inv_m_barang_id'] = !empty($val['inv_m_barang_id']['id']) ? $val['inv_m_barang_id']['id'] : null;

                $detail = $db->insert("inv_retur_penjualan_det", $val);

                /* PENGISIAN KARTU STOK */
                if ($val['jumlah_retur'] > 0 && $data['form']['status'] != 'draft') {
                    /* PENGISIAN KARTU STOK */
                    $dataa = array(
                        "kode"              => $model->kode,
                        "inv_m_barang_id"   => $val['inv_m_barang_id'],
                        "catatan"           => 'Retur Penjualan',
                        "jumlah_masuk"      => $detail->jumlah_retur,
                        "stok"              => $detail->jumlah_retur,
                        "harga_masuk"       => $detail->harga_retur,
                        "trans_id"          => $model->id,
                        "trans_tipe"        => 'inv_retur_penjualan_id',
                        "jenis_kas"         => 'masuk',
                        "acc_m_lokasi_id"   => $lokasi_retur,
                        "tanggal"           => $model->tanggal,
                    );

                    $insertKartuStok = $db->insert("inv_kartu_stok", $dataa);
                }
            }
        }

        // Insert Jurnal
        if($data['form']['status'] != 'draft'){
          $paramsJurnal = [
            'reff_type'   => 'inv_retur_penjualan',
            'reff_id'     => $model->id,
          ];

          simpanJurnal($paramsJurnal);
        }

        // Jurnal Akuntansi
        // $transDetail = [];
        // if (!empty($data['listJurnal'])) {
        //     $db->delete('acc_trans_detail', ['reff_id' => $model->id, 'reff_type' => 'inv_retur_penjualan']);

        //     foreach ($data['listJurnal'] as $key => $value) {
        //         $transDetail[$key]['m_lokasi_id'] = $lokasi;
        //         $transDetail[$key]['m_akun_id'] = $value['akun']['id'];
        //         $transDetail[$key]['m_kontak_id'] = $model->acc_m_kontak_id;
        //         $transDetail[$key]['debit'] = $value['debit'];
        //         $transDetail[$key]['kredit'] = $value['kredit'];
        //         $transDetail[$key]['kode'] = $model->kode;
        //         $transDetail[$key]['keterangan'] = $value['keterangan'];
        //         $transDetail[$key]['reff_id'] = $model->id;
        //         $transDetail[$key]['tanggal'] = date("Y-m-d", $model->tanggal);
        //         $transDetail[$key]['reff_type'] = "inv_retur_penjualan";
        //         $transDetail[$key]['m_lokasi_jurnal_id'] = $lokasi;
        //     }
        //     insertTransDetail($transDetail);
        // }
        // Jurnal Akuntansi - End

        return successResponse($response, $model);
    } catch (Exception $exc) {
        echo $exc;
        die;
        return unprocessResponse($response, $exc);
    }
});
$app->post('/t_retur_penjualan/unpost', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $data['form']['acc_m_lokasi_id'] = !empty($data['form']['acc_m_lokasi_id']['id']) ? $data['form']['acc_m_lokasi_id']['id'] : 1;
    $data['form']['acc_m_lokasi_id_retur'] = !empty($data['form']['acc_m_lokasi_id_retur']['id']) ? $data['form']['acc_m_lokasi_id_retur']['id'] : 1;
    $data['form']['acc_m_kontak_id'] = isset($data['form']['acc_m_kontak_id']) ? $data['form']['acc_m_kontak_id']['id'] : null;
    $data['form']['inv_penjualan_id'] = isset($data['form']['inv_penjualan_id']['id']) ? $data['form']['inv_penjualan_id']['id'] : null;
    $data['form']['m_akun_biaya_id'] = isset($data['form']['m_akun_biaya_id']) ? $data['form']['m_akun_biaya_id']['id'] : null;
    $data['form']['m_akun_id'] = isset($data['form']['m_akun_id']) ? $data['form']['m_akun_id']['id'] : null;
    $data['form']['tanggal'] = strtotime(date("d-m-Y", strtotime($data['form']['tanggal_retur'])));
    $data['form']['total'] = !empty($data['form']['grand_total']) ? $data['form']['grand_total'] - $data['form']['ppn'] : 0;
    $data['form']['kode'] = !empty($data['form']['kode_retur']) ? $data['form']['kode_retur'] : generateNoTransaksi('retur_penjualan', 0);
    $data['form']['ppn'] = !empty($data['form']['ppn']) ? $data['form']['ppn'] : 0;

    if (isset($data['form']) && !empty($data['form'])) {
        $model = $db->update("inv_retur_penjualan", $data['form'], array('id' => $data['form']['id']));

        /* HAPUS KARTU STOK */
        $hapus_kartu_stok = $db->delete("inv_kartu_stok", ['trans_tipe' => 'inv_retur_penjualan_id', 'trans_id' => $model->id]);
    }

    return successResponse($response, $model);
});

$app->get('/t_retur_penjualan/getAll', function ($request, $response) {
    $db = $this->db;

    $db->select("inv_penjualan.*, inv_m_faktur_pajak.nomor")
            ->from("inv_penjualan")
            ->join("JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
            ->where("is_draft", "=", "0")
            ->where("status", "!=", "dibatalkan");

    $models = $db->findAll();

    successResponse($response, $models);
});

$app->get('/t_retur_penjualan/getKode', function ($request, $response) {
    $db = $this->db;
    $models = generateKode($db);

    return successResponse($response, $models);
});

function generateKode($db) {
    $cekKode = $db->select('*')
            ->from("inv_retur_penjualan")
            ->where("kode", "!=", "")
            ->orderBy('kode DESC')
            ->find();

    if ($cekKode) {
        $kode_terakhir = $cekKode->kode;
    } else {
        $kode_terakhir = 0;
    }

    $kodeReturPenjualan = (substr($kode_terakhir, -5) + 1);
    $kode = substr('00000' . $kodeReturPenjualan, strlen($kodeReturPenjualan));
    $kode = 'RJ' . date("y") . "/" . $kode;

    return $kode;
}

$app->get('/t_retur_penjualan/getPenjualan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $penjualan = $db->select("
     inv_penjualan.kode as kode_penjualan,
     inv_penjualan.acc_m_lokasi_id,
     inv_penjualan.acc_m_kontak_id,
     inv_penjualan.m_akun_id,
     inv_penjualan.m_akun_biaya_id,
     inv_penjualan.tanggal,
     inv_penjualan.total,
     inv_penjualan.cash,
     inv_penjualan.id as inv_penjualan_id")
            ->from("inv_penjualan")
            ->where("inv_penjualan.id", "=", $params['id'])
            ->find();

    if (isset($penjualan->acc_m_kontak_id)) {
        $acc_m_kontak_id = $db->find("SELECT * FROM acc_m_kontak WHERE id = $penjualan->acc_m_kontak_id");
    }
    $penjualan->acc_m_kontak_id = !empty($acc_m_kontak_id) ? $acc_m_kontak_id : [];

    if (isset($penjualan->acc_m_lokasi_id)) {
        $acc_m_lokasi_id = $db->find("SELECT * FROM acc_m_lokasi WHERE id = $penjualan->acc_m_lokasi_id");
    }
    $penjualan->acc_m_lokasi_id = !empty($acc_m_lokasi_id) ? $acc_m_lokasi_id : [];

    $penjualan->inv_penjualan_id = $params;
    $penjualan->kode_retur = generateKode($db);

    $detail_retur = $db->select("inv_retur_penjualan_det.*, inv_m_barang.nama, FROM_UNIXTIME(inv_retur_penjualan.tanggal, '%d-%m-%Y') as tanggal, inv_penjualan.kode as kode_penjualan, inv_retur_penjualan.kode as kode_retur")
            ->from("inv_retur_penjualan_det")
            ->join("join", "inv_retur_penjualan", "inv_retur_penjualan.id = inv_retur_penjualan_id")
            ->join("join", "inv_penjualan", "inv_penjualan.id = inv_retur_penjualan.inv_penjualan_id")
            ->join("join", "inv_m_barang", "inv_m_barang.id = inv_retur_penjualan_det.inv_m_barang_id")
            ->where("inv_penjualan_id", "=", $params['id'])
            ->findAll();

    $arr_retur = [];
    foreach ($detail_retur as $key => $val) {
        $arr_retur[$val->inv_m_barang_id]['jumlah_retur'] = isset($arr_retur[$val->inv_m_barang_id]['jumlah_retur']) ? $arr_retur[$val->inv_m_barang_id]['jumlah_retur'] + $val->jumlah_retur : $val->jumlah_retur;
    }

    $detail = $db->select("
    inv_penjualan_det.*,
    inv_m_barang.id as inv_m_barang_id,
    inv_m_barang.nama,
    inv_m_barang.kode,
    inv_m_barang.harga_pokok,
    inv_m_satuan.nama as nama_satuan,
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
            ->from("inv_penjualan_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->join('LEFT JOIN', "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->join("left join", "acc_m_akun akun_pembelian", "akun_pembelian.id= inv_m_kategori.akun_pembelian_id")
            ->join("left join", "acc_m_akun akun_penjualan", "akun_penjualan.id = inv_m_kategori.akun_penjualan_id")
            ->join("left join", "acc_m_akun akun_hpp", "akun_hpp.id= inv_m_kategori.akun_hpp_id")
            ->where("inv_penjualan_det.inv_penjualan_id", "=", $params['id'])
            ->findAll();

    $result = [];
    foreach ($detail as $key => $value) {
        $result[$key] = (array) $value;
        $stok = getStok($db, $value->inv_m_barang_id, $penjualan->acc_m_lokasi_id->id);

//        $stok = isset($arr_retur[$val->inv_m_barang_id]['jumlah_retur']) ? $stok - $arr_retur[$val->inv_m_barang_id]['jumlah_retur'] : $stok;

        $result[$key]['inv_m_barang_id'] = [
            'id' => $value->inv_m_barang_id,
            'kode' => $value->kode,
            'nama' => $value->nama,
            'nama_satuan' => $value->nama_satuan,
            'stok' => $stok,
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

        $result[$key]['jumlah'] = isset($arr_retur[$value->inv_m_barang_id]['jumlah_retur']) ? $value->jumlah - $arr_retur[$value->inv_m_barang_id]['jumlah_retur'] : $value->jumlah;
    }

    return successResponse($response, ['form' => $penjualan, 'detail' => $result, 'detail_retur' => $detail_retur]);
});
$app->get('/t_retur_penjualan/getDetail', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $detail = $db->select("
      inv_retur_penjualan_det.*,
      inv_retur_penjualan.inv_penjualan_id,
      inv_m_barang.id as inv_m_barang_id,
      inv_m_barang.nama,
      inv_m_barang.kode,
      inv_m_barang.harga_pokok,
      inv_m_satuan.nama as nama_satuan,
      akun_pembelian.kode as kodePembelian,
      akun_pembelian.nama as namaPembelian,
      akun_persediaan_brg.kode as kodePersediaan,
      akun_persediaan_brg.nama as namaPersediaan,
      akun_penjualan.kode as kodePenjualan,
      akun_penjualan.nama as namaPenjualan,
      akun_hpp.kode as kodeHpp,
      akun_hpp.nama as namaHpp,
      inv_m_kategori.akun_pembelian_id,
      inv_m_kategori.akun_penjualan_id,
      inv_m_kategori.akun_persediaan_brg_id,
      inv_m_kategori.akun_hpp_id
      ")
            ->from("inv_retur_penjualan_det")
            ->join('LEFT JOIN', "inv_retur_penjualan", "inv_retur_penjualan.id = inv_retur_penjualan_det.inv_retur_penjualan_id")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_retur_penjualan_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->join('LEFT JOIN', "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->join("left join", "acc_m_akun akun_pembelian", "akun_pembelian.id= inv_m_kategori.akun_pembelian_id")
            ->join("left join", "acc_m_akun akun_penjualan", "akun_penjualan.id = inv_m_kategori.akun_penjualan_id")
            ->join("left join", "acc_m_akun akun_hpp", "akun_hpp.id= inv_m_kategori.akun_hpp_id")
            ->join("left join", "acc_m_akun akun_persediaan_brg", "akun_persediaan_brg.id= inv_m_kategori.akun_persediaan_brg_id")
            ->where("inv_retur_penjualan_det.inv_retur_penjualan_id", "=", $params['id'])
            ->findAll();

//    pd($detail);

    $result = [];
    foreach ($detail as $key => $value) {
        $result[$key] = (array) $value;
        // $stok = getStok($db, $value->inv_m_barang_id, $params['acc_m_lokasi_id']);
        $result[$key]['inv_m_barang_id'] = [
            'id' => $value->inv_m_barang_id,
            'kode' => $value->kode,
            'nama' => $value->nama,
            'nama_satuan' => $value->nama_satuan,
            // 'stok' => $stok,
            'harga_pokok' => $value->harga_pokok,
            'akun_pembelian_id' => !empty($value->akun_pembelian_id) ? ["id" => $value->akun_pembelian_id, "nama" => $value->namaPembelian, "kode" => $value->kodePembelian] : [],
            'akun_penjualan_id' => !empty($value->akun_penjualan_id) ? ["id" => $value->akun_penjualan_id, "nama" => $value->namaPenjualan, "kode" => $value->kodePenjualan] : [],
            'akun_hpp_id' => !empty($value->akun_hpp_id) ? ["id" => $value->akun_hpp_id, "nama" => $value->namaHpp, "kode" => $value->kodeHpp] : [],
            'akun_persediaan_brg_id' => !empty($value->akun_persediaan_brg_id) ? ["id" => $value->akun_persediaan_brg_id, "nama" => $value->namaPersediaan, "kode" => $value->kodePersediaan] : [],
        ];

        $subharga = ($value->jumlah_retur * $value->harga_retur);
        $result[$key]['subtotal'] = $subharga;
        // $result[$key]['stok'] = $stok;
    }

    return successResponse($response, ['detail' => $result]);
});

$app->get('/t_retur_penjualan/getJurnal', function ($request, $response) {
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
            ->where("acc_trans_detail.reff_type", "=", 'inv_retur_penjualan')
            ->findAll();

    $result = [];
    foreach ($trans_detail as $key => $value) {

        $value->akun = ['id' => $value->m_akun_id, 'nama' => $value->namaAkun, 'kode' => $value->kodeAkun];

        $result[$key] = (array) $value;
    }

    return successResponse($response, ['list' => $result]);
});

$app->get('/t_retur_penjualan/notaRetur', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select("
      inv_retur_penjualan.*,
      FROM_UNIXTIME(inv_retur_penjualan.tanggal, '%d-%m-%Y') as tanggal_retur,
      FROM_UNIXTIME(inv_retur_penjualan.tanggal_nota, '%d-%m-%Y') as tanggal_nota,
      acc_m_kontak.kode as kode_customer,
      acc_m_kontak.nama as nama_customer,
      acc_m_kontak.alamat, acc_m_kontak.npwp
    ")
    ->from("inv_retur_penjualan")
    ->join("join", "acc_m_kontak", "acc_m_kontak.id = inv_retur_penjualan.acc_m_kontak_id")
    ->where("inv_retur_penjualan.id", "=", $params['id']);
    $models = $db->find();

    $barang = $db->select("
      SUM(jumlah_retur) as total,
      SUM(inv_retur_penjualan_det.jumlah_retur * inv_retur_penjualan_det.harga_retur) as harga_total,
      inv_m_barang.nama,
      inv_m_satuan.nama as nama_satuan
    ")
    ->from("inv_retur_penjualan_det")
    ->join("left join", "inv_retur_penjualan", "inv_retur_penjualan.id = inv_retur_penjualan_det.inv_retur_penjualan_id")
    ->join("left join", "inv_m_barang", "inv_m_barang.id = inv_retur_penjualan_det.inv_m_barang_id")
    ->join("left join", "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
    ->groupBy("inv_m_barang_id")
    ->where("inv_retur_penjualan_det.inv_retur_penjualan_id", "=", $params['id'])
    ->findAll();

    $data = $models;

    if (isset($params['print']) && $params['print'] == 1) {
        $view = twigView();
        $content = $view->fetch("surat/nota_retur.html", [
            'data' => $data,
            'detail' => $barang,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    }
});
