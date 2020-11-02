<?php

$app->get('/m_barang/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("inv_m_barang.*, inv_m_kategori.nama as kategori")
            ->from("inv_m_barang")
            ->join("LEFT JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->where("inv_m_barang.is_pakai", "=", 0);


    $lokasi = 0;

    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'inv_m_barang.is_deleted') {
                $db->where("inv_m_barang.is_deleted", '=', $val);
            } elseif ($key == 'acc_m_lokasi_id') {
                $lokasi = $val;
            } else if ($key == 'inv_m_barang.inv_m_kategori_id') {
                $db->where($key, '=', $val);
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
    $models = $db->findAll();
    $totalItem = $db->count();

    $allAkun = getAllData($db, 'acc_m_akun', 'id');
    $allKategori = getAllData($db, 'inv_m_kategori', 'id');
    $allSatuan = getAllData($db, 'inv_m_satuan', 'id');

    foreach ($models as $key => $value) {
        $models[$key]->inv_m_kategori_id = isset($allKategori[$value->inv_m_kategori_id]) ? $allKategori[$value->inv_m_kategori_id] : NULL;

        $models[$key]->inv_m_satuan_id = isset($allSatuan[$value->inv_m_satuan_id]) ? $allSatuan[$value->inv_m_satuan_id] : NULL;
        $models[$key]->akun_pembelian_id = isset($allAkun[$value->akun_pembelian_id]) ? $allAkun[$value->akun_pembelian_id] : NULL;
        $models[$key]->akun_penjualan_id = isset($allAkun[$value->akun_penjualan_id]) ? $allAkun[$value->akun_penjualan_id] : NULL;
        $models[$key]->akun_hpp_id = isset($allAkun[$value->akun_hpp_id]) ? $allAkun[$value->akun_hpp_id] : NULL;
        $models[$key]->stok = $lokasi != 0 ? getStok($db, $value->id, $lokasi) : getStok($db, $value->id, $lokasi, true);
    }

    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
    ]);
});

$app->post('/m_barang/trash', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $model = $db->update("inv_m_barang", ['is_deleted' => $data['is_deleted']], array('id' => $data['id']));
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

$app->get('/m_barang/kodeBarang', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $get = $db->select("kode")
            ->from("inv_m_barang")
            ->where('type', '=', 'barang')
            ->andWhere("jenis_kode", "=", "auto")
            ->orderBy('id DESC')
            ->limit(1)
            ->find();

    if ($get) {
        $kode_cust = (substr($get->kode, -5) + 1);
        $kodeCust = substr('00000' . $kode_cust, strlen($kode_cust));
        $kodeCust = "BRG" . $kodeCust;
    } else {
        $kodeCust = "BRG00001";
    }

    return successResponse($response, ['kode' => $kodeCust]);
});

