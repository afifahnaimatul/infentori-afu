<?php

function validasi($data, $custom = array()) {
    $validasi = array(
        'nama' => 'required',
        'kode' => 'required',
        'type' => 'required',
    );
    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->get('/m_pelabuhan/kode', function ($request, $response) {
    return generateNoTransaksi("jasa", 0);
});
$app->get('/m_pelabuhan/getKontak', function ($request, $response) {
    $db = $this->db;
    $params = $request->getParams();
    $db->select("*")
            ->from("acc_m_kontak")
            ->orderBy("acc_m_kontak.nama")
            ->where("is_deleted", "=", 0)
            ->customWhere("type IN ('supplier', 'angkutan', 'pelabuhan', 'dokumen', 'gudang', 'lain-lain')", "AND");
    if (isset($params['nama']) && !empty($params['nama'])) {
        $db->customWhere("nama LIKE '%" . $params['nama'] . "%'", "AND");
    }
    $models = $db->findAll();
    foreach ($models as $key => $val) {
        $val->type = ucfirst($val->type);
        $val->acc_m_akun_id = !empty($val->acc_m_akun_id) ? $db->find("SELECT id, kode, nama FROM acc_m_akun WHERE id = " . $val->acc_m_akun_id) : [];
    }
    return successResponse($response, [
        'list' => $models,
    ]);
});

$app->get('/m_pelabuhan/index', function ($request, $response) {
    $params = $request->getParams();
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;
    $db = $this->db;
    $db->select("*")
            ->from("acc_m_kontak")
            ->customWhere("type IN ('angkutan', 'pelabuhan', 'pengurusan dokumen', 'pengelolaan gudang', 'lain-lain')")
            ->orderBy('acc_m_kontak.nama');
    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'is_deleted') {
                $db->where("is_deleted", '=', $val);
            } elseif ($key == "jenis") {
                $db->where("jenis", '=', $val);
            } else {
                $db->where($key, 'like', $val);
            }
        }
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
    // $models = $db->log();

    $totalItem = $db->count();
    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
    ]);
});
$app->post('/m_pelabuhan/save', function ($request, $response) {
    $params = $request->getParams();
    $sql = $this->db;
    /*
     * generate kode
     */
    $kode = generateNoTransaksi("jasa", 0);
    $params["nama"] = isset($params["nama"]) ? $params["nama"] : "";
    $validasi = validasi($params);
    if ($validasi === true) {
//        $params['type'] = "pelabuhan";
        if (isset($params["id"])) {
            if (isset($params["kode"]) && !empty($params["kode"])) {
                $params["kode"] = $params["kode"];
            } else {
                $params["kode"] = $kode;
            }
            $model = $sql->update("acc_m_kontak", $params, array('id' => $params['id']));
        } else {
            $params["kode"] = $kode;
            $model = $sql->insert("acc_m_kontak", $params);
        }
        if ($model) {
            return successResponse($response, $model);
        } else {
            return unprocessResponse($response, ['Data Gagal Di Simpan']);
        }
    } else {
        return unprocessResponse($response, $validasi);
    }
});
$app->post('/m_pelabuhan/trash', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;
    $model = $db->update("acc_m_kontak", $data, array('id' => $data['id']));
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});
$app->post('/m_pelabuhan/delete', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;
    $delete = $db->delete('acc_m_kontak', array('id' => $data['id']));
    if ($delete) {
        return successResponse($response, ['data berhasil dihapus']);
    } else {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});
