<?php

$app->get('/l_rincian_biaya/akunRincian', function ($request, $response) {
    $pararms = $request->getParams();
    $db = $this->db;

    $models = $db->select("*")->from("acc_m_akun")
            ->where("tipe", "=", "BEBAN")
            ->where("is_tipe", "=", 1)
            ->where("is_deleted", "=", 0)
            ->where("parent_id", "!=", 0)
            ->findAll();

    foreach ($models as $key => $value) {
        $value->nama = mb_convert_encoding($value->nama, 'UTF-8', 'UTF-8');
        $spasi = ($value->level == 1) ? '' : str_repeat("--", $value->level - 1);
        $value->nama_lengkap = $spasi . $value->kode . ' - ' . $value->nama;
    }

    return successResponse($response, ['list' => $models]);
});

$app->get('/l_rincian_biaya/laporan', function ($request, $response) {
    $params = $request->getParams();

    $db = $this->db;

//    pd($params);

    $tanggal_start = $params['tanggal'] . '-01-01';
    $tanggal_end = $params['tanggal'] . '-12-31';
    $data['tanggal'] = date("d/m/Y", strtotime($tanggal_start)) . ' Sampai ' . date("d/m/Y", strtotime($tanggal_end));
    $data['disiapkan'] = date("d/m/Y, H:i");
    $data['lokasi'] = $params['nama_lokasi'];
    $data['akun'] = strtoupper($params['nama_akun']);
    $data['tahun'] = $params['tanggal'];
    if (isset($params['m_lokasi_id'])) {
        $lokasiId = getChildId("acc_m_lokasi", $params['m_lokasi_id']);
        /*
         * jika lokasi punya child
         */
        if (!empty($lokasiId)) {
            $lokasiId[] = $params['m_lokasi_id'];
            $lokasiId = implode(",", $lokasiId);
        } else {
            $lokasiId = $params['m_lokasi_id'];
        }
    }

    if (isset($params['m_akun_id'])) {
        $akunId = getChildId("acc_m_akun", $params['m_akun_id']);
        /*
         * jika lokasi punya child
         */
        if (!empty($akunId)) {
            $akunId = implode(",", $akunId);
        } else {
            $akunId = $params['m_akun_id'];
        }
    }

//    pd($akunId);
//    pd($lokasiId);

    $arr = [];
    $data['tanggal'] = [];

    $akun = $db->select("id, nama")->from("acc_m_akun")->customWhere("id IN($akunId)", "AND")->orderBy("id")->findAll();

    foreach ($akun as $key => $value) {
        for ($i = 1; $i <= 12; $i++) {
            $data['tanggal'][] = date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'));
            $arr[$value->id][date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'))] = [];
        }
        $arr[$value->id]['nama'] = $value->nama;
        $arr[$value->id]['no'] = $key + 1;
    }

    $data['tanggal'] = [];
    $data['total'] = [];
    for ($i = 1; $i <= 12; $i++) {
        $data['tanggal'][] = date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'));
        $data['total'][date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'))] = 0;
    }


//    pd($arr);

    $data['total_debit'] = 0;
    $data['total_kredit'] = 0;
    $index = 0;
    /*
     * ambil trans detail dari akun
     */

    $db->select("acc_trans_detail.id, acc_trans_detail.kode, 
        acc_trans_detail.m_akun_id,
                acc_trans_detail.debit, 
                acc_trans_detail.kredit, 
                acc_trans_detail.keterangan, 
                acc_trans_detail.tanggal, 
                acc_m_akun.kode as kodeAkun, 
                acc_m_akun.nama as namaAkun,
                acc_m_lokasi.kode as kodeLokasi,
                acc_m_lokasi.nama as namaLokasi,
                lokasi_saldo.nama as namaLokasiSaldo
        ")->from("acc_trans_detail");
    if (isset($params['m_lokasi_id']) && !empty($params['m_lokasi_id'])) {
        $db->customWhere("acc_trans_detail.m_lokasi_jurnal_id IN ($lokasiId)");
    }
    if (isset($params['m_akun_id']) && !empty($params['m_akun_id'])) {
        $db->customWhere("acc_trans_detail.m_akun_id IN ($akunId)", "AND");
    }
    $db->leftJoin("acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
            ->leftJoin("acc_m_lokasi", "acc_m_lokasi.id = acc_trans_detail.m_lokasi_jurnal_id")
            ->leftJoin("acc_m_lokasi as lokasi_saldo", "lokasi_saldo.id = acc_trans_detail.m_lokasi_id")
            ->andWhere('date(acc_trans_detail.tanggal)', '>=', $tanggal_start)
            ->andWhere('date(acc_trans_detail.tanggal)', '<=', $tanggal_end)
            ->orderBy("acc_trans_detail.tanggal ASC, acc_trans_detail.kode ASC, acc_trans_detail.id ASC");
    $gettransdetail = $db->findAll();

//    pd($gettransdetail);

    foreach ($gettransdetail as $key => $value) {
        if (isset($arr[$value->m_akun_id][date('F', strtotime($value->tanggal))]['debit'])) {
            $arr[$value->m_akun_id][date('F', strtotime($value->tanggal))]['debit'] += $value->debit;
        } else {
            $arr[$value->m_akun_id][date('F', strtotime($value->tanggal))]['debit'] = $value->debit;
        }

        if (isset($arr[$value->m_akun_id][date('F', strtotime($value->tanggal))]['kredit'])) {
            $arr[$value->m_akun_id][date('F', strtotime($value->tanggal))]['kredit'] += $value->kredit;
        } else {
            $arr[$value->m_akun_id][date('F', strtotime($value->tanggal))]['kredit'] = $value->kredit;
        }

        if (isset($arr[$value->m_akun_id][date('F', strtotime($value->tanggal))]['total'])) {
            $arr[$value->m_akun_id][date('F', strtotime($value->tanggal))]['total'] += $value->debit - $value->kredit;
        } else {
            $arr[$value->m_akun_id][date('F', strtotime($value->tanggal))]['total'] = $value->debit - $value->kredit;
        }

        if (isset($arr[$value->m_akun_id]['total'])) {
            $arr[$value->m_akun_id]['total'] += $value->debit - $value->kredit;
        } else {
            $arr[$value->m_akun_id]['total'] = $value->debit - $value->kredit;
        }

        if (isset($data['total'][date('F', strtotime($value->tanggal))])) {
            $data['total'][date('F', strtotime($value->tanggal))] += $value->debit - $value->kredit;
        } else {
            $data['total'][date('F', strtotime($value->tanggal))] = $value->debit - $value->kredit;
        }

        if (isset($data['total']['total'])) {
            $data['total']['total'] += $value->debit - $value->kredit;
        } else {
            $data['total']['total'] = $value->debit - $value->kredit;
        }

        $arr[$value->m_akun_id]['nama'] = $value->namaAkun;
    }

//    pd($arr);
    /*
     * sorting array berdasarkan tanggal
     */
//    $kode = "";
//    $namaLokasi = "";
//    foreach ($arr as $key => $val) {
//        $arr[$key]['namaLokasi'] = $val['namaLokasiSaldo'];
//        if ($val['kode'] == $kode && $val['kode'] != "") {
//            $arr[$key]['kode'] = "";
//            $arr[$key]['kodeLokasi'] = "";
//            $arr[$key]['tanggal'] = "";
//        } else {
//            $kode = $val['kode'];
//            $namaLokasi = $val['namaLokasi'];
//        }
//    }
    if (isset($params['export']) && $params['export'] == 1) {
        $view = twigView();
        $content = $view->fetch('laporan/rincian_biaya.html', [
            "data" => $data,
            "detail" => $arr,
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment;Filename=laporan-rincian-biaya.xls");
        echo $content;
    } elseif (isset($params['print']) && $params['print'] == 1) {
        $view = twigView();
        $content = $view->fetch('laporan/rincian_biaya.html', [
            "data" => $data,
            "detail" => $arr,
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, ["data" => $data, "detail" => $arr]);
    }
});
