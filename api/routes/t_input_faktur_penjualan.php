<?php

function validasi($data, $custom = array()) {
    $validasi = array(
//        'kode' => 'required',
        'nama' => 'required',
    );
    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->get('/t_input_faktur_penjualan/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;
    $db->select("inv_penjualan.*, inv_m_faktur_pajak.id as pajak, inv_m_faktur_pajak.nomor, acc_m_kontak.npwp")
            ->from("inv_penjualan")
            ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
            ->join("JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
//            ->customWhere("acc_m_kontak.npwp != ''", "AND")
            ->where("is_draft", "=", 0)
            ->where("status", "!=", 'dibatalkan');
//            ->where("inv_penjualan.is_ppn", "=", 1);

    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'inv_m_faktur_pajak_id') {
                if ($val == 0) {
                    $db->customWhere("$key IS NULL", "AND");
                } else {
                    $db->customWhere("$key IS NOT NULL", "AND");
                }
            } else {
                $db->where($key, 'like', $val);
            }
        }
    }

    $models = $db->orderBy("inv_penjualan.tanggal ASC, inv_penjualan.acc_m_lokasi_id, inv_penjualan.no_surat_jalan")
//            ->limit(50)
            ->findAll();
//    print_r($models);die;
//    $index = 0;
//    $arr = [];
    foreach ($models as $key => $val) {
        $val->faktur = ["id" => $val->pajak, "nomor" => $val->nomor];
    }

    return successResponse($response, [
        'list' => $models
    ]);
});
$app->post('/t_input_faktur_penjualan/save', function ($request, $response) {
    $params = $request->getParams();
//    print_r($params);
//    die();
    $db = $this->db;
//    $validasi = validasi($params);
    try {
        $query = 'INSERT INTO inv_penjualan (id, inv_m_faktur_pajak_id) VALUES ';
        $run_query = false;
        $id_faktur = '';
        foreach ($params as $key => $val) {
            $run_query = true;
            if (isset($val['faktur']) && !empty($val['faktur'])) {
                $query .= "('{$val['id']}', '{$val['faktur']['id']}'),";
                $id_faktur .= $val['faktur']['id'] . ",";
            }
        }

        if ($run_query) {
            $query = substr($query, 0, -1) . " ON DUPLICATE KEY UPDATE id=VALUES(id), inv_m_faktur_pajak_id=VALUES(inv_m_faktur_pajak_id)";
            $id_faktur = substr($id_faktur, 0, -1);
//            echo $query;die;
            $db->run($query);
//            $db->run("UPDATE inv_m_faktur_pajak SET terpakai = 'ya' WHERE id IN({$id_faktur})");
        }

        return successResponse($response, []);
    } catch (Exception $exc) {
//        echo $exc;die;
        return unprocessResponse($response, ['Data Gagal Di Simpan']);
    }
});
$app->post('/t_input_faktur_penjualan/trash', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;
    $model = false;
    if ($data['id'] != 1) {
        $model = $db->update("acc_m_lokasi", $data, array('id' => $data['id']));
    }
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});
$app->post('/t_input_faktur_penjualan/delete', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;
    $model = false;
    if ($data['id'] != 1) {
        $model = $db->delete('inv_m_faktur_penjualan', array('id' => $data['id']));
    }
    if ($model) {
        return successResponse($response, ['data berhasil dihapus']);
    } else {
        return unprocessResponse($response, ['data gagal dihapus']);
    }
});

$app->get('/t_input_faktur_penjualan/default_lokasi', function ($request, $response) {

    $db = $this->db;

    $pemasukan = !empty(config('LOKASI_PEMASUKAN')) ? $db->find("SELECT * FROM acc_m_lokasi WHERE id = " . config('LOKASI_PEMASUKAN')) : 0;
    $pengeluaran = !empty(config('LOKASI_PENGELUARAN')) ? $db->find("SELECT * FROM acc_m_lokasi WHERE id = " . config('LOKASI_PENGELUARAN')) : 0;

    return successResponse($response, ['lokasi_pemasukan' => $pemasukan, 'lokasi_pengeluaran' => $pengeluaran]);
});
