<?php

$app->get('/t_saldo_awal_piutang/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    date_default_timezone_set("Asia/Jakarta");

    if (isset($params['bulan']) && !empty($params['bulan'])) {
        $bulan_awal = date("Y-m-01", strtotime($params['bulan']));
        $bulan_akhir = date("Y-m-t", strtotime($params['bulan']));
    }

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("
      inv_penjualan.*,
      m_user.nama as pembuat,
      acc_m_akun.nama acc_m_akun_nama,
      acc_m_akun.kode acc_m_akun_kode
    ")
    ->from("inv_penjualan")
    ->join("LEFT JOIN", "m_user", "m_user.id = inv_penjualan.created_by")
    ->join("LEFT JOIN", "acc_m_akun", "acc_m_akun.id = inv_penjualan.m_akun_id")
    ->join("LEFT JOIN", "acc_m_lokasi", "acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id")
    ->join("JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
    ->where("inv_penjualan.tipe","=","piutang");

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

    $db->where("inv_penjualan.tipe", "=", 'piutang');

    $models = $db->orderBy("acc_m_lokasi.nama ASC, inv_penjualan.tanggal ASC")->findAll();
    $totalItem = $db->count();

    $allLokasi = getAllData($db, 'acc_m_lokasi', 'id');

    foreach ($models as $key => $value) {
        $models[$key]->no               = $params['offset'] + $key + 1;
        $models[$key]->grand_total      = $value->total;
        $models[$key]->tanggal2         = date("Y-m-d", $value->tanggal);
        $models[$key]->acc_m_lokasi_id  = isset($allLokasi[$value->acc_m_lokasi_id]) ? $allLokasi[$value->acc_m_lokasi_id] : [];

        $supp                           = $db->find("SELECT * FROM acc_m_kontak WHERE id = {$value->acc_m_kontak_id}");
        $models[$key]->acc_m_kontak_id  = !empty($supp) ? $supp : [];
        $models[$key]->total            = intval($value->total);
        $models[$key]->checklist        = false;
        $models[$key]->terbayar         = 0;
        $models[$key]->m_akun_id        = [
          'id'        => $value->m_akun_id,
          'nama'      => $value->acc_m_akun_nama,
          'kode'      => $value->acc_m_akun_kode,
        ];
    }

    return successResponse($response, [
        'list' => $models,
        'totalDpp' => !empty($totalDpp->total_dpp) ? $totalDpp->total_dpp : 0,
        'totalItems' => $totalItem,
    ]);
});

$app->get('/t_saldo_awal_piutang/customer', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select("*")
            ->from("acc_m_kontak")
            ->where("nama", "LIKE", $params['nama'])
            ->where("type", "=", "customer");

    if (!empty($params['lokasi'])) {
        $db->andWhere("acc_m_kontak.acc_m_lokasi_id", "=", $params['lokasi']);
    }

    $db->limit(20);

    $models = $db->findAll();

    return successResponse($response, $models);
});

function validasiPenjualan($data, $custom = array()) {
    $validasi = array(
        'acc_m_lokasi_id'   => 'required',
        'acc_m_kontak_id'   => 'required',
        'tanggal'           => 'required',
        'm_akun_id'         => 'required',
        'no_invoice'        => 'required',
    );

    GUMP::set_field_name("acc_m_kontak_id", "Customer");
    GUMP::set_field_name("acc_m_lokasi_id", "Lokasi");
    GUMP::set_field_name("m_akun_id", "Akun Keluar");
    GUMP::set_field_name("no_invoice", "Nomor Transaksi");

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->post('/t_saldo_awal_piutang/save', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;
    $keterangan_trans = "";
    $tanggal = date('Y-m-d', strtotime($data['form']['tanggal']));

    //simpan ke session
    $lokasi = $data['form']['acc_m_lokasi_id'];

    $data['form']['acc_m_lokasi_id']    = !empty($data['form']['acc_m_lokasi_id']['id']) ? $data['form']['acc_m_lokasi_id']['id'] : NULL;
    $data['form']['tanggal']            = strtotime($tanggal);
    $data['form']['kode']               = generatePenjualan('kode', $tanggal);
    $data['form']['is_ppn']             = isset($data['form']['is_ppn']) ? $data['form']['is_ppn'] : 0;
    $data['form']['tipe']               = 'Piutang';
    $data['form']['total']              = !empty($data['form']['piutang']) ? $data['form']['piutang'] : 0;
    $data['form']['acc_m_kontak_id']    = (isset($data['form']['acc_m_kontak_id']['id'])) ? $data['form']['acc_m_kontak_id']['id'] : null;

    $validasi = validasiPenjualan($data['form']);
    if ($validasi !== true) {
        return unprocessResponse($response, $validasi);
    }
    $data['form']['m_akun_id']          = $data['form']['m_akun_id']['id'];

    if ($data['form']['type'] == 'draft') {
        $data['form']['status']     = 'draft';
        $data['form']['is_draft']   = 1;
    }else {
        $data['form']['status']     = 'belum lunas';
        $data['form']['is_draft']   = 0;
    }

    $data['form']['ppn']    = !empty($data['form']['ppn']) ? $data['form']['ppn'] : 0;

    try {

        if (isset($data['form']["id"])) {
            $model = $db->update("inv_penjualan", $data['form'], array('id' => $data['form']['id']));
        } else {
            $model = $db->insert("inv_penjualan", $data['form']);
            $_SESSION['user']['temp']['depo'] = $lokasi;
            $_SESSION['user']['temp']['tanggal'] = $tanggal;
        }

        $lokasi = config('LOKASI_STOK') != 0 ? config('LOKASI_STOK') : $model->acc_m_lokasi_id;

        if($model->status != 'draft'){
          $paramsJurnal = [
            'reff_type'   => 'inv_penjualan',
            'reff_id'     => $model->id,
          ];

          simpanJurnal($paramsJurnal);
        }

        return successResponse($response, $model);
    } catch (Exception $exc) {
        echo $exc;
    }
});

$app->post('/t_saldo_awal_piutang/unpost', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    try {
        $update = $db->update('inv_penjualan', ['status' => 'pending'], ['id' => $params['id']]);

        // Hapus Jurnal Umum dan Trans Detail
        $paramsJurnal = [
          'reff_type'   => 'inv_penjualan',
          'reff_id'     => $params['id'],
        ];
        hapusJurnalUmum($paramsJurnal);
        // Hapus Jurnal Umum dan Trans Detail- END

        return successResponse($response, []);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->post('/t_saldo_awal_piutang/hapusFaktur', function ($request, $response) {
    $params = $request->getParams();
    $db     = $this->db;

    try {
        $update = $db->delete('inv_penjualan', ['id' => $params['id']]);

        // Hapus Jurnal Umum dan Trans Detail
        $paramsJurnal = [
          'reff_type'   => 'inv_penjualan',
          'reff_id'     => $params['id'],
        ];
        hapusJurnalUmum($paramsJurnal);
        // Hapus Jurnal Umum dan Trans Detail- END

        return successResponse($response, ['Berhasil menghapus data penjualan.']);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});
