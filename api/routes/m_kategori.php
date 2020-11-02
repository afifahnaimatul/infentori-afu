<?php

function validasi($data, $custom = array()) {
    $validasi = array(
//        'kode' => 'required',
        'nama' => 'required',
    );
    $cek = validate($data, $validasi, $custom);
    GUMP::set_field_name("akun_persediaan_brg_id", "Akun Persediaan");
    GUMP::set_field_name("akun_hpp_id", "Akun HPP");
    GUMP::set_field_name("akun_penjualan_id", "Akun Penjualan");
    return $cek;
}

$app->get('/m_kategori/getAll', function ($request, $response) {
    $db = $this->db;
    $db->select("*")
            ->from("acc_m_lokasi")
            ->orderBy('acc_m_lokasi.kode_parent')
            ->where("is_deleted", "=", 0);
    $models = $db->findAll();
    $arr = getChildFlat($models, 0);
    foreach ($arr as $key => $val) {
        $spasi = ($val->level == 0) ? '' : str_repeat("---", $val->level);
        $val->nama_lengkap = $spasi . $val->kode . ' - ' . $val->nama;
    }
    return successResponse($response, [
        'list' => $arr
    ]);
});
$app->get('/m_kategori/getKategori', function ($request, $response) {
    $db = $this->db;
    $db->select("*")
            ->from("inv_m_kategori")
//            ->orderBy('inv_m_kategori.kode_parent')
            ->where("is_deleted", "=", 0);

    $models = $db->findAll();
    $arr = getChildFlat($models, 0);
//    $arr = [];
    foreach ($models as $key => $val) {
        $spasi = '';
        $spasi = ($val->level == 0) ? '' : str_repeat("---", $val->level);
        $val->nama_lengkap = $spasi . $val->kode . ' - ' . $val->nama;
        $arr[$val->id] = $val;
    }
    return successResponse($response, [
        'list' => $models
    ]);
});
$app->get('/m_kategori/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;
    $db->select("
      inv_m_kategori.*,
      inv_m_kategori.nama nama_lengkap,
      induk.nama as namaInduk,
      induk.level as level_induk,
      akun_persediaan.kode as kodePersediaan,
      akun_persediaan.nama as namaPersediaan,
      akun_penjualan.kode as kodePenjualan,
      akun_penjualan.nama as namaPenjualan,
      akun_hpp.kode as kodeHpp,
      akun_hpp.nama as namaHpp
      ")
            ->from("inv_m_kategori")
            ->join("left join", "inv_m_kategori induk", "induk.id = inv_m_kategori.parent_id")
            ->join("left join", "inv_m_barang", "inv_m_barang.inv_m_kategori_id = inv_m_kategori.id")
            ->join("left join", "acc_m_akun akun_persediaan", "akun_persediaan.id= inv_m_kategori.akun_persediaan_brg_id")
            ->join("left join", "acc_m_akun akun_penjualan", "akun_penjualan.id = inv_m_kategori.akun_penjualan_id")
            ->join("left join", "acc_m_akun akun_hpp", "akun_hpp.id= inv_m_kategori.akun_hpp_id");
            // ->where("inv_m_kategori.is_deleted", "=", 0);

    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'inv_m_kategori.is_deleted') {
                $db->where("inv_m_kategori.is_deleted", '=', $val);
            } else if ($key == 'inv_m_barang.id') {
                $db->customWhere("inv_m_barang.id IS NULL", 'AND');
            } else {
                $db->where($key, 'LIKE', $val);
            }
        }
    }

    $db->groupBy("inv_m_kategori.id")->orderBy("inv_m_kategori.nama");

    $models = $db->findAll();

    $arr = getChildFlat($models, 0);

    if (sizeof($arr) > 0) {
        $tmpArr = [];
        foreach ($arr as $key => $val) {
            $spasi = ($val->level == 0) ? '' : str_repeat("---", $val->level);
            $val->nama_lengkap = $spasi . $val->nama;
            $val->parent = ["id" => $val->parent_id, "nama" => $val->namaInduk, "level" => $val->level_induk];
//            $val->akun_pembelian_id = !empty($val->akun_pembelian_id) ? ["id" => $val->akun_pembelian_id, "nama" => $val->namaPersediaan, "kode" => $val->kodePersediaan] : [];
            $val->akun_persediaan_brg_id = !empty($val->akun_persediaan_brg_id) ? ["id" => $val->akun_persediaan_brg_id, "nama" => $val->namaPersediaan, "kode" => $val->kodePersediaan] : [];
            $val->akun_penjualan_id = !empty($val->akun_penjualan_id) ? ["id" => $val->akun_penjualan_id, "nama" => $val->namaPenjualan, "kode" => $val->kodePenjualan] : [];
            $val->akun_hpp_id = !empty($val->akun_hpp_id) ? ["id" => $val->akun_hpp_id, "nama" => $val->namaHpp, "kode" => $val->kodeHpp] : [];
            /*
             * cek child
             */
            $val->child = count(getChildFlat($models, $val->id));
            $tmpArr[$key] = (array) $val;
        }
        return successResponse($response, [
            'list' => $tmpArr
        ]);
    } else {
        return successResponse($response, [
            'list' => $models
        ]);
    }
});

