<?php

function generateKode($db) {
    $cekKode = $db->select('*')
            ->from("inv_transfer")
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
    $kode = 'TF' . date("y") . "/" . $kode;

    return $kode;
}

$app->get('/t_transfer_barang/cariBarang', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $model = $db->select("
          inv_m_kategori.akun_pembelian_id,
          inv_m_kategori.akun_penjualan_id,
          inv_m_kategori.akun_hpp_id,
          inv_m_kategori.akun_persediaan_brg_id,
          inv_m_kategori.akun_rpenjualan_id,
          inv_m_kategori.akun_rpembelian_id,
          inv_m_barang.nama,
          inv_m_barang.harga_beli,
          inv_m_barang.harga_jual,
          inv_m_barang.id as barang_id,
          inv_m_satuan.id as satuan_id,
          inv_m_barang.kode,
          inv_m_barang.type_barcode as jenis,
          inv_m_barang.harga_pokok,
          
          inv_m_kategori.nama as kategori,
          inv_m_satuan.nama as satuan,
          acc_m_akun.id as akun_id,
          acc_m_akun.kode as akun_kode,
          acc_m_akun.nama as akun_nama,
          acc_m_akun2.kode as hpp_kode,
          acc_m_akun2.nama as hpp_nama
        ")
            ->from("inv_m_barang")
            ->where("inv_m_barang.is_deleted", "=", '0')
            ->andWhere("inv_m_barang.type", "=", 'barang')
            ->customWhere("
            inv_m_barang.nama like '%$params[val]%' or
            inv_m_barang.kode like '%$params[val]%' or
            inv_m_barang.barcode like '%$params[val]%'
        ", "AND")
            ->join('left join', 'inv_m_kategori', 'inv_m_kategori.id = inv_m_barang.inv_m_kategori_id')
            ->join('left join', 'inv_m_satuan', 'inv_m_satuan.id = inv_m_barang.inv_m_satuan_id')
            ->join('left join', 'acc_m_akun', 'acc_m_akun.id = inv_m_kategori.akun_persediaan_brg_id')
            ->join('left join', 'acc_m_akun acc_m_akun2', 'acc_m_akun2.id = inv_m_kategori.akun_hpp_id')
            ->findAll();

    foreach ($model as $key => $value) {
        $value->stok = pos_stok($value->barang_id, $params['acc_m_lokasi_id']);
        $value->akun_persediaan = [
            'id' => $value->akun_id,
            'kode' => $value->akun_kode,
            'nama' => $value->akun_nama,
        ];
        $value->akun_hpp = [
            'id' => $value->akun_hpp_id,
            'kode' => $value->hpp_kode,
            'nama' => $value->hpp_nama,
        ];
    }

    return successResponse($response, ['list' => $model, 'status' => 1]);
});

$app->get('/t_transfer_barang/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("inv_transfer.*,
    acc_m_user.nama as namaUser,
    acc_m_lokasi.nama as namaAsal,
    acc_m_lokasi.kode as kodeAsal,
    acc_m_lokasi2.nama as namaTujuan,
    acc_m_lokasi2.kode as kodeTujuan")
            ->from("inv_transfer")
            ->join("LEFT JOIN", "acc_m_user", "acc_m_user.id = inv_transfer.created_by")
            ->join("LEFT JOIN", "acc_m_lokasi", "acc_m_lokasi.id = inv_transfer.lokasi_asal")
            ->join("LEFT JOIN", "acc_m_lokasi acc_m_lokasi2", "acc_m_lokasi2.id = inv_transfer.lokasi_tujuan");

    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'is_deleted') {
                $db->where("is_deleted", '=', $val);
            } else if ($key == 'transfer') {
                if ($val == 'terima') {
                    $db->where("is_draft", "=", 0);
                }
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

        $models[$key]->created_at = date("d-m-Y H:i", $value->created_at);
        $models[$key]->created_by = $value->namaUser;
        $models[$key]->lokasi_asal = ['id' => $value->lokasi_asal, 'kode' => $value->kodeAsal, 'nama' => $value->namaAsal];
        $models[$key]->lokasi_tujuan = ['id' => $value->lokasi_tujuan, 'kode' => $value->kodeTujuan, 'nama' => $value->namaTujuan];
        $models[$key]->tanggal_kirim_formated = date("d-m-Y", strtotime($value->tanggal_kirim));
        $models[$key]->tanggal_kirim_terima = date("d-m-Y", strtotime($value->tanggal_terima));
    }

    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
    ]);
});
$app->post('/t_transfer_barang/save', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

//    print_r($data);
//    die;

    try {
        $data['form']['lokasi_asal'] = !empty($data['form']['lokasi_asal']['id']) ? $data['form']['lokasi_asal']['id'] : null;
        $data['form']['lokasi_tujuan'] = !empty($data['form']['lokasi_tujuan']['id']) ? $data['form']['lokasi_tujuan']['id'] : null;
//        $data['form']['m_akun_biaya_id'] = isset($data['form']['m_akun_biaya_id']) ? $data['form']['m_akun_biaya_id']['id'] : null;
//        $data['form']['m_akun_id'] = isset($data['form']['m_akun_id']) ? $data['form']['m_akun_id']['id'] : null;
        $data['form']['tanggal_kirim'] = date("Y-m-d", strtotime($data['form']['tanggal_kirim']));
        $data['form']['tanggal_terima'] = !empty($data['form']['tanggal_terima']) ? date("Y-m-d", strtotime($data['form']['tanggal_terima'])) : null;
        $data['form']['kode'] = !empty($data['form']['kode']) ? $data['form']['kode'] : generateKode($db);
        $data['form']['keterangan_kirim'] = !empty($data['form']['keterangan_kirim']) ? $data['form']['keterangan_kirim'] : null;
        $data['form']['keterangan_terima'] = !empty($data['form']['keterangan_terima']) ? $data['form']['keterangan_terima'] : null;

        $data['form']['is_draft'] = $data['form']['status'] == 'draft' ? 1 : 0;
        $data['form']['jenis'] = 'baru';

//        print_r($data['form']);
//        die;

        if (isset($data['form']["id"])) {
            $model = $db->update("inv_transfer", $data['form'], array('id' => $data['form']['id']));
        } else {
            $model = $db->insert("inv_transfer", $data['form']);
        }

        $lokasi_asal = config('LOKASI_STOK') != 0 ? config('LOKASI_STOK') : $model->lokasi_asal;
        $lokasi_tujuan = config('LOKASI_STOK') != 0 ? config('LOKASI_STOK') : $model->lokasi_tujuan;


        if (!empty($data['detail'])) {
            $db->delete('inv_transfer_det', ['inv_transfer_id' => $model->id]);

            foreach ($data['detail'] as $val) {
//                unset($val['id']);
                $barang = $val['inv_m_barang_id'];
                $harga_pokok = $barang['harga_pokok'];
                $val['inv_transfer_id'] = $model->id;
                $val['inv_m_barang_id'] = !empty($val['inv_m_barang_id']['barang_id']) ? $val['inv_m_barang_id']['barang_id'] : null;
                $val['hpp'] = pos_hpp($val['inv_m_barang_id'], $model->lokasi_asal);
                $val['harga_beli'] = $barang['harga_beli'];

                $db->insert("inv_transfer_det", $val);

                /* PENGISIAN KARTU STOK */
                if ($val['qty'] > 0 && $model->is_draft == 0) {

                    //STOK KELUAR
                    if ($model->status == 'pending') {
                        $_hpp = pos_hpp($barang['barang_id'], $lokasi_asal, null, $barang['harga_pokok'], $val['qty'], false);
                        foreach ($_hpp as $key) {
                            $jumlah = ($barang['harga_pokok'] == 'average') ? $val['qty'] : $key['jumlah'];

                            $dataa = array(
                                "kode" => $model->kode,
                                "inv_m_barang_id" => $barang['barang_id'],
                                "catatan" => 'Transfer Barang ' . $model->kode . ' ' . '(Keluar)',
                                "jumlah_keluar" => $jumlah,
                                "harga_keluar" => $val['harga'],
                                "trans_tipe" => 'inv_transfer',
                                "trans_id" => $model->id,
                                "hpp" => $key['hpp'],
                                "jenis_kas" => 'keluar',
                                "acc_m_lokasi_id" => $lokasi_asal,
                                "tanggal" => strtotime($model->tanggal_kirim),
                            );
                            $insertKartuStok = $db->insert("inv_kartu_stok", $dataa);
                        }

                        /* PENGURANGAN STOCK */
                        pos_pengurangan_stok($val['qty'], $barang['barang_id'], $lokasi_asal, $model->tanggal_kirim);
                    }


                    //STOK MASUK
                    if ($model->status == 'accepted') {
                        $val['hpp'] = pos_hpp(
                                $barang['barang_id'], $lokasi_tujuan, null, $barang['harga_pokok'], 0, false, 0, '', true
                        );
                        $val['hpp'] = !empty($val['hpp'][0]['hpp']) && $val['hpp'][0]['hpp'] != 0 ? $val['hpp'][0]['hpp'] : $barang['harga_beli'];

                        $kartu = [
                            "kode" => $model->kode,
                            "inv_m_barang_id" => $barang['barang_id'],
                            "catatan" => 'Transfer Barang ' . $model->kode . ' (Masuk)',
                            "jumlah_masuk" => $val['qty'],
                            "harga_masuk" => $val['hpp'],
                            "trans_tipe" => 'inv_transfer',
                            "trans_id" => $model->id,
                            "jenis_kas" => 'masuk',
                            "stok" => $val['qty'],
                            "acc_m_lokasi_id" => $lokasi_tujuan,
                            "tanggal" => strtotime($model->tanggal_terima),
                        ];
                        $insertKartuStok = $db->insert("inv_kartu_stok", $kartu);
                    }
                }
            }
        }
        return successResponse($response, $model);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->post('/t_transfer_barang/delete', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    try {

        $db->delete("inv_transfer", ["id" => $params['id']]);
        $db->delete("inv_transfer_det", ["inv_transfer_id" => $params['id']]);

        return successResponse($response, []);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->get('/t_transfer_barang/getDetail', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $detail = $db->select("
      inv_transfer_det.*,
      inv_m_barang.id as inv_m_barang_id,
      inv_m_barang.nama,
      inv_m_barang.kode,
      inv_m_barang.harga_beli,
      inv_m_barang.harga_pokok,
      inv_m_barang.harga_jual,
      inv_m_barang.type_barcode as jenis,
      inv_m_satuan.nama as nama_satuan
      ")
            ->from("inv_transfer_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_transfer_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->where("inv_transfer_det.inv_transfer_id", "=", $params['id'])
            ->findAll();

    $result = [];
    foreach ($detail as $key => $value) {
        $result[$key] = (array) $value;
        $stok = getStok($db, $value->inv_m_barang_id, $params['acc_m_lokasi_id']);
        $result[$key]['inv_m_barang_id'] = [
            'id' => $value->inv_m_barang_id,
            'barang_id' => $value->inv_m_barang_id,
            'kode' => $value->kode,
            'nama' => $value->nama,
            'nama_satuan' => $value->nama_satuan,
            'harga_beli' => $value->harga_beli,
            'harga_pokok' => $value->harga_pokok,
            'harga_jual' => $value->harga_jual,
            'jenis' => $value->jenis,
            'stok' => $stok,
        ];
        $result[$key]['harga'] = $value->harga_beli;
        $result[$key]['satuan'] = $value->nama_satuan;
    }

    return successResponse($response, ['detail' => $result]);
});
