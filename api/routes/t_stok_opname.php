<?php

$app->get('/t_stok_opname/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("
      inv_stok_opname.*,
      acc_m_lokasi.id lokasi_id,
      acc_m_lokasi.kode lokasi_kode,
      acc_m_lokasi.nama lokasi_nama,
      m_user.nama as pembuat,
      inv_m_kategori.nama as kategori
      ")
            ->from("inv_stok_opname")
            ->JOIN("JOIN", "acc_m_lokasi", "acc_m_lokasi.id = inv_stok_opname.acc_m_lokasi_id")
            ->join("LEFT JOIN", "m_user", "m_user.id = inv_stok_opname.created_by")
            ->join("LEFT JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_stok_opname.inv_m_kategori_id");

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
        $models[$key]->acc_m_lokasi_id = [
            'id' => $value->lokasi_id,
            'kode' => $value->lokasi_kode,
            'nama' => $value->lokasi_nama,
        ];
        $models[$key]->inv_m_kategori_id = [
            'id' => $value->inv_m_kategori_id,
            'nama' => $value->kategori
        ];
    }

    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
    ]);
});

function generateKode($db) {
    $cekKode = $db->select('*')
            ->from("inv_stok_opname")
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

$app->get('/t_stok_opname/getKode', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    return successResponse($response, ['kode' => generateKode($db)]);
});