function validasi($data, $custom = array()) {
    $validasi = array(
        // 'inv_m_jenis_id'    => 'required',
        // 'is_dijual' => 'required',
        'nama' => 'required',
        'inv_m_kategori_id' => 'required',
//        'inv_m_satuan_id' => 'required',
        // 'stok_maksimal' => 'required',
        // 'stok_minimal' => 'required',
        'harga_pokok' => 'required',
//        'harga_beli' => 'required',
//        'harga_jual' => 'required',
//        'akun_pembelian_id' => 'required',
//        'akun_penjualan_id' => 'required',
//        'akun_hpp_id'       => 'required',
    );

    GUMP::set_field_name("harga_pokok", "Jenis Persediaan");
    GUMP::set_field_name("inv_m_kategori_id", "Kategori");
    GUMP::set_field_name("inv_m_satuan_id", "Satuan");
    GUMP::set_field_name("akun_pembelian_id", "Akun Pembelian");
    GUMP::set_field_name("akun_penjualan_id", "Akun Penjualan");
    GUMP::set_field_name("akun_hpp_id", "Akun HPP");
    GUMP::set_field_name("inv_m_jenis_id", "Jenis Barang");
    GUMP::set_field_name("harga_jual", "Harga Beli");
    GUMP::set_field_name("harga_beli", "Harga Jual");
    GUMP::set_field_name("stok_minimal", "Stok Minimal");
    GUMP::set_field_name("stok_maksimal", "Stok Maksimal");
    GUMP::set_field_name("is_dijual", "Produk Dijual");

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->post('/m_barang/save', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;
    $validasi = isset($params['form']['is_popup']) ? validasi($params['form']) : validasi($params['form']);
    if ($validasi !== true) {
        return unprocessResponse($response, $validasi);
    }

//    echo json_encode($params);die;
    try {

        $params['form']['inv_m_kategori_id'] = $params['form']['inv_m_kategori_id']['id'];
//        $params['form']['inv_m_jenis_id']  = !empty($params['form']['akun_pembelian_id']) ? $params['form']['akun_pembelian_id']['id'] : null;
        $params['form']['inv_m_satuan_id'] = isset($params['form']['inv_m_satuan_id']['id']) && !empty($params['form']['inv_m_satuan_id']['id']) ? $params['form']['inv_m_satuan_id']['id'] : null;
        $params['form']['akun_pembelian_id'] = !empty($params['form']['akun_pembelian_id']) ? $params['form']['akun_pembelian_id']['id'] : null;
        $params['form']['akun_penjualan_id'] = !empty($params['form']['akun_penjualan_id']) ? $params['form']['akun_penjualan_id']['id'] : null;
        $params['form']['akun_hpp_id'] = !empty($params['form']['akun_hpp_id']) ? $params['form']['akun_hpp_id']['id'] : null;

        if (isset($params['form']["id"])) {
            $db->delete("inv_m_barang_nama", ['inv_m_barang_id' => $params['form']['id']]);
            $model = $db->update("inv_m_barang", $params['form'], array('id' => $params['form']['id']));
        } else {
            $model = $db->insert("inv_m_barang", $params['form']);
        }

        if (isset($params['detail']) && !empty($params['detail'])) {
            foreach ($params['detail'] as $key => $value) {
                $value['inv_m_barang_id'] = $model->id;
                $db->insert("inv_m_barang_nama", $value);
            }
        }

        $models = $db->select("inv_m_barang.*,
                inv_m_barang.id as barang_id,
                0 as harga_beli,
                inv_m_satuan.nama as nama_satuan,
                inv_m_kategori.nama as nama_kategori,
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
                akun_hpp.nama as namaHpp")
                        ->from("inv_m_barang")
                        ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
                        ->join('LEFT JOIN', "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
                        ->join("left join", "acc_m_akun akun_pembelian", "akun_pembelian.id= inv_m_kategori.akun_pembelian_id")
                        ->join("left join", "acc_m_akun akun_penjualan", "akun_penjualan.id = inv_m_kategori.akun_penjualan_id")
                        ->join("left join", "acc_m_akun akun_hpp", "akun_hpp.id= inv_m_kategori.akun_hpp_id")
                        ->join("left join", "acc_m_akun akun_persediaan_brg", "akun_persediaan_brg.id= inv_m_kategori.akun_persediaan_brg_id")
                        ->where("inv_m_barang.is_deleted", "=", 0)
                        ->where("inv_m_barang.id", "=", $model->id)->find();

        $models->akun_pembelian_id = !empty($models->akun_pembelian_id) ? ["id" => $models->akun_pembelian_id, "nama" => $models->namaPembelian, "kode" => $models->kodePembelian] : [];
        $models->akun_penjualan_id = !empty($models->akun_penjualan_id) ? ["id" => $models->akun_penjualan_id, "nama" => $models->namaPenjualan, "kode" => $models->kodePenjualan] : [];
        $models->akun_hpp_id = !empty($models->akun_hpp_id) ? ["id" => $models->akun_hpp_id, "nama" => $models->namaHpp, "kode" => $models->kodeHpp] : [];
        $models->akun_persediaan_brg_id = !empty($models->akun_persediaan_brg_id) ? ["id" => $models->akun_persediaan_brg_id, "nama" => $models->namaPersediaan, "kode" => $models->kodePersediaan] : [];

        return successResponse($response, $models);
    } catch (Exception $e) {
        return unprocessResponse($response, ['Terjadi kesalahan pada server']);
    }
});

$app->get('/m_barang/getBarang', function ($request, $response) {
    $db = $this->db;
    $params = $request->getParams();

    if (isset($params['is_dijual']) && !empty($params['is_dijual'])) {
        $db->select("
      inv_m_barang.*,
      inv_m_barang.id as barang_id,
      0 as harga_beli,
      inv_m_satuan.nama as nama_satuan,
      inv_m_kategori.nama as nama_kategori,
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
      ");
    } else {
        $db->select("inv_m_barang.*,
      inv_m_barang.id as barang_id,
      0 as harga_beli,
      inv_m_satuan.nama as nama_satuan,
      inv_m_kategori.nama as nama_kategori,
      inv_m_kategori.akun_pembelian_id,
      inv_m_kategori.akun_persediaan_brg_id,
      inv_m_kategori.akun_penjualan_id,
      inv_m_kategori.akun_hpp_id,
      (CASE WHEN inv_m_barang_nama.nama IS NOT NULL THEN inv_m_barang_nama.nama ELSE inv_m_barang.nama END) as nama,
      inv_m_barang_nama.id as inv_m_barang_nama_id,
      akun_pembelian.kode as kodePembelian,
      akun_pembelian.nama as namaPembelian,
      akun_persediaan_brg.kode as kodePersediaan,
      akun_persediaan_brg.nama as namaPersediaan,
      akun_penjualan.kode as kodePenjualan,
      akun_penjualan.nama as namaPenjualan,
      akun_hpp.kode as kodeHpp,
      akun_hpp.nama as namaHpp");
    }
    $db->from("inv_m_barang");
//             ->join('LEFT JOIN', "inv_kartu_stok", "inv_kartu_stok.inv_m_barang_id = inv_m_barang.id")
    if (isset($params['is_dijual']) && !empty($params['is_dijual'])) {

    } else {
        $db->join('LEFT JOIN', "inv_m_barang_nama", "inv_m_barang_nama.inv_m_barang_id = inv_m_barang.id");
    }

    $db->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->join('LEFT JOIN', "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->join("left join", "acc_m_akun akun_pembelian", "akun_pembelian.id= inv_m_kategori.akun_pembelian_id")
            ->join("left join", "acc_m_akun akun_penjualan", "akun_penjualan.id = inv_m_kategori.akun_penjualan_id")
            ->join("left join", "acc_m_akun akun_hpp", "akun_hpp.id= inv_m_kategori.akun_hpp_id")
            ->join("left join", "acc_m_akun akun_persediaan_brg", "akun_persediaan_brg.id= inv_m_kategori.akun_persediaan_brg_id")
            ->where("inv_m_barang.is_deleted", "=", 0);

    if (isset($params['val']) && !empty($params['val'])) {
        $db->customWhere("inv_m_barang.nama LIKE '%" . $params['val'] . "%' OR inv_m_barang.kode LIKE '%" . $params['val'] . "%'", "AND");
    }

    if (isset($params['is_dijual']) && !empty($params['is_dijual'])) {
        $db->andWhere("inv_m_kategori.is_dijual", "=", "ya");
    }

    if (isset($params['jenis']) && !empty($params['jenis'])) {
        $db->andWhere("inv_m_kategori.jenis_barang", "=", $params['jenis']);
    }
    if (isset($params['is_pakai'])) {
        $db->andWhere("inv_m_barang.is_pakai", "=", $params['is_pakai']);
    }
//    if (isset($params['acc_m_lokasi_id'])) {
//        $db->andWhere("inv_kartu_stok.acc_m_lokasi_id", "=", $params['acc_m_lokasi_id']);
//    }
    // $db->orderBy("inv_m_barang.nama, inv_kartu_stok.created_at ASC");
    // $db->groupBy("inv_m_barang.id");
    // $models = $db->limit(20)->findAll();
    $models = $db->findAll();
// pd([
//   $models,
//   $params
// ]);
//    pd($models);die;
    foreach ($models as $key => $value) {

      if( !empty($params['tanggal']) ){
        $tanggal_start  = '2019-01-01';
        $tanggal_end     = date("Y-m-d", strtotime($params['tanggal']) );
        $stok = pos_stok($value->id, $params['acc_m_lokasi_id'], false, $tanggal_start, $tanggal_end);
        // pd([
        //   'test',
        //   $stok,
        //   $tanggal_start,
        //   $params['tanggal']
        // ]);
      } else {
        $models[$key]->stok             = pos_stok($value->id, $params['acc_m_lokasi_id']);
      }
        // $models[$key]->hpp              = pos_hpp($value->id, $params['acc_m_lokasi_id']);
        $models[$key]->hpp              = 0;
        $value->akun_pembelian_id       = !empty($value->akun_pembelian_id) ? ["id" => $value->akun_pembelian_id, "nama" => $value->namaPembelian, "kode" => $value->kodePembelian] : [];
        $value->akun_penjualan_id       = !empty($value->akun_penjualan_id) ? ["id" => $value->akun_penjualan_id, "nama" => $value->namaPenjualan, "kode" => $value->kodePenjualan] : [];
        $value->akun_hpp_id             = !empty($value->akun_hpp_id) ? ["id" => $value->akun_hpp_id, "nama" => $value->namaHpp, "kode" => $value->kodeHpp] : [];
        $value->akun_persediaan_brg_id  = !empty($value->akun_persediaan_brg_id) ? ["id" => $value->akun_persediaan_brg_id, "nama" => $value->namaPersediaan, "kode" => $value->kodePersediaan] : [];
    }

    return successResponse($response, [
        'list' => $models
    ]);
});


//===================== Gambar ==============================================
$app->post('/m_barang/upload/{folder}', function ($request, $response) {
    $folder = $request->getAttribute('folder');
    $params = $request->getParams();

    if (!is_dir($folder)) {
        mkdir("../img/" . $folder, 0777);
    }

    if (!empty($_FILES)) {
        $tempPath = $_FILES['file']['tmp_name'];
        $sql = $this->db;
        $produk_foto = $sql->find("select * from inv_m_barang_foto order by id desc");

        $gid = (isset($produk_foto->id)) ? $produk_foto->id + 1 : 1;
        $newName = $gid . "_" . urlParsing($_FILES['file']['name']);
        $uploadPath = "../img/" . $folder . DIRECTORY_SEPARATOR . $newName;

//        echo json_encode($tempPath);
//        die();

        move_uploaded_file($tempPath, $uploadPath);

        if ($params['id'] == "undefined" || empty($params['id'])) {
            $produk_id = $sql->find("select * from inv_m_barang order by id desc");
            $pid = (isset($produk_id->id)) ? $produk_id->id + 1 : 1;
        } else {
            $pid = $params['id'];
        }
        $file = $uploadPath;
        if (file_exists($file)) {
            $answer = array('answer' => 'File transfer completed', 'img' => $newName, 'id' => $gid);
            if ($answer['answer'] == "File transfer completed") {
                $data = array(
                    'id' => $gid,
                    'inv_m_barang_id' => $pid,
                    'img' => $newName,
                );
                $create_foto = $sql->insert('inv_m_barang_foto', $data);
            }
            echo json_encode($answer);
        } else {
            if (file_exists($uploadPath)) {
                $answer = array('answer' => 'File transfer completed', 'img' => $newName, 'id' => $gid);
            } else {
                echo $uploadPath;
            }
        }
    } else {
        echo 'No files';
    }
});

$app->get('/m_barang/view/{id}', function ($request, $response) {
    $db = $this->db;
    $id = $request->getAttribute('id');
    $path = substr(config('PATH_IMG'), 3, 4);



    $produk_foto = $db->findAll("SELECT * FROM inv_m_barang_foto WHERE inv_m_barang_id = $id");
    $gambar = array();
    foreach ($produk_foto as $key2 => $value2) {
        $gambar[$key2]['id'] = $value2->id;
        $gambar[$key2]['img'] = $path . 'produk/' . $value2->img;
    }

    $alternatif = $db->findAll("SELECT * FROM inv_m_barang_nama WHERE inv_m_barang_id = $id");

    return successResponse($response, ['images' => $gambar, 'path' => $path, 'alternatif' => $alternatif]);
});

$app->post('/m_barang/removegambar', function ($request, $response) {
    $params = $request->getParams();
    $sql = $this->db;

    $delete = $sql->delete("inv_m_barang_foto", ["id" => $params["id"]]);
    $dir = substr(__DIR__, 0, -11);

    unlink($dir . "/" . $params['img']);
});
