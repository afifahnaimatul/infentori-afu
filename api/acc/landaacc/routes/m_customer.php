<?php

function validasi($data, $custom = array()) {
    $validasi = array(
        'nama' => 'required',
        'kode' => 'required'
    );
    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->get('/acc/m_customer/kode', function ($request, $response) {
    $params = $request->getParams();
    return isset($params['project']) && !empty($params['project']) && $params['project'] == "afu" ? generateNoTransaksi("afu_customer", 0) : generateNoTransaksi("customer", 0);
});
$app->get('/acc/m_customer/getKontak', function ($request, $response) {
    $db = $this->db;
    $params = $request->getParams();
    $db->select("*")
            ->from("acc_m_kontak")
            ->orderBy("acc_m_kontak.nama")
            ->where("is_deleted", "=", 0);
    if (isset($params['nama']) && !empty($params['nama'])) {
        $db->customWhere("nama LIKE '%" . $params['nama'] . "%'", "AND");
    }
    $models = $db->findAll();
    foreach ($models as $key => $val) {
        $val->type = ucfirst($val->type);
    }
    return successResponse($response, [
        'list' => $models,
    ]);
});
$app->get('/acc/m_customer/getKaryawan', function ($request, $response) {
    $db = $this->db;
    $params = $request->getParams();
    $models = $db->select("*")
            ->from("karyawan")
            ->where("is_deleted", "=", 0)
            ->andWhere("status", "=", "Aktif")
            ->findAll();

    return successResponse($response, [
        'list' => $models,
    ]);
});
$app->get('/acc/m_customer/getCustomer', function ($request, $response) {
    $db = $this->db;
    $params = $request->getParams();

    // $db->select("*")->from("acc_m_kontak");
    // $db->orderBy("acc_m_kontak.nama")
    // ->where("is_deleted", "=", 0)
    // ->andWhere("type", "=", "customer")
    // ->andWhere("nama", "!=", "");
    //
    // if (isset($params['nama']) && !empty($params['nama'])) {
    //   $db->customWhere("nama LIKE '%" . $params['nama'] . "%' OR kode LIKE '%" . $params['nama'] . "%'", "AND");
    // }
    //
    // if (isset($params['lokasi']) && !empty($params['lokasi'])) {
    //   $db->customWhere("acc_m_lokasi_id = '" . $params['lokasi'] . "'", "AND");
    // }
    //
    // $models = $db->limit(20)->findAll();

    $query = "SELECT * FROM acc_m_kontak
      WHERE is_deleted = 0
      AND type = 'customer'
      AND nama != ''";

    if (isset($params['nama']) && !empty($params['nama'])) {
      $query .= "AND (nama LIKE '%" . $params['nama'] . "%' OR kode LIKE '%" . $params['nama'] . "%') ";
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
      $query .= "AND (acc_m_lokasi_id = '". $params['lokasi'] ."' OR acc_m_kontak.is_ppn = 1)";
    }

    $query .= " ORDER BY acc_m_kontak.nama LIMIT 20";

    $models = $db->findAll($query);
    
    //cek kolom, untuk di afu
    $cek_column = $db->select("*")->from("INFORMATION_SCHEMA.COLUMNS")->where("TABLE_NAME", "=", "acc_m_kontak")->where("COLUMN_NAME", "=", "acc_m_akun_id")->find();

    if (!empty($cek_column)) {
        foreach ($models as $key => $value) {
            $value->acc_m_akun_id = !empty($value->acc_m_akun_id) ? $db->find("SELECT id, kode, nama FROM acc_m_akun WHERE id = " . $value->acc_m_akun_id) : [];
        }
    }
    //end

    return successResponse($response, [
        'list' => $models,
    ]);
});
$app->get('/acc/m_customer/index', function ($request, $response) {
    $params = $request->getParams();
    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;
    $db = $this->db;
    $db->select("*")
            ->from("acc_m_kontak")
            ->orderBy('acc_m_kontak.nama');
    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'is_deleted') {
                $db->where("is_deleted", '=', $val);
            } elseif ($key == "jenis") {
                $db->where("jenis", '=', $val);
            } elseif ($key == "acc_m_kontak.acc_m_lokasi_id") {
                $db->where($key, '=', $val);
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
    $totalItem = $db->count();

    //cek kolom, untuk di afu
    $cek_column = $db->select("*")->from("INFORMATION_SCHEMA.COLUMNS")->where("TABLE_NAME", "=", "acc_m_kontak")->where("COLUMN_NAME", "=", "acc_m_lokasi_id")->find();
    $cek_column2 = $db->select("*")->from("INFORMATION_SCHEMA.COLUMNS")->where("TABLE_NAME", "=", "acc_m_kontak")->where("COLUMN_NAME", "=", "acc_m_akun_id")->find();

    if (!empty($cek_column) && !empty($cek_column2)) {
        foreach ($models as $key => $value) {
            $value->acc_m_lokasi_id = !empty($value->acc_m_lokasi_id) ? $db->find("SELECT id, kode, nama FROM acc_m_lokasi WHERE id = " . $value->acc_m_lokasi_id) : [];
            $value->acc_m_akun_id = !empty($value->acc_m_akun_id) ? $db->find("SELECT id, kode, nama FROM acc_m_akun WHERE id = " . $value->acc_m_akun_id) : [];
        }
    }
    //end


    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
    ]);
});
$app->post('/acc/m_customer/save', function ($request, $response) {
    $params = $request->getParams();
    $sql = $this->db;
    /*
     * generate kode
     */
    $kode = isset($params['project']) && !empty($params['project']) && $params['project'] == "afu" ? generateNoTransaksi("afu_customer", 0) : generateNoTransaksi("customer", 0);
    $kode = isset($params['acc_m_akun_id']) && !empty($params['acc_m_akun_id']) ? $params['acc_m_akun_id']['kode'] : $kode;
    $params['acc_m_akun_id'] = isset($params['acc_m_akun_id']) && !empty($params['acc_m_akun_id']) ? $params['acc_m_akun_id']['id'] : NULL;

    $params["nama"] = isset($params["nama"]) ? $params["nama"] : "";
    $params["acc_m_lokasi_id"] = isset($params["acc_m_lokasi_id"]) && !empty($params['acc_m_lokasi_id']) ? $params["acc_m_lokasi_id"]['id'] : null;
    $validasi = validasi($params);
    if ($validasi === true) {
        $params['type'] = "customer";
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
$app->post('/acc/m_customer/trash', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;
    $model = $db->update("acc_m_kontak", $data, array('id' => $data['id']));
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});
$app->post('/acc/m_customer/delete', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;
    $delete = $db->delete('acc_m_kontak', array('id' => $data['id']));
    if ($delete) {
        return successResponse($response, ['data berhasil dihapus']);
    } else {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});
