<?php

$app->get('/l_rekapan_depo/laporan', function ($request, $response) {
    $params = $request->getParams();

    $db = $this->db;

//    pd($params);

    $tanggal_start = $params['tanggal'] . '-01-01';
    $tanggal_end = $params['tanggal'] . '-12-31';
    $tanggal_end_saldo = $params['tanggal'] . '-12-01';
//    $data['tanggal'] = date("d/m/Y", strtotime($tanggal_start)) . ' Sampai ' . date("d/m/Y", strtotime($tanggal_end));
    $data['disiapkan'] = date("d/m/Y, H:i");
    $data['lokasi'] = $params['nama_lokasi'];
//    $data['akun'] = strtoupper($params['nama_akun']);
    $data['tahun'] = $params['tanggal'];
    if (isset($params['m_lokasi_id'])) {
        $lokasiId = getChildId("acc_m_lokasi", $params['m_lokasi_id']);
        /*
         * jika lokasi punya child
         */
        if (!empty($lokasiId)) {
//            $lokasiId[] = $params['m_lokasi_id'];
            $lokasiId = implode(",", $lokasiId);
        } else {
            $lokasiId = $params['m_lokasi_id'];
        }
    }

//    pd($lokasiId);

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

    $lokasi = $db->select("*")->from("acc_m_lokasi")->customWhere("id IN($lokasiId)")->orderBy("id")->findAll();
    $akun_kas = $db->select("*")->from("acc_m_akun")->where("is_kas", "=", 1)->findAll();

    $akunId = [];
    foreach ($akun_kas as $key => $value) {
        $akunId[] = $value->id;
    }

//    pd($lokasi);

    $list = ["PENERIMAAN", "PENGELUARAN"];

    $arr = [];
    foreach ($list as $key => $value) {
        foreach ($lokasi as $k => $v) {
            for ($i = 1; $i <= 12; $i++) {
                $date = date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'));
                $arr[$value]['detail'][$v->id]['detail'][$date] = 0;
            }
            $arr[$value]['detail'][$v->id]['nama'] = $v->nama;
            $arr[$value]['detail'][$v->id]['total'] = 0;
        }

        for ($i = 1; $i <= 12; $i++) {
            $date = date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'));
            $arr[$value]['total'][$date] = 0;
        }
        $arr[$value]['total2'] = 0;
    }

//    pd($arr);

    for ($i = 1; $i <= 12; $i++) {
        $data['tanggal'][] = date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'));
        $data['saldo_awal'][date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'))] = 0;
        $data['saldo_akhir'][date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'))] = 0;
    }

    $akunId = implode(", ", array_unique($akunId));

//    pd($arr);
//    pd($akunId);

    /*
     * Saldo awal
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
                lokasi_saldo.nama as namaLokasiSaldo,
                lokasi_saldo.id as idLokasiSaldo
        ")->from("acc_trans_detail")
            ->where("acc_m_akun.is_kas", "=", 1);
    if (isset($params['m_lokasi_id']) && !empty($params['m_lokasi_id'])) {
        $db->customWhere("acc_trans_detail.m_lokasi_id IN ($lokasiId)", "AND");
    }
    if (isset($akunId) && !empty($akunId)) {
        $db->customWhere("acc_trans_detail.m_akun_id IN ($akunId)", "AND");
    }
    $db->leftJoin("acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
            ->leftJoin("acc_m_lokasi", "acc_m_lokasi.id = acc_trans_detail.m_lokasi_jurnal_id")
            ->leftJoin("acc_m_lokasi as lokasi_saldo", "lokasi_saldo.id = acc_trans_detail.m_lokasi_id")
//            ->customWhere("LENGTH(debit) > 0 AND debit != 0", "AND")
            ->andWhere('date(acc_trans_detail.tanggal)', '<', $tanggal_end_saldo)
            ->orderBy("acc_trans_detail.tanggal ASC, acc_trans_detail.kode ASC, acc_trans_detail.id ASC");
    $trans_detail_awal = $db->findAll();
//
////    $arr_awal = [];
    $data['total_saldo_awal'] = 0;
    foreach ($trans_detail_awal as $key => $value) {
        $month = date('m', strtotime($value->tanggal));

        for ($i = ($month + 1); $i <= 12; $i++) {
            $date = date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'));
            $data['saldo_awal'][$date] += $value->debit - $value->kredit;
            $data['total_saldo_awal'] += $value->debit - $value->kredit;
        }
    }
//    pd($data['saldo_awal']);

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
                lokasi_saldo.nama as namaLokasiSaldo,
                lokasi_saldo.id as idLokasiSaldo
        ")->from("acc_trans_detail")
            ->where("acc_m_akun.is_kas", "=", 1);
    if (isset($params['m_lokasi_id']) && !empty($params['m_lokasi_id'])) {
        $db->customWhere("acc_trans_detail.m_lokasi_id IN ($lokasiId)", "AND");
    }
    if (isset($akunId) && !empty($akunId)) {
        $db->customWhere("acc_trans_detail.m_akun_id IN ($akunId)", "AND");
    }
    $db->leftJoin("acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
            ->leftJoin("acc_m_lokasi", "acc_m_lokasi.id = acc_trans_detail.m_lokasi_jurnal_id")
            ->leftJoin("acc_m_lokasi as lokasi_saldo", "lokasi_saldo.id = acc_trans_detail.m_lokasi_id")
//            ->customWhere("LENGTH(debit) > 0 AND debit != 0", "AND")
            ->andWhere('date(acc_trans_detail.tanggal)', '>=', $tanggal_start)
            ->andWhere('date(acc_trans_detail.tanggal)', '<=', $tanggal_end)
            ->orderBy("acc_trans_detail.tanggal ASC, acc_trans_detail.kode ASC, acc_trans_detail.id ASC");
    $trans_detail = $db->findAll();

//    pd($trans_detail);

    foreach ($trans_detail as $key => $value) {
        $date = date("F", strtotime($value->tanggal));
        if (isset($arr['PENERIMAAN']['detail'][$value->idLokasiSaldo]['detail'][$date]) && !empty($arr['PENERIMAAN']['detail'][$value->idLokasiSaldo]['detail'][$date])) {
            $arr['PENERIMAAN']['detail'][$value->idLokasiSaldo]['detail'][$date] += isset($value->debit) && !empty($value->debit) ? $value->debit : 0;
            $arr['PENERIMAAN']['detail'][$value->idLokasiSaldo]['total'] += isset($value->debit) && !empty($value->debit) ? $value->debit : 0;
            $arr['PENERIMAAN']['total'][$date] += isset($value->debit) && !empty($value->debit) ? $value->debit : 0;
            $arr['PENERIMAAN']['total2'] += isset($value->debit) && !empty($value->debit) ? $value->debit : 0;
        } else {
            $arr['PENERIMAAN']['detail'][$value->idLokasiSaldo]['detail'][$date] = isset($value->debit) && !empty($value->debit) ? $value->debit : 0;
            $arr['PENERIMAAN']['detail'][$value->idLokasiSaldo]['total'] = isset($value->debit) && !empty($value->debit) ? $value->debit : 0;
            $arr['PENERIMAAN']['total'][$date] = isset($value->debit) && !empty($value->debit) ? $value->debit : 0;
            $arr['PENERIMAAN']['total2'] = isset($value->debit) && !empty($value->debit) ? $value->debit : 0;
        }
        if (isset($arr['PENGELUARAN']['detail'][$value->idLokasiSaldo]['detail'][$date]) && !empty($arr['PENGELUARAN']['detail'][$value->idLokasiSaldo]['detail'][$date])) {
            $arr['PENGELUARAN']['detail'][$value->idLokasiSaldo]['detail'][$date] += isset($value->kredit) && !empty($value->kredit) ? $value->kredit : 0;
            $arr['PENGELUARAN']['detail'][$value->idLokasiSaldo]['total'] += isset($value->kredit) && !empty($value->kredit) ? $value->kredit : 0;
            $arr['PENGELUARAN']['total'][$date] += isset($value->kredit) && !empty($value->kredit) ? $value->kredit : 0;
            $arr['PENGELUARAN']['total2'] += isset($value->kredit) && !empty($value->kredit) ? $value->kredit : 0;
        } else {
            $arr['PENGELUARAN']['detail'][$value->idLokasiSaldo]['detail'][$date] = isset($value->kredit) && !empty($value->kredit) ? $value->kredit : 0;
            $arr['PENGELUARAN']['detail'][$value->idLokasiSaldo]['total'] = isset($value->kredit) && !empty($value->kredit) ? $value->kredit : 0;
            $arr['PENGELUARAN']['total'][$date] = isset($value->kredit) && !empty($value->kredit) ? $value->kredit : 0;
            $arr['PENGELUARAN']['total2'] = isset($value->kredit) && !empty($value->kredit) ? $value->kredit : 0;
        }
    }

//    pd($arr);
    $data['total_saldo_akhir'] = $data['total_saldo_awal'];
    $data['saldo_akhir'] = $data['saldo_awal'];
    foreach ($arr as $key => $value) {
        foreach ($value['detail'] as $k => $v) {
            foreach ($v['detail'] as $kk => $vv) {

                if ($key == 'PENERIMAAN') {
                    $data['saldo_akhir'][$kk] += isset($vv) ? $vv : 0;
                    $data['total_saldo_akhir'] += isset($vv) ? $vv : 0;
                } else {
                    $data['saldo_akhir'][$kk] -= isset($vv) ? $vv : 0;
                    $data['total_saldo_akhir'] -= isset($vv) ? $vv : 0;
                }
            }
        }
    }

//    $data['total_saldo_akhir'] = $data['total_saldo_awal'] + $arr['PENERIMAAN']['total2'] - $arr['PENGELUARAN']['total2'];
//    pd($arr);

    if (isset($params['export']) && $params['export'] == 1) {
        $view = twigView();
        $content = $view->fetch('laporan/rekapan_depo.html', [
            "data" => $data,
            "detail" => $arr,
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment;Filename=laporan-rekapan-depo.xls");
        echo $content;
    } elseif (isset($params['print']) && $params['print'] == 1) {
        $view = twigView();
        $content = $view->fetch('laporan/rekapan_depo.html', [
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