$app->post('/m_kategori/save', function ($request, $response) {
    $params = $request->getParams();
    $sql = $this->db;

    $validasi = validasi($params);

    if($params['is_dijual'] == 'ya'){
      $validasi = validasi($params, ['akun_persediaan_brg_id' => 'required']);
    } else {
      $validasi = validasi($params, ['akun_hpp_id' => 'required']);
    }

    if ($validasi === true) {
        if (isset($params['parent']) && $params['parent']['id'] != 0) {
            $params['level'] = $params['parent']['level'] + 1;
            $params['is_parent'] = 0;
        } else {
            $params['is_parent'] = 1;
            $params['level'] = 0;
            $params['parent']['id'] = 0;
        }

        $params['akun_persediaan_brg_id'] = !empty($params['akun_persediaan_brg_id']) ? $params['akun_persediaan_brg_id']['id'] : null;
        $params['akun_penjualan_id']      = !empty($params['akun_penjualan_id']) ? $params['akun_penjualan_id']['id'] : null;
        $params['akun_hpp_id']            = !empty($params['akun_hpp_id']) ? $params['akun_hpp_id']['id'] : null;

        if (isset($params['id']) && !empty($params['id'])) {
            $params['parent_id'] = $params['parent']['id'];
            $model = $sql->update("inv_m_kategori", $params, ["id" => $params['id']]);
        } else {
            $params['parent_id'] = $params['parent']['id'];
            $model = $sql->insert("inv_m_kategori", $params);
        }

        //update parent dari data yg disimpan
        if ($model->is_parent == 0) {
            $models = $sql->update("inv_m_kategori", ['is_parent' => 1], ["id" => $model->parent_id]);
        }

        $models = $sql->update("inv_m_kategori", ['level' => $model->level + 1], ["parent_id" => $model->id, 'is_parent' => 0]);

        if ($model) {
            return successResponse($response, $model);
        } else {
            return unprocessResponse($response, ['Data Gagal Di Simpan']);
        }
    } else {
        return unprocessResponse($response, $validasi);
    }
});

$app->post('/m_kategori/trash', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;
    $model = false;
    if ($data['id'] != 1) {
        $model = $db->update("inv_m_kategori", $data, array('id' => $data['id']));
    }
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

$app->post('/m_kategori/delete', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;
    $model = false;
    if ($data['id'] != 1) {
        $model = $db->delete('inv_m_kategori', array('id' => $data['id']));
    }
    if ($model) {
        return successResponse($response, ['data berhasil dihapus']);
    } else {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});

$app->post('/m_kategori/copyCustomer', function ($request, $response) {
    $params = $request->getParams();
    $params = $params['form'];
    $db = $this->db;

    if( empty($params["lokasi_tujuan"]) ){
      return unprocessResponse($response, ['Anda belum memilih Depo Tujuan.']);
      die;
    }

    if( $params["lokasi_tujuan"]['id'] == $params["acc_m_lokasi_id"]['id'] ){
      return unprocessResponse($response, ['Depo Tujuan tidak boleh sama dengan Depo Asal.']);
      die;
    }

    // Cek duplikat
    $duplikat = $db->find(" SELECT id
      FROM acc_m_kontak
      WHERE npwp = '". $params["npwp"] ."'
      AND nama = '". $params["nama"] ."'
      AND alamat = '". $params["alamat"] ."'
      AND type = 'customer'
      AND is_deleted = 0
      AND acc_m_lokasi_id = '". $params["lokasi_tujuan"]['id'] ."'
    ");

    if( !empty($duplikat) ){
      return unprocessResponse($response, ['Customer sudah ada di Depo Tujuan.']);
      die;
    }
    
    $params["acc_m_lokasi_id"]  = !empty($params["lokasi_tujuan"]) ? $params["lokasi_tujuan"]['id'] : null;
    $params["kode"]             = generateNoTransaksi("afu_customer", 0);
    unset($params["id"]);
    unset($params["lokasi_tujuan"]);
    unset($params["acc_m_akun_id"]);

    $model = $db->insert("acc_m_kontak", $params);

    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});
