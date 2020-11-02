<?php

$app->get('/t_po_pembelian/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("inv_po_pembelian.*, m_user.nama as pembuat")
            ->from("inv_po_pembelian")
            ->join("LEFT JOIN", "m_user", "m_user.id = inv_po_pembelian.created_by")
            ->join("LEFT JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_po_pembelian.acc_m_kontak_id");

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
        $models[$key]->grand_total = $value->total;
        $models[$key]->acc_m_lokasi_id = isset($allLokasi[$value->acc_m_lokasi_id]) ? $allLokasi[$value->acc_m_lokasi_id] : [];

        $supp = $db->find("SELECT * FROM acc_m_kontak WHERE id = {$value->acc_m_kontak_id}");
        $models[$key]->acc_m_kontak_id = !empty($supp) ? $supp : [];
        $models[$key]->stok = 0;
    }

    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
    ]);
});

$app->post('/t_po_pembelian/trash', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $model = $db->update("inv_m_barang", ['is_deleted' => $data['is_deleted']], array('id' => $data['id']));
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

$app->post('/t_po_pembelian/approve', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $model = $db->update("inv_po_pembelian", ['status' => $params['status']], array('id' => $params['id']));
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal mengapprove data']);
    }
});

function generateKode($db) {
    $cekKode = $db->select('*')
            ->from("inv_po_pembelian")
            ->where("kode", "!=", "")
            ->orderBy('kode DESC')
            ->find();

    if ($cekKode) {
        $kode_terakhir = $cekKode->kode;
    } else {
        $kode_terakhir = 0;
    }

    $kodeTerakhir = (substr($kode_terakhir, -4) + 1);
    $kode = substr('0000' . $kodeTerakhir, strlen($kodeTerakhir));
    $kode = "POB/" . date("y") . "/" . $kode;

    return $kode;
}

$app->get('/t_po_pembelian/getKode', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    return successResponse($response, ['kode' => generateKode($db)]);
});

function validasi($data, $custom = array()) {
    $validasi = array(
        'acc_m_lokasi_id' => 'required',
        'acc_m_kontak_id' => 'required',
        'tanggal' => 'required',
            // 'no_invoice'           => 'required',
            // 'm_akun_id'            => 'required',
    );

    GUMP::set_field_name("acc_m_kontak_id", "Supplier");
    GUMP::set_field_name("acc_m_lokasi", "Lokasi");

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->post('/t_po_pembelian/save', function ($request, $response) {

    $params = $request->getParams();
    $db = $this->db;
    if (validasi($params['form']) !== true) {
        return unprocessResponse($response, $validasi);
    }
    try {
        $params['form']['acc_m_lokasi_id'] = !empty($params['form']['acc_m_lokasi_id']['id']) ? $params['form']['acc_m_lokasi_id']['id'] : NULL;
        $params['form']['acc_m_kontak_id'] = !empty($params['form']['acc_m_kontak_id']['id']) ? $params['form']['acc_m_kontak_id']['id'] : NULL;

        $params['form']['tanggal'] = strtotime($params['form']['tanggal']);
        $params['form']['total'] = !empty($params['form']['grand_total']) ? $params['form']['grand_total'] : 0;

        if (isset($params['form']["id"])) {
            $model = $db->update("inv_po_pembelian", $params['form'], array('id' => $params['form']['id']));
        } else {
            $params['form']['kode'] = generateKode($db);
            $params['form']['status'] = 'pending';
            $model = $db->insert("inv_po_pembelian", $params['form']);
        }

        if (!empty($params['detail'])) {
            $deleteLama = $db->delete('inv_po_pembelian_det', ['inv_po_pembelian_id' => $model->id]);

            foreach ($params['detail'] as $val) {
                if (!empty($val['inv_m_barang_id']['id'])) {
                    /** SIMPAN DETAIL PEMBELIAN */
                    $val['inv_po_pembelian_id'] = $model->id;
                    $val['inv_m_barang_id'] = $val['inv_m_barang_id']['id'];
                    $detail = $db->insert("inv_po_pembelian_det", $val);
                }
            }
        }

        return successResponse($response, $model);
    } catch (Exception $e) {
        return unprocessResponse($response, ['Terjadi kesalahan pada server! ' . $e->getMessage()]);
    }
});

$app->get('/t_po_pembelian/getDetail', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $detail = $db->select("
      inv_po_pembelian_det.*,
      inv_m_barang.id as inv_m_barang_id,
      inv_m_barang.nama,
      inv_m_barang.kode,
      inv_m_satuan.nama as nama_satuan
      ")
            ->from("inv_po_pembelian_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_po_pembelian_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->where("inv_po_pembelian_det.inv_po_pembelian_id", "=", $params['id'])
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
        ];
    }

    return successResponse($response, ['detail' => $result]);
});

$app->get('/t_po_pembelian/getAll', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select("inv_po_pembelian.*")
            ->from("inv_po_pembelian")
            ->join('LEFT JOIN', "inv_pembelian", "inv_pembelian.inv_po_pembelian_id = inv_po_pembelian.id")
            ->where("inv_po_pembelian.status", "=", 'approved')
            ->customWhere("inv_pembelian.id IS NULL", "AND")
            ->findAll();

    $models = $db->findAll();

    return successResponse($response, $models);
});
