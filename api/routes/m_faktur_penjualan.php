<?php

function validasi($data, $custom = array()) {
    $validasi = array(
//        'kode' => 'required',
        'nama' => 'required',
    );
    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->get('/m_faktur_penjualan/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;
    $db->select("inv_m_faktur_pajak.*")
        ->from("inv_m_faktur_pajak")
        ->leftJoin("inv_penjualan", "inv_penjualan.inv_m_faktur_pajak_id = inv_m_faktur_pajak.id");

    $db->customWhere("inv_m_faktur_pajak.jenis_faktur IN('penjualan')");


    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'pembelian_terpakai') {
                if ($val == 'tidak') {
                    $db->customWhere("inv_pembelian.inv_m_faktur_pajak_id IS NULL", "AND");
                }
            }
            //  else if ($key == 'is_deleted') {
            //       $db->where("acc_m_lokasi.is_deleted", '=', $val);
            // }
            else if ($key == "inv_penjualan.id") {
                if ($val == 0) {
                    $db->customWhere($key . " IS NULL", "AND");
                } else {
                    $db->customWhere($key . " IS NOT NULL", "AND");
                }
            } else if($key == "bulan") {
              $filter_tanggal_start = date("Y-m-01", strtotime($val));
              $filter_tanggal_end = date("Y-m-t", strtotime($val));

              $db->customWhere("FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') >= '" . $filter_tanggal_start . "'", "AND");
              $db->customWhere("FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') <= '" . $filter_tanggal_end . "'", "AND");

            } else {
                $db->where($key, 'LIKE', $val);
            }
        }
    }

    if (isset($params["limit"]) && !empty($params["limit"])) {
        $db->limit($params["limit"]);
    }
    if (isset($params["offset"]) && !empty($params["offset"])) {
        $db->offset($params["offset"]);
    }
    $models = $db
            ->orderBy("nomor")
            ->findAll();
    $totalItem = $db->count();
