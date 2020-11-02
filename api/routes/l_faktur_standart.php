<?php

$app->get('/l_faktur_standart/listbarang', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;



    $db->select('*')
            ->from('inv_m_barang')
            ->where('type', '=', 'barang')
            ->andWhere('nama', 'LIKE', $params['nama']);

    if (isset($params['produk_id']) && !empty($params['produk_id'])) {
        $db->andWhere('inv_m_barang.id', '=', $params['produk_id']);
    }
    $barang = $db->findAll();

    return successResponse($response, $barang);
});

$app->get("/l_faktur_standart/laporan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    //stok setahun
    $db->select("
        inv_penjualan_det.inv_m_barang_id,
        inv_penjualan_det.jumlah,
        inv_penjualan_det.harga,
        SUM(inv_penjualan_det.harga * inv_penjualan_det.jumlah) as dpp,
        FROM_UNIXTIME(inv_penjualan.tanggal, '%d/%m/%Y') as tanggal,
        inv_m_barang.nama as barang,
        inv_m_barang.harga_jual,
        acc_m_kontak.id as id_kontak,
        acc_m_kontak.nama as kontak,
        acc_m_kontak.npwp,
        inv_m_faktur_pajak.nomor as no_faktur,
        inv_penjualan.no_surat_jalan,
        inv_penjualan.is_ppn
        ")
            ->from("inv_penjualan_det")
            ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
            ->join("JOIN", "inv_penjualan", "inv_penjualan.id = inv_penjualan_det.inv_penjualan_id")
            ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
            ->join("JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
            ->customWhere("FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') <= '{$params['endDate']}'", "AND")
            ->customWhere("FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') >= '{$params['startDate']}'", "AND")
            ->where("inv_penjualan_det.jumlah", ">", 0);
//            ->customWhere("acc_m_kontak.npwp IS NOT NULL", "AND")

    if (isset($params['m_customer_id']) && !empty($params['m_customer_id'])) {

        $db->andWhere('inv_penjualan.acc_m_kontak_id', '=', $params['m_customer_id']);
    }
    if (isset($params['acc_m_lokasi_id']) && !empty($params['acc_m_lokasi_id'])) {
        $db->andWhere('inv_kartu_stok.acc_m_lokasi_id', '=', $params['acc_m_lokasi_id']);
    }
    if (isset($params['barang_id']) && !empty($params['barang_id'])) {
        $db->andWhere('inv_kartu_stok.inv_m_barang_id', '=', $params['barang_id']);
    }

    $kartu_stok = $db->groupBy('inv_penjualan.no_surat_jalan')->orderBy("acc_m_kontak.nama, inv_penjualan.no_surat_jalan, inv_penjualan.tanggal")->findAll();

    $data = [
        'total_sederhana' => 0,
        'total_dpp' => 0,
        'total_ppn' => 0,
        'total' => 0,
        'startDate' => date("d/m/Y", strtotime($params['startDate'])),
        'endDate' => date("d/m/Y", strtotime($params['endDate'])),
        'lokasi' => 'PT. AMAK FIRDAUS UTOMO',
    ];
//    pd($kartu_stok);

    $arr = [];
    $index = 0;
    $idx = 0;
    $no = 1;
    $total = 0;
    foreach ($kartu_stok as $key => $val) {
//        $val->dpp = $val->jumlah_keluar * $val->harga_keluar;
        $val->dpp = floatval($val->dpp);
        $val->ppn = floatval(10 / 100 * $val->dpp);
        $val->total = floatval($val->dpp + $val->ppn);

        if ($val->is_ppn == 0) {
            $data['total_sederhana'] += $val->total;
        } else {
            $data['total_dpp'] += $val->dpp;
            $data['total_ppn'] += $val->ppn;
            $data['total'] += $val->total;

            if ($index + 1 != count($kartu_stok)) {
//                echo $val->kontak . '/' . $kartu_stok[$index+1]->kontak . '/';
                if ($val->id_kontak != $kartu_stok[$key + 1]->id_kontak) {
                    $val->total2 = $val->total + $total;
                    $total = 0;
                    $arr[$index] = $val;
                    $arr[$index + 1] = [];
                    $index += 2;

                    $val->no = $no;
                    $no = 1;
                } else {
                    $total += $val->total;
                    $arr[$index] = $val;
                    $index++;

                    $val->no = $no;
                    $no += 1;
                }
            } else {
                $val->total2 = $val->total + $total;
//                $total = 0;
                $arr[$index] = $val;

                $val->no = $no;
                $no += 1;
            }
        }
    }

//    echo json_encode($kartu_stok);
//    die;


    $detail = $arr;

    if (isset($params['export']) && $params['export'] == 1) {

//        pd($detail);
//        pd($data);

        $xls = PHPExcel_IOFactory::load("format_excel/faktur_standart.xlsx");

// get the first worksheet
        $sheet = $xls->getSheet(0);
        $sheet->getCell('A3')->setValue("Periode : " . $data['startDate'] . " sampai " . $data['endDate']);

        $index = 7;

        $sheet->getCell('G' . ($index - 1))->setValue($data['total_dpp']);
        $sheet->getCell('H' . ($index - 1))->setValue($data['total_ppn']);
        $sheet->getCell('I' . ($index - 1))->setValue(($data['total']));
        $sheet->getCell('J' . ($index - 1))->setValue(($data['total']));
        $sheet->getCell('K' . ($index - 1))->setValue($data['total_sederhana']);
        $sheet->getCell('L' . ($index - 1))->setValue(($data['total'] + $data['total_sederhana']));

        foreach ($detail as $k => $v) {

            $v = (array) $v;
            if (!empty($v['no'])) {
                $sheet->getCell('A' . $index)->setValue($v['no']);
                $sheet->getCell('B' . $index)->setValue($v['tanggal']);
                $sheet->setCellValueExplicit('C' . $index, $v['no_surat_jalan'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('D' . $index, $v['no_faktur'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->getCell('E' . $index)->setValue($v['kontak']);
                $sheet->setCellValueExplicit('F' . $index, $v['npwp'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->getCell('G' . $index)->setValue($v['dpp']);
                $sheet->getCell('H' . $index)->setValue($v['ppn']);
                $sheet->getCell('I' . $index)->setValue($v['total']);
                $sheet->getCell('J' . $index)->setValue(isset($v['total2']) ? $v['total2'] : '');
            } else {
                $sheet->fromArray([], null, "A" . ($index));
            }
            $index++;
        }

        $sheet->getStyle('G6:G' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('H6:H' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('I6:I' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('J6:J' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('K6:K' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('L6:L' . $index)->getNumberFormat()->setFormatCode("#,##0.00");

        $sheet->getStyle("A" . 5 . ":L" . $index)->applyFromArray(
                    array(
                        'borders' => array(
                            'allborders' => array(
                                'style' => PHPExcel_Style_Border::BORDER_THIN,
                            )
                        )
                    )
            );

        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel2007');
//        pd($writer);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"Faktur Standart (" . date("d F Y", strtotime($params['startDate'])) . "-" . date("d F Y", strtotime($params['endDate'])) . ").xlsx\"");
        $writer->save('php://output');
    } elseif (isset($params['print']) && $params['print'] == 1) {

//        pd($detail);

        $page = 1;
        $limit = 34;
        $index = 0;
        $no = 1;
        $fix = [];
        foreach ($detail as $key => $value) {
            $fix[$page]['data'][$key] = $value;
            $limit -= 1;

            if ($limit == 0) {
                $page += 1;
                $limit = 41;
            }
        }

//        pd($fix);

        $view = twigView();
        $content = $view->fetch("laporan/faktur_standart_fix.html", [
            'data' => $data,
            'detail' => $fix,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, ['data' => $data, 'detail' => $detail]);
    }
});
