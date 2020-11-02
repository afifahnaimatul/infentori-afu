<?php

$app->get("/l_pembelian_import_ppn/laporan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;


    //bulan
    $params['bulan_awal'] = $params['bulan_awal'] . "-01";
    $params['bulan_akhir'] = date("Y-m-t", strtotime($params['bulan_akhir']));

//    pd($params);
//    pd($params);
//    die;

    $pembelian = $db->select("inv_pembelian_det_biaya.*, inv_pembelian.pib, FROM_UNIXTIME(inv_pembelian.tanggal, '%d/%m/%Y') as tanggal, FROM_UNIXTIME(inv_pembelian.tanggal, '%M %Y') as bulan, CAST(acc_m_kontak.nama AS CHAR) as nama")
            ->from("inv_pembelian_det_biaya")
            ->join('LEFT JOIN', 'inv_pembelian', 'inv_pembelian.id = inv_pembelian_det_biaya.inv_pembelian_id')
            ->join('LEFT JOIN', 'acc_m_kontak', 'acc_m_kontak.id = inv_pembelian.acc_m_kontak_id')
            ->where("is_import", "=", 1)
            ->where("inv_pembelian.status", "!=", 'draft')
            ->where("inv_pembelian.tanggal", ">=", strtotime($params['bulan_awal']))
            ->where("inv_pembelian.tanggal", "<=", strtotime($params['bulan_akhir']))
            ->where("inv_pembelian_det_biaya.ppn", "!=", 0)
//            ->where("inv_pembelian.is_ppn", "=", $params['is_ppn'])
            ->orderBy("inv_pembelian_det_biaya.inv_pembelian_id, inv_pembelian_det_biaya.tanggal_ntpn")
            ->findAll();

//    pd($pembelian);

    $arr = [];
    $temp_id = 0;
    foreach ($pembelian as $key => $value) {
        $value->tanggal_ntpn = date("d/m/Y", strtotime($value->tanggal_ntpn));

//        if ($value->inv_pembelian_id != $temp_id) {
//            $temp_id = $value->inv_pembelian_id;
//        } else {
//            $value->tanggal = '';
//            $value->pib = '';
//        }

        $arr[strtoupper($value->bulan)][] = (array) $value;
    }

//    pd($arr);

    $data = [
        "periode" => date("F Y", strtotime($params['bulan_awal'])) . " - " . date("F Y", strtotime($params['bulan_akhir'])),
        "lokasi" => "PT. AMAK FIRDAUS UTOMO",
    ];
    $detail = $arr;

    if (isset($params['is_export']) && $params['is_export'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/pembelian_import_ppn.html", [
            'data' => $data,
            'detail' => $detail,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"Pembelian Import PPN (" . $data['periode'] . ").xls\"");
        echo $content;
    } elseif (isset($params['is_print']) && $params['is_print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/pembelian_import_ppn.html", [
            'data' => $data,
            'detail' => $detail,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, [
            'data' => $data,
            'detail' => $detail,
//            'totalPerbeli' => $totalPerBeli,
        ]);
    }
});