//    echo json_encode($models);die();

    if (isset($models)) {
        $arrPenjualan = [];
        $arrPembelian = [];
        foreach ($models as $key => $value) {
            $faktur_id[] = $value->id;
        }
        $faktur_id = implode(",", $faktur_id);

        $db->select("inv_penjualan.id,inv_penjualan.tanggal,inv_m_faktur_pajak_id,acc_m_kontak.nama as supplier")
            ->from("inv_penjualan")
            ->join("LEFT JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id");
        $db->customWhere("inv_penjualan.inv_m_faktur_pajak_id IN(". $faktur_id .")");
        $penjualan_id = $db->findAll();
        if(isset($penjualan_id)) {
            foreach ($penjualan_id as $key => $val) {
                $arrPenjualan[$val->inv_m_faktur_pajak_id]['id'] = $val->id;
                $arrPenjualan[$val->inv_m_faktur_pajak_id]['supplier'] = $val->supplier;
                $arrPenjualan[$val->inv_m_faktur_pajak_id]['tanggal'] = $val->tanggal;

            }
        }
        $db->select("inv_pembelian.id,inv_m_faktur_pajak_id,inv_pembelian.tanggal")
            ->from("inv_pembelian");
        $db->customWhere("inv_pembelian.inv_m_faktur_pajak_id IN(". $faktur_id .")");
        $pembelian_id = $db->findAll();
        if(isset($penjualan_id)) {
            foreach ($pembelian_id as $key => $val) {
                $arrPembelian[$val->inv_m_faktur_pajak_id]['id'] = $val->id;
                $arrPembelian[$val->inv_m_faktur_pajak_id]['tanggal'] = $val->tanggal;
            }
        }
        foreach ($models as $key => $val) {
            if($val->jenis_faktur == "penjualan" && isset($arrPenjualan[$val->id])) {
                $models[$key]->penjualan = $arrPenjualan[$val->id]['id'];
                $models[$key]->supplier = $arrPenjualan[$val->id]['supplier'];
                $models[$key]->tanggal = $arrPenjualan[$val->id]['tanggal'] *1000;
            }
            else if($val->jenis_faktur == "pembelian") {
                $models[$key]->pembelian = $arrPembelian[$val->id]['id'];
                $models[$key]->tanggal =  $arrPembelian[$val->id]['tanggal'] *1000;
                $models[$key]->supplier = "-";
            }
            else {
                $models[$key]->pembelian = null;
                $models[$key]->supplier = "-";
            }
        }

    }


    foreach ($models as $key => $val) {
        $val->terpakai = $val->jenis_faktur == 'penjualan' && isset($val->penjualan) && !empty($val->penjualan) ? "Ya" : ($val->jenis_faktur == 'pembelian' && isset($val->pembelian) && !empty($val->pembelian) ? "Ya" : "Tidak");
    }



    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem
    ]);
});
$app->post('/m_faktur_penjualan/save', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

//    print_r($params);die;

    try {
        $query = 'INSERT INTO inv_m_faktur_pajak (nomor, jenis_faktur) VALUES ';
        $run_query = false;
        $id = 0;
        foreach ($params['detail'] as $key => $val) {
            if (isset($val['id']) && !empty($val['id'])) {
                $id = $val['id'];
                $db->update("inv_m_faktur_pajak", [
                    "nomor" => $val['no_faktur'],
                    "jenis_faktur" => $params['form']['jenis_faktur']
                        ], ["id" => $val['id']]);
            } else {
                $run_query = true;
                if ($val['no_faktur'] != '') {
                    $query .= "('{$val['no_faktur']}', '{$params['form']['jenis_faktur']}'),";
                }
            }
        }

        if ($run_query) {
//            echo $query;die;
            $query = substr($query, 0, -1) . ";";
            $db->run($query);
        }

        if ($id == 0)
            $ret = $db->find("SELECT * FROM inv_m_faktur_pajak ORDER BY id DESC");
        else
            $ret = $db->find("SELECT * FROM inv_m_faktur_pajak WHERE id = $id");

        return successResponse($response, $ret);
    } catch (Exception $exc) {
        return unprocessResponse($response, ['Data Gagal Di Simpan']);
    }
});
$app->post('/m_faktur_penjualan/trash', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $model = $db->update("acc_m_lokasi", $data, array('id' => $data['id']));

    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

$app->post('/m_faktur_penjualan/delete', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

//    pd($data);

    if ((isset($data['mulai']) && !empty($data['mulai'])) || (isset($data['all']) && !empty($data['all']))) {
        $arr_id = [];
        $db->select("inv_m_faktur_pajak.id")
                ->from("inv_m_faktur_pajak")
                ->join("LEFT JOIN", "inv_penjualan", "inv_penjualan.inv_m_faktur_pajak_id = inv_m_faktur_pajak.id");
        if (isset($data['all']) && !empty($data['all'])) {
            $db->customWhere("inv_penjualan.inv_proses_akhir_id IS NULL", "AND");
        } else {
            $db->where("inv_m_faktur_pajak.id", ">=", $data['mulai'])
                    ->where("inv_m_faktur_pajak.id", "<=", $data['selesai']);
        }

        $get_id = $db->customWhere("jenis_faktur = 'penjualan'", "AND")
                ->findAll();

//        pd($get_id);

        foreach ($get_id as $key => $value) {
            array_push($arr_id, $value->id);
        }

//        pd($arr_id);

        $model = $db->run("DELETE FROM inv_m_faktur_pajak WHERE id IN(" . implode(", ", $arr_id) . ")");
        $model2 = $db->run("UPDATE inv_penjualan SET inv_m_faktur_pajak_id = NULL WHERE inv_m_faktur_pajak_id IN(" . implode(", ", $arr_id) . ")");
    } else {
        $model = $db->delete('inv_m_faktur_pajak', array('id' => $data['id']));
    }


    if ($model) {
        return successResponse($response, ['data berhasil dihapus']);
    } else {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});
