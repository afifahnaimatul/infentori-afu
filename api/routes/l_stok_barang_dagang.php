<?php

$app->get("/l_stok_barang_dagang/laporan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $params['bulan_awal'] = $params['bulan'] . "-01";
    $params['bulan_akhir'] = date("Y-m-t", strtotime($params['bulan_awal']));

    //saldo_awal
    $db->select("inv_m_barang_id, jumlah_masuk, jumlah_keluar, trans_tipe, trans_id, inv_kartu_stok.kode")
            ->from("inv_kartu_stok")
            ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
            ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->where("tanggal", "<", strtotime($params['bulan_awal']));
//            ->where("acc_m_lokasi_id", "=", $params['lokasi']);

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $saldo_awal = $db->findAll();

    $arr_saldo_awal = [];
    foreach ($saldo_awal as $key => $val) {
        if (isset($arr_saldo_awal[$val->inv_m_barang_id])) {
            $arr_saldo_awal[$val->inv_m_barang_id]['jumlah_masuk'] += intval($val->jumlah_masuk);
            $arr_saldo_awal[$val->inv_m_barang_id]['jumlah_keluar'] += intval($val->jumlah_keluar);
            $arr_saldo_awal[$val->inv_m_barang_id]['total'] += intval($val->jumlah_masuk) - intval($val->jumlah_keluar);
        } else {
            $arr_saldo_awal[$val->inv_m_barang_id]['jumlah_masuk'] = intval($val->jumlah_masuk);
            $arr_saldo_awal[$val->inv_m_barang_id]['jumlah_keluar'] = intval($val->jumlah_keluar);
            $arr_saldo_awal[$val->inv_m_barang_id]['total'] = intval($val->jumlah_masuk) - intval($val->jumlah_keluar);
        }
    }

    //saldo_periode
    $db->select("inv_m_barang_id, jumlah_masuk, jumlah_keluar, trans_tipe, trans_id, inv_kartu_stok.kode, jenis_kas")
            ->from("inv_kartu_stok")
            ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
            ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->where("tanggal", ">=", strtotime($params['bulan_awal']))
            ->where("tanggal", "<=", strtotime($params['bulan_akhir']));
//            ->where("acc_m_lokasi_id", "=", $params['lokasi']);

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi']) && $params['lokasi'] != 1) {
        $db->where("acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $saldo_periode = $db->orderBy("inv_m_barang_id, inv_kartu_stok.id")->findAll();

//    pd($saldo_periode);

    $arr_saldo_periode = [];
    foreach ($saldo_periode as $key => $val) {
        $val->add = !empty($val->add) ? $val->add : 'ya';
        if ($val->trans_tipe == 'inv_penjualan_id' && $val->add == 'ya' && ($key + 1) != count($saldo_periode)) {
            if (strpos($saldo_periode[($key + 1)]->kode, "KRM") !== false) {

                $val->add = 'tidak';
                $saldo_periode[($key + 1)]->add = 'tidak';

//                echo $val->kode . "1";
//                die;
//                pd($saldo_periode);
            } else {
//                echo $val->kode . "2";
//                die;
                $val->add = 'ya';
            }
        }
        if ($val->add == 'ya') {
            if (isset($arr_saldo_periode[$val->inv_m_barang_id])) {
                $arr_saldo_periode[$val->inv_m_barang_id]['jumlah_masuk'] += intval($val->jumlah_masuk);
                $arr_saldo_periode[$val->inv_m_barang_id]['jumlah_keluar'] += intval($val->jumlah_keluar);
                $arr_saldo_periode[$val->inv_m_barang_id]['total'] += intval($val->jumlah_masuk) - intval($val->jumlah_keluar);
            } else {
                $arr_saldo_periode[$val->inv_m_barang_id]['jumlah_masuk'] = intval($val->jumlah_masuk);
                $arr_saldo_periode[$val->inv_m_barang_id]['jumlah_keluar'] = intval($val->jumlah_keluar);
                $arr_saldo_periode[$val->inv_m_barang_id]['total'] = intval($val->jumlah_masuk) - intval($val->jumlah_keluar);
            }
        }
    }

//    pd($arr_saldo_periode);
    //saldo_retur_penjualan
    $db->select("inv_m_barang_id, jumlah_masuk, jumlah_keluar")
            ->from("inv_kartu_stok")
            ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
            ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->where("tanggal", ">=", strtotime($params['bulan_awal']))
            ->where("tanggal", "<=", strtotime($params['bulan_akhir']))
//            ->where("acc_m_lokasi_id", "=", $params['lokasi'])
            ->where("trans_tipe", "=", "inv_retur_penjualan_id");

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $saldo_retur_penjualan = $db->findAll();

    $arr_saldo_retur_penjualan = [];
    foreach ($saldo_retur_penjualan as $key => $val) {
        if (isset($arr_saldo_retur_penjualan[$val->inv_m_barang_id])) {
            $arr_saldo_retur_penjualan[$val->inv_m_barang_id]['jumlah_masuk'] += intval($val->jumlah_masuk);
            $arr_saldo_retur_penjualan[$val->inv_m_barang_id]['total'] += intval($val->jumlah_masuk);
        } else {
            $arr_saldo_retur_penjualan[$val->inv_m_barang_id]['jumlah_masuk'] = intval($val->jumlah_masuk);
            $arr_saldo_retur_penjualan[$val->inv_m_barang_id]['total'] = intval($val->jumlah_masuk);
        }
    }

    //saldo_retur_pembelian
    $db->select("inv_m_barang_id, jumlah_masuk, jumlah_keluar")
            ->from("inv_kartu_stok")
            ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
            ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->where("tanggal", ">=", strtotime($params['bulan_awal']))
            ->where("tanggal", "<=", strtotime($params['bulan_akhir']))
//            ->where("acc_m_lokasi_id", "=", $params['lokasi'])
            ->where("trans_tipe", "=", "inv_retur_pembelian_id");

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $saldo_retur_pembelian = $db->findAll();

    $arr_saldo_retur_pembelian = [];
    foreach ($saldo_retur_penjualan as $key => $val) {
        if (isset($arr_saldo_retur_pembelian[$val->inv_m_barang_id])) {
            $arr_saldo_retur_pembelian[$val->inv_m_barang_id]['jumlah_keluar'] += intval($val->jumlah_keluar);
            $arr_saldo_retur_pembelian[$val->inv_m_barang_id]['total'] += intval($val->jumlah_keluar);
        } else {
            $arr_saldo_retur_pembelian[$val->inv_m_barang_id]['jumlah_keluar'] = intval($val->jumlah_keluar);
            $arr_saldo_retur_pembelian[$val->inv_m_barang_id]['total'] = intval($val->jumlah_keluar);
        }
    }

    $db->select("inv_m_barang.*")->from("inv_m_barang")->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")->where("inv_m_kategori.is_dijual", "=", "ya")->where("inv_m_barang.is_deleted", "=", 0);
    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("id", "=", $params['barang']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_kategori.id", "=", $params['kategori']);
    }

    $barang = $db->findAll();

    $data = [
        'total_qty_awal' => 0,
        'total_qty_jual' => 0,
        'total_qty_beli' => 0,
        'total_qty_akhir' => 0,
        'total_qty_akhir_retur' => 0,
        'total_qty_retur_pembelian' => 0,
        'bulan' => date("F", strtotime($params['bulan_awal'])),
        'tahun' => date("Y", strtotime($params['bulan_awal'])),
        'lokasi' => isset($params['lokasi_nama']) && !empty($params['lokasi_nama']) ? $params['lokasi_nama'] : "PT. AMAK FIRDAUS UTOMO",
    ];

    $arr = [];
    $index = 0;
    foreach ($barang as $key => $val) {
        $val->no = $key + 1;
        $val->qty_awal = isset($arr_saldo_awal[$val->id]) && !empty($arr_saldo_awal[$val->id]) ? $arr_saldo_awal[$val->id]['total'] : 0;
        $val->qty_beli = isset($arr_saldo_periode[$val->id]) && !empty($arr_saldo_periode[$val->id]) ? $arr_saldo_periode[$val->id]['jumlah_masuk'] : 0;
        $val->qty_jual = isset($arr_saldo_periode[$val->id]) && !empty($arr_saldo_periode[$val->id]) ? $arr_saldo_periode[$val->id]['jumlah_keluar'] : 0;

        $val->qty_retur_penjualan = isset($arr_saldo_retur_penjualan[$val->id]) && !empty($arr_saldo_retur_penjualan[$val->id]) ? $arr_saldo_retur_penjualan[$val->id]['total'] : 0;
        $val->qty_retur_pembelian = isset($arr_saldo_retur_pembelian[$val->id]) && !empty($arr_saldo_retur_pembelian[$val->id]) ? $arr_saldo_retur_pembelian[$val->id]['total'] : 0;

        $val->total_beli = $val->qty_beli * $val->harga_beli;
        $val->total_jual = intval($val->qty_jual) * intval($val->harga_jual);

        $val->qty_akhir = $val->qty_awal + $val->qty_beli - $val->qty_jual;
        $val->qty_akhir_retur = $val->qty_akhir - $val->qty_retur_pembelian;

        $data['total_qty_awal'] += $val->qty_awal;
        $data['total_qty_jual'] += $val->qty_jual;
        $data['total_qty_beli'] += $val->qty_beli;
        $data['total_qty_akhir'] += $val->qty_akhir;
        $data['total_qty_retur_pembelian'] += $val->qty_retur_pembelian;
        $data['total_qty_akhir_retur'] += $val->qty_akhir_retur;
        $arr[$index] = (array) $val;
        $index++;
//        if($index != 0 && $val->nama != $arr[$index-1]['nama']){
//            $arr[$index] = [];
//            $arr[$index+1] = (array) $val;
//            $index = $index+2;
//        }else{
//            $arr[$index] = (array) $val;
//            $index = $index+1;
//        }
    }


    $detail = $arr;
//    echo json_encode($detail);die;

    if (isset($params['is_export']) && $params['is_export'] == 1) {
        ob_start();
        $xls = PHPExcel_IOFactory::load("format_excel/stok_barang_dagang.xlsx");

        // get the first worksheet
        $sheet = $xls->getSheet(0);


        $sheet->getCell('A3')->setValue( date('F Y', strtotime($params['bulan_awal'])));
        $index = 6;

        $jumlahArray = count($detail)-1;

        foreach ($detail as $k => $v) {
            $v = (array) $v;
            if( $k == 0 || (isset($detail[$k-1])) ){
                $sheet->getCell('A' . $index)->setValue($v['no']);
                $sheet->getCell('B' . $index)->setValue($v['nama']);
                $sheet->getCell('C' . $index)->setValue($v['qty_awal']);
                $sheet->getCell('D' . $index)->setValue($v['qty_beli']);
                $sheet->getCell('E' . $index)->setValue($v['qty_jual']);
                $sheet->getCell('F' . $index)->setValue($v['qty_akhir']);
                $sheet->getCell('G' . $index)->setValue($v['qty_retur_pembelian']);
                $sheet->getCell('H' . $index)->setValue($v['qty_akhir_retur']);
            } else {
                $sheet->getCell('A' . $index)->setValue('');
                $sheet->getCell('B' . $index)->setValue('');
                $sheet->getCell('C' . $index)->setValue('');
                $sheet->getCell('D' . $index)->setValue('');
                $sheet->getCell('E' . $index)->setValue('');
                $sheet->getCell('F' . $index)->setValue('');
                $sheet->getCell('G' . $index)->setValue('');
                $sheet->getCell('H' . $index)->setValue('');
            }


            $index++;
        }

        // Footer
        $sheet->getCell('L' . $index)->setValue('');
        $sheet->mergeCells("A".($index).":C".($index));
        $sheet->getCell('C' . $index)->setValue($data['total_qty_awal']);
        $sheet->getCell('D' . $index)->setValue($data['total_qty_beli']);
        $sheet->getCell('E' . $index)->setValue($data['total_qty_jual']);
        $sheet->getCell('F' . $index)->setValue($data['total_qty_akhir']);
        $sheet->getCell('G' . $index)->setValue($data['total_qty_retur_pembelian']);
        $sheet->getCell('H' . $index)->setValue($data['total_qty_akhir_retur']);

        $index1 =  $index+2;
        $index2 =  $index+3;
        $index3 =  $index+4;
        $index4 =  $index+5;
        $index5 =  $index+6;
        $index6 =  $index+7;
        $index7 =  $index+8;

        $sheet->getCell('G' . $index1)->setValue( 'S.AWAL');
        $sheet->getCell('H' . $index1)->setValue($data['total_qty_awal']);
        $sheet->getCell('G' . $index2)->setValue('BELI');
        $sheet->getCell('H' . $index2)->setValue($data['total_qty_beli']);
        $sheet->getStyle("G" . $index2 . ":H" . $index2)->applyFromArray(
            array(
                'borders' => array(
                    'bottom' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                    )
                )
            )
        );

        $qtyJualBeli = $data['total_qty_awal'] + $data['total_qty_beli'];
        $sheet->getCell('G' . $index3)->setValue( '');
        $sheet->getCell('H' . $index3)->setValue($qtyJualBeli);
        $sheet->getCell('G' . $index4)->setValue('JUAL');
        $sheet->getCell('H' . $index4)->setValue($data['total_qty_jual']);
        $sheet->getStyle("G" . $index4 . ":H" . $index4)->applyFromArray(
            array(
                'borders' => array(
                    'bottom' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                    )
                )
            )
        );
        $sheet->getCell('G' . $index5)->setValue( '');
        $sheet->getCell('H' . $index5)->setValue($data['total_qty_akhir']);
        $sheet->getCell('G' . $index6)->setValue('RETUR');
        $sheet->getCell('H' . $index6)->setValue($data['total_qty_retur_pembelian']);
        $sheet->getCell('G' . $index7)->setValue('S.AKHIR');
        $sheet->getCell('H' . $index7)->setValue($data['total_qty_akhir_retur']);


//        $sheet->getCell('G' . $index+3)->setValue($data['total_qty_retur_pembelian']);
//        $sheet->getCell('H' . $index+3)->setValue($data['total_qty_akhir_retur']);
//        $sheet->getCell('I' . $index)->setValue('');
//        $sheet->getCell('J' . $index)->setValue($data['total_dpp']);
//        $sheet->getCell('K' . $index)->setValue($data['total_ppn']);
//        $sheet->getCell('L' . $index)->setValue($data['total']);
        // Footer - END

        $sheet->getStyle('H6:H' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('I6:I' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('J6:J' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('K6:K' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('L6:L' . $index)->getNumberFormat()->setFormatCode("#,##0.00");

        $sheet->getStyle("A" . 6 . ":H" . $index)->applyFromArray(
            array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                    )
                )
            )
        );


         /** PHP 5 */
//         header('Content-Type: application/vnd.ms-excel');
//         header("Content-Disposition: attachment;Filename=\"STOK BARANG DAGANG ". date("F Y", strtotime($params['bulan_awal'])) .".xls\"");
//         $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        /** PHP 7 */
        $writer = new PHPExcel_Writer_Excel2007($xls);
        // header('Content-type: application/vnd.ms-excel');
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"RETUR PEMBELIAN ". date("F Y", strtotime($params['startDate'])) .".xlsx\"");
        // header("Content-Disposition: attachment;filename=export.xls");

        ob_end_clean();
        $writer->save('php://output');
        exit;
//        $view = twigView();
//        $content = $view->fetch("laporan/stok_barang_dagang.html", [
//            'data' => $data,
//            'detail' => $detail,
//            'css' => modulUrl() . '/assets/css/style.css',
//        ]);
//        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
//        header("Content-Disposition: attachment;Filename=\"Stok Barang Dagang (" . $data['bulan'] . " " . $data['tahun'] . ").xlsx\"");
//        echo $content;
    } elseif (isset($params['is_print']) && $params['is_print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/stok_barang_dagang.html", [
            'data' => $data,
            'detail' => $detail,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, ['data' => $data, 'detail' => $detail]);
    }
});