$app->get('/t_stok_opname/getDetail', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $detail = $db->select("
      inv_stok_opname_det.*,
      inv_stok_opname_det.stok_real as stock_real,
      inv_stok_opname_det.stok_app as stock_app,
      inv_m_barang.nama,
      inv_m_barang.kode,
      inv_m_barang.harga_jual,
      inv_m_barang.harga_beli,
      inv_m_barang.id as barang_id,
      inv_m_satuan.nama as satuan,
      inv_m_satuan.id as satuan_id,
      inv_m_kategori.nama as kategori
      ")
            ->from("inv_stok_opname_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_stok_opname_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->join('LEFT JOIN', "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->where("inv_stok_opname_det.inv_stok_opname_id", "=", $params['id'])
            ->findAll();

    return successResponse($response, ['detail' => $detail]);
});

$app->get('/t_stok_opname/getJurnal', function ($request, $response) {
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
            ->where("acc_trans_detail.reff_type", "=", 'inv_stok_opname')
            ->findAll();

    $result = [];
    foreach ($trans_detail as $key => $value) {

        $value->akun = ['id' => $value->m_akun_id, 'nama' => $value->namaAkun, 'kode' => $value->kodeAkun];

        $result[$key] = (array) $value;
    }

    return successResponse($response, ['list' => $result]);
});

function validasi($data, $custom = array()) {
    $validasi = array(
        'acc_m_lokasi_id' => 'required',
//        'inv_m_kategori_id' => 'required',
        'tanggal' => 'required',
//        'jatuh_tempo' => 'required',
            // 'no_invoice' => 'required',
            // 'm_akun_id'             => 'required',
    );

    GUMP::set_field_name("acc_m_kontak_id", "Supplier");
    GUMP::set_field_name("acc_m_lokasi", "Lokasi");
    GUMP::set_field_name("jatuh_tempo", "Tanggal Jatuh Tempo");
    GUMP::set_field_name("m_akun_id", "Akun Keluar");

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->post('/t_stok_opname/save', function ($request, $response) {

    $params = $request->getParams();
    $db = $this->db;

//    print_r($params);
//    die;

    $validasi = validasi($params['form']);
    if ($validasi !== true) {
        return unprocessResponse($response, $validasi);
    }
    try {

        $kode = generateNoTransaksi("stok_opname", $params['form']['acc_m_lokasi_id']['kode']);

        $params['form']['acc_m_lokasi_id'] = !empty($params['form']['acc_m_lokasi_id']['id']) ? $params['form']['acc_m_lokasi_id']['id'] : NULL;
        $params['form']['inv_m_kategori_id'] = !empty($params['form']['inv_m_kategori_id']['id']) ? $params['form']['inv_m_kategori_id']['id'] : NULL;

        $params['form']['tanggal'] = date("Y-m-d", strtotime($params['form']['tanggal']));
        $params['form']['total'] = !empty($params['form']['total']) ? $params['form']['total'] : 0;

//        print_r($params);die;

        if (isset($params['form']["id"])) {
            $model = $db->update("inv_stok_opname", $params['form'], array('id' => $params['form']['id']));
            $deleteLama = $db->delete('inv_stok_opname_det', ['inv_stok_opname_id' => $model->id]);
        } else {
            $params['form']['kode'] = $kode;
            $model = $db->insert("inv_stok_opname", $params['form']);
        }

        // Detail Stok Opname
        if (!empty($params['detail'])) {

            foreach ($params['detail'] as $key => $value) {

                $value['inv_stok_opname_id'] = $model->id;
                $value['inv_m_barang_id'] = $value['barang_id'];
                $value['inv_m_satuan_id'] = $value['satuan_id'];
                $value['stok_app'] = $value['stock_app'];
                $value['stok_real'] = $value['stock_real'];
                $detail = $db->insert("inv_stok_opname_det", $value);

                if ($model->is_draft != 1) {
                    if ($value['stock_app'] > 0 || $value['stock_real'] > 0) {
                        if ($value['stock_real'] > $value['stock_app']) {
                            /** Stok Masuk */
                            $kartu = [
                                "kode"              => $model->kode,
                                "acc_m_lokasi_id"   => $model->acc_m_lokasi_id,
                                "inv_m_barang_id"   => $value['inv_m_barang_id'],
                                "catatan"           => 'Penyesuaian Persediaan Masuk',
                                "jumlah_masuk"      => abs($value['selisih']),
                                "harga_masuk"       => $value['hpp'],
                                "jenis_kas"         => 'masuk',
                                "stok"              => abs($value['selisih']),
                                "trans_id"          => $model->id,
                                "trans_tipe"        => 'inv_stok_opname',
                                "tanggal"           => strtotime($model->tanggal),
                            ];
                            $insertKartuStok = $db->insert("inv_kartu_stok", $kartu);
                        } else {
                            /** Stok Keluar */
                            $_hpp = pos_hpp(
                                    $value['inv_m_barang_id'], $model->acc_m_lokasi_id, null, $value['harga_pokok'], abs($value['selisih']), true, $value['hpp'], 'beli'
                            );

                            foreach ($_hpp as $k => $v) {
                                $kartu = [
                                    "kode"              => $model->kode,
                                    "inv_m_barang_id"   => $value['inv_m_barang_id'],
                                    "catatan"           => 'Penyesuaian Persediaan Keluar',
                                    "jumlah_keluar"     => $v['jumlah'],
                                    "harga_keluar"      => $v['hpp'],
                                    "hpp"               => $v['hpp'],
                                    "jenis_kas"         => 'keluar',
                                    "trans_id"          => $model->id,
                                    "trans_tipe"        => 'inv_stok_opname_id',
                                    "acc_m_lokasi_id"   => $model->acc_m_lokasi_id,
                                    "tanggal"           => strtotime($model->tanggal),
                                ];
                                $insertKartuStok = $db->insert("inv_kartu_stok", $kartu);

                                /** Pengurangan Stok */
                                pos_pengurangan_stok(
                                        abs($value['selisih']), $value['inv_m_barang_id'], $model->acc_m_lokasi_id, $kartu['tanggal']
                                );
                            }
                        }
                    }
                }
            }
        }

        // Jurnal Akuntansi
        $transDetail = [];
        if (!empty($params['listJurnal']) && $model->is_draft != 1) {
            $db->delete('acc_trans_detail', ['reff_id' => $model->id, 'reff_type' => 'inv_stok_opname']);

            foreach ($params['listJurnal'] as $key => $value) {
                $transDetail[$key]['m_lokasi_id'] = $model->acc_m_lokasi_id;
                $transDetail[$key]['m_akun_id'] = $value['akun']['id'];
//                $transDetail[$key]['m_kontak_id'] = $model->acc_m_kontak_id;
                $transDetail[$key]['debit'] = $value['debit'];
                $transDetail[$key]['kredit'] = $value['kredit'];
                $transDetail[$key]['kode'] = $model->kode;
                $transDetail[$key]['keterangan'] = $value['keterangan'];
                $transDetail[$key]['reff_id'] = $model->id;
                $transDetail[$key]['tanggal'] = date("Y-m-d", strtotime($model->tanggal));
                $transDetail[$key]['reff_type'] = "inv_stok_opname_id";
                $transDetail[$key]['m_lokasi_jurnal_id'] = $model->acc_m_lokasi_id;
            }
            insertTransDetail($transDetail);
        }
        // Jurnal Akuntansi - End

        return successResponse($response, $model);
    } catch (Exception $e) {
        return unprocessResponse($response, ['Terjadi kesalahan pada server! ' . $e->getMessage()]);
    }
});

$app->post('/t_stok_opname/delete', function ($request, $response) {

    $params = $request->getParams();
    $db = $this->db;

    try {
        $db->delete('inv_stok_opname', ['id' => $params['id']]);
        $db->delete('inv_stok_opname', ['inv_stok_opname_id' => $params['id']]);
        return successResponse($response, []);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->post('/t_stok_opname/unpost', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    try {
        $delete = $db->delete('inv_kartu_stok', ['trans_id' => $params['id'], 'trans_tipe' => 'inv_stok_opname_id']);
        $update = $db->update('inv_stok_opname', ['is_unpost' => 1], ['id' => $params['id']]);

        return successResponse($response, []);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

/** Ambil barang percabang dan kategori */
// $app->get('/t_stok_opname/barang/{idcabang}/{idkategori}/{idbarang}/{tanggal}', function ($request, $response) {
$app->post('/t_stok_opname/barang/', function ($request, $response) {
  $params = $request->getParams();
  $db = $this->db;

    // $idkategori = $request->getAttribute('idkategori');
    // $idcabang = $request->getAttribute('idcabang');
    // $idbarang = $request->getAttribute('idbarang');
    // $tanggal  = $request->getAttribute('tanggal');

    $idkategori = $params['idkategori'];
    $idcabang = $params['idcabang'];
    $idbarang = $params['idbarang'];
    $tanggal  = $params['tanggal'];
// pd($params);
    $db->select("
            inv_m_barang.nama,
            inv_m_barang.harga_beli,
            inv_m_barang.harga_jual,
            inv_m_barang.harga_pokok,
            inv_m_barang.id as barang_id,
            inv_m_satuan.id as satuan_id,
            inv_m_barang.kode,
            inv_m_kategori.nama as namakategori,
            inv_m_satuan.nama as namasatuan,
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
            ->from("inv_m_barang")
            ->join('left join', 'inv_m_kategori', 'inv_m_kategori.id = inv_m_barang.inv_m_kategori_id')
            ->join("left join", "acc_m_akun akun_pembelian", "akun_pembelian.id= inv_m_kategori.akun_pembelian_id")
            ->join("left join", "acc_m_akun akun_penjualan", "akun_penjualan.id = inv_m_kategori.akun_penjualan_id")
            ->join("left join", "acc_m_akun akun_hpp", "akun_hpp.id= inv_m_kategori.akun_hpp_id")
            ->join("left join", "acc_m_akun akun_persediaan_brg", "akun_persediaan_brg.id= inv_m_kategori.akun_persediaan_brg_id")
            ->join('left join', 'inv_m_satuan', 'inv_m_satuan.id = inv_m_barang.inv_m_satuan_id')
            ->where("inv_m_barang.is_deleted", "=", 0);

    if ($idbarang > 0) {
        $db->AndWhere("inv_m_barang.id", "=", $idbarang);
    } else if ($idkategori > 0) {
        $db->AndWhere("inv_m_barang.inv_m_kategori_id", "=", $idkategori);
    }

    $model = $db->findAll();

    $data = [];
    if (!empty($model)) {
        foreach ($model as $key => $value) {
            $data[$key]['akun_penjualan_id'] = $value->akun_penjualan_id;
            $data[$key]['akun_persediaan_brg_id'] = $value->akun_persediaan_brg_id;
            $data[$key]['satuan_id'] = $value->satuan_id;
            $data[$key]['barang_id'] = $value->barang_id;
            $data[$key]['kode'] = $value->kode;
            $data[$key]['harga_beli'] = $value->harga_beli;
            $data[$key]['harga_jual'] = $value->harga_jual;
            $data[$key]['harga_pokok'] = $value->harga_pokok;
            $data[$key]['nama'] = $value->nama;
            $data[$key]['satuan'] = $value->namasatuan;
            $data[$key]['kategori'] = $value->namakategori;

            // $stok = pos_stok($value->id, $params['acc_m_lokasi_id'], false, $tanggal_start, $tanggal_end);
            $tanggal_start  = '2019-01-01';
            $tanggal_end     = date("Y-m-d", strtotime($tanggal) );
            $data[$key]['stock_app'] = getStok($db, $value->barang_id, $idcabang, false, $tanggal_start, $tanggal_end);
            // $data[$key]['stock_app2'] = getStok($value->barang_id, $idcabang);
            // pd([
            //   $data[$key]['stock_app'],
            //   // $data[$key]['stock_app2'],
            //   $tanggal_start,
            //   $tanggal_end
            // ]);

            $data[$key]['stock_real'] = $data[$key]['stock_app'];

            // $data[$key]['hpp'] = pos_hpp(
            //         $value->barang_id, $idcabang, null, $value->harga_pokok, 0, false, 0, '', true
            // );
            // $data[$key]['hpp'] = !empty($data[$key]['hpp'][0]['hpp']) && $data[$key]['hpp'][0]['hpp'] != 0 ? $data[$key]['hpp'][0]['hpp'] : $value->harga_beli;
            $data[$key]['hpp'] = 0;
            $selisih = ($data[$key]['stock_real'] - $data[$key]['stock_app']);

            $data[$key]['selisih'] = $selisih;
            $data[$key]['required'] = ($selisih != 0) ? true : false;
            $data[$key]['nilai_penyesuaian'] = abs($data[$key]['selisih'] * $data[$key]['hpp']);

            $data[$key]['akun_hpp_id'] = [
                'id' => $value->akun_hpp_id,
                'kode' => $value->kodeHpp,
                'nama' => $value->namaHpp,
            ];
            $data[$key]['akun_persediaan_id'] = [
                'id' => $value->akun_persediaan_brg_id,
                'kode' => $value->kodePersediaan,
                'nama' => $value->namaPersediaan,
            ];
        }
    }

    return successResponse($response, ['data' => $data]);
});
