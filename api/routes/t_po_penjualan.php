<?php

$app->get('/t_po_penjualan/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("inv_po_penjualan.*, m_user.nama as pembuat")
            ->from("inv_po_penjualan")
            ->join("LEFT JOIN", "m_user", "m_user.id = inv_po_penjualan.created_by")
            ->join("LEFT JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_po_penjualan.acc_m_kontak_id");

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
        $models[$key]->acc_m_lokasi_id = isset($allLokasi[$value->acc_m_lokasi_id]) ? $allLokasi[$value->acc_m_lokasi_id] : [];

        if (!empty($value->acc_m_kontak_id)) {
            $supp = $db->find("SELECT * FROM acc_m_kontak WHERE id = {$value->acc_m_kontak_id}");
        }

        $models[$key]->acc_m_kontak_id = !empty($supp) ? $supp : [];
        $models[$key]->stok = 0;
    }

    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
    ]);
});

$app->get('/t_po_penjualan/customer', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select("*")
            ->from("acc_m_kontak")
            ->where("nama", "LIKE", $params['nama'])
            ->limit(20);

    $models = $db->findAll();

    return successResponse($response, $models);
});

function generateKode($db) {
    $cekKode = $db->select('inv_po_penjualan.kode')
            ->from("inv_po_penjualan")
            ->where("kode", "!=", "")
            ->orderBy('kode DESC')
            ->find();

    if ($cekKode) {
        $kode_terakhir = $cekKode->kode;
    } else {
        $kode_terakhir = 0;
    }

    $kodePenjualan = (substr($kode_terakhir, -5) + 1);
    $kode = substr('00000' . $kodePenjualan, strlen($kodePenjualan));
    $kode = 'POJ' . date("y") . "/" . $kode;

    return $kode;
}

function validasiPenjualan($data, $custom = array()) {
    $validasi = array(
        'acc_m_lokasi_id' => 'required',
        'acc_m_kontak_id' => 'required',
        'tanggal' => 'required',
    );

    GUMP::set_field_name("acc_m_kontak_id", "Supplier");
    GUMP::set_field_name("acc_m_lokasi", "Lokasi");
    GUMP::set_field_name("jatuh_tempo", "Tanggal Jatuh Tempo");

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->post('/t_po_penjualan/save', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $keterangan_trans = "";
    if (empty($data['form']['acc_m_kontak_id'])) {
        $data['form']['acc_m_kontak_id'] = (array) $customer;
    }

    $data['form']['acc_m_lokasi_id'] = !empty($data['form']['acc_m_lokasi_id']['id']) ? $data['form']['acc_m_lokasi_id']['id'] : NULL;
    $data['form']['tanggal'] = strtotime(date('d-m-Y', strtotime($data['form']['tanggal'])));

    $validasi = validasiPenjualan($data['form']);

    if ($validasi !== true) {
        return unprocessResponse($response, $validasi);
    }

    $data['form']['acc_m_kontak_id'] = (isset($data['form']['acc_m_kontak_id']['id'])) ? $data['form']['acc_m_kontak_id']['id'] : null;
    $data['form']['total'] = !empty($data['form']['grand_total']) ? $data['form']['grand_total'] : 0;

    if (isset($data['form']["id"])) {
        $model = $db->update("inv_po_penjualan", $data['form'], array('id' => $data['form']['id']));
        $deDetail = $db->delete('inv_po_penjualan_det', ['inv_po_penjualan_id' => $model->id]);
    } else {
        $data['form']['status'] = 'pending';
        $data['form']['kode'] = generateKode($db);
        $model = $db->insert("inv_po_penjualan", $data['form']);
    }

    $model->type = $data['form']['type'];

    if (!empty($data['detail'])) {
        foreach ($data['detail'] as $val) {
            $val['inv_po_penjualan_id'] = $model->id;
            $val['inv_m_barang_id'] = !empty($val['inv_m_barang_id']['id']) ? $val['inv_m_barang_id']['id'] : NULL;
            $detail = $db->insert("inv_po_penjualan_det", $val);
        }
    }
    return successResponse($response, $model);
});

$app->post('/t_po_penjualan/approval', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $model = $db->update("inv_po_penjualan", ['status' => $data['setujui']], array('id' => $data['id'])
    );

    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

$app->get('/t_po_penjualan/getDetail', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;
    // pd($params);
    $detail = $db->select("
      inv_po_penjualan_det.*,
      inv_m_barang.id as inv_m_barang_id,
      inv_m_barang.nama,
      inv_m_barang.kode,
      inv_m_satuan.nama as nama_satuan
      ")
            ->from("inv_po_penjualan_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_po_penjualan_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->where("inv_po_penjualan_det.inv_po_penjualan_id", "=", $params['id'])
            ->findAll();

    $result = [];
    foreach ($detail as $key => $value) {
        $result[$key] = (array) $value;
        // $stok = getStok($db, $value->inv_m_barang_id, $params['acc_m_lokasi_id']);
        $result[$key]['inv_m_barang_id'] = [
            'id' => $value->inv_m_barang_id,
            'kode' => $value->kode,
            'nama' => $value->nama,
            'nama_satuan' => $value->nama_satuan,
                // 'stok'        => $stok,
        ];

        if ($value->diskon == 0) {
            $result[$key]['diskon_persen'] = 0;
        } else {
            $result[$key]['diskon_persen'] = round(($value->diskon / $value->harga) * 100, 1);
        }

        $subharga = ($value->jumlah * $value->harga) - ($value->jumlah * $value->diskon);
        $result[$key]['subtotal'] = $subharga;
//        $result[$key]['stok'] = $stok;
    }

    return successResponse($response, ['detail' => $result]);
});

$app->get('/t_po_penjualan/getAll', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select("inv_po_penjualan.*")
            ->from("inv_po_penjualan")
            ->join('LEFT JOIN', "inv_penjualan", "inv_penjualan.inv_po_penjualan_id = inv_po_penjualan.id")
            ->where("inv_po_penjualan.status", "=", 'approved')
            ->customWhere("inv_penjualan.id IS NULL", "AND")
            ->findAll();

    $models = $db->findAll();

    return successResponse($response, $models);
});
