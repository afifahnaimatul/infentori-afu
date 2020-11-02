<?php

$app->get('/l_retur_pembelian/listbarang', function ($request, $response) {
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

$app->get("/l_retur_pembelian/laporan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $params['startDate'] = $params['tanggal'] . "-01";
    $params['endDate'] = date("Y-m-t", strtotime($params['tanggal']));

    //stok setahun
    $db->select("
        inv_retur_pembelian_det.inv_m_barang_id,
        inv_retur_pembelian_det.jumlah_retur AS jumlah_keluar,
        inv_retur_pembelian_det.harga AS harga_keluar,
        inv_retur_pembelian.id AS inv_retur_pembelian_id,
        inv_retur_pembelian.kode,
        FROM_UNIXTIME(inv_retur_pembelian.tanggal, '%d/%m/%Y') as tanggal,
        inv_m_faktur_pajak.nomor as no_faktur,
        inv_m_barang.nama as barang,
        acc_m_kontak.nama as kontak,
        acc_m_kontak.npwp
    ")
    ->from("inv_retur_pembelian_det")
    ->join("LEFT JOIN", "inv_retur_pembelian", "
      inv_retur_pembelian.id = inv_retur_pembelian_det.inv_retur_pembelian_id
      ")
      // AND inv_retur_pembelian_det.jumlah > 0
    ->join("LEFT JOIN", "inv_m_barang", "inv_m_barang.id = inv_retur_pembelian_det.inv_m_barang_id")
    ->join("LEFT JOIN", "inv_pembelian", "inv_pembelian.id = inv_retur_pembelian.inv_pembelian_id")
    ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_pembelian.inv_m_faktur_pajak_id")
    ->join("LEFT JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_pembelian.acc_m_kontak_id")
    ->where("inv_retur_pembelian.tanggal", "<=", strtotime($params['endDate']))
    ->where("inv_retur_pembelian.tanggal", ">=", strtotime($params['startDate']))
    ->where("inv_retur_pembelian_det.jumlah_retur", "!=", 0);
//            ->customWhere("acc_m_kontak.npwp IS NOT NULL", "AND")
            // ->where("inv_kartu_stok.trans_tipe", "=", "inv_retur_pembelian_id");

    if (isset($params['m_customer_id']) && !empty($params['m_customer_id'])) {
        $db->andWhere('inv_pembelian.acc_m_kontak_id', '=', $params['m_customer_id']);
    }

    if (isset($params['acc_m_lokasi_id']) && !empty($params['acc_m_lokasi_id'])) {
        $db->andWhere('inv_pembelian.acc_m_lokasi_id', '=', $params['acc_m_lokasi_id']);
    }

    if (isset($params['barang_id']) && !empty($params['barang_id'])) {
        $db->andWhere('inv_retur_pembelian_det.inv_m_barang_id', '=', $params['barang_id']);
    }

    $kartu_stok = $db->orderBy("acc_m_kontak.id, inv_retur_pembelian.tanggal")->findAll();

//    echo json_encode($kartu_stok);
//    die;

    $data = [
        'total_kwt' => 0,
        'total_harga' => 0,
        'total_dpp' => 0,
        'total_ppn' => 0,
        'total' => 0,
        'startDate' => date("d/m/Y", strtotime($params['startDate'])),
        'endDate' => date("d/m/Y", strtotime($params['endDate'])),
        'lokasi' => 'PT. AMAK FIRDAUS UTOMO',
    ];

    $arr = [];
    $totalPerRetur = [];
    $no = 0;
    $index = 0;
    $total = 0;
    foreach ($kartu_stok as $key => $val) {
        if($val->jumlah_keluar != 0){
            $val->dpp = $val->jumlah_keluar * $val->harga_keluar;
            $val->ppn = round(10 / 100 * $val->dpp);
            $val->total = floatval($val->dpp + $val->ppn);

            if (empty($totalPerRetur[$val->inv_retur_pembelian_id])) {
                $no++;
                $val->no_urut = $no;

                $totalPerRetur[$val->inv_retur_pembelian_id]['dpp'] = $val->dpp;
                $totalPerRetur[$val->inv_retur_pembelian_id]['ppn'] = $val->ppn;
                $totalPerRetur[$val->inv_retur_pembelian_id]['total'] = $val->total;
            } else {
                $totalPerRetur[$val->inv_retur_pembelian_id]['dpp'] += $val->dpp;
                $totalPerRetur[$val->inv_retur_pembelian_id]['ppn'] += $val->ppn;
                $totalPerRetur[$val->inv_retur_pembelian_id]['total'] += $val->total;
            }

            $val->sum_dpp = $totalPerRetur[$val->inv_retur_pembelian_id]['dpp'];
            $val->sum_ppn = $totalPerRetur[$val->inv_retur_pembelian_id]['ppn'];
            $val->sum_total = $totalPerRetur[$val->inv_retur_pembelian_id]['total'];

            if (empty($kartu_stok[$key + 1]) || $kartu_stok[$key + 1]->inv_retur_pembelian_id != $val->inv_retur_pembelian_id) {
                $data['total_kwt'] += $val->jumlah_keluar;
                $data['total_harga'] += $val->harga_keluar;
                $data['total_dpp'] += $totalPerRetur[$val->inv_retur_pembelian_id]['dpp'];
                $data['total_ppn'] += $totalPerRetur[$val->inv_retur_pembelian_id]['ppn'];
                $data['total'] += $totalPerRetur[$val->inv_retur_pembelian_id]['total'];
            }


            // if ($index + 1 != count($kartu_stok)) {
            //     if ($val->kontak != $kartu_stok[$index + 1]->kontak) {
            //        $val->total2 = $val->total + $total;
            //        $total = 0;
            //        $arr[$index] = $val;
            //        $arr[$index + 1] = [];
            //        $index += 2;
            //        } else {
            //            $total += $val->total;
            //            $arr[$index] = $val;
            //            $index++;
            //        }
            // } else {
            //     $val->total2 = $val->total + $total;
            //     // $total = 0;
            //     $arr[$index] = $val;
            // }
        }
    }

    $detail = $kartu_stok;

    if (isset($params['export']) && $params['export'] == 1) {
      ob_start();
        $xls = PHPExcel_IOFactory::load("format_excel/retur_pembelian.xlsx");

        // get the first worksheet
        $sheet = $xls->getSheet(0);

        $sheet->getCell('A3')->setValue( date('F Y', strtotime($params['startDate'])) );
        $index = 6;

        $jumlahArray = count($detail)-1;

        foreach ($detail as $k => $v) {
            $v = (array) $v;
            if( $k == 0 || (isset($detail[$k-1]) && $v['inv_retur_pembelian_id'] != $detail[$k-1]->inv_retur_pembelian_id) ){
              $sheet->getCell('A' . $index)->setValue($v['no_urut']);
              $sheet->getCell('B' . $index)->setValue($v['tanggal']);
              $sheet->getCell('C' . $index)->setValue($v['kode']);
              $sheet->getCell('D' . $index)->setValue($v['no_faktur']);
              $sheet->getCell('E' . $index)->setValue($v['kontak']);
              $sheet->getCell('F' . $index)->setValue($v['npwp']);
            } else {
              $sheet->getCell('A' . $index)->setValue('');
              $sheet->getCell('B' . $index)->setValue('');
              $sheet->getCell('C' . $index)->setValue('');
              $sheet->getCell('D' . $index)->setValue('');
              $sheet->getCell('E' . $index)->setValue('');
              $sheet->getCell('F' . $index)->setValue('');
            }
            $sheet->getCell('G' . $index)->setValue($v['barang']);
            $sheet->getCell('H' . $index)->setValue($v['jumlah_keluar']);
            $sheet->getCell('I' . $index)->setValue($v['harga_keluar']);
            if( ($jumlahArray == $k) || (isset($detail[$k+1]) && $v['inv_retur_pembelian_id'] != $detail[$k+1]->inv_retur_pembelian_id) ){
              $sheet->getCell('J' . $index)->setValue($v['sum_dpp']);
              $sheet->getCell('K' . $index)->setValue($v['sum_ppn']);
              $sheet->getCell('L' . $index)->setValue($v['sum_total']);
            } else {
              $sheet->getCell('J' . $index)->setValue('');
              $sheet->getCell('K' . $index)->setValue('');
              $sheet->getCell('L' . $index)->setValue('');
            }

            $index++;
        }

        // Footer
        $sheet->getCell('L' . $index)->setValue('');
        $sheet->mergeCells("A".($index).":F".($index));
        $sheet->getCell('G' . $index)->setValue('TOTAL');
        $sheet->getCell('H' . $index)->setValue('');
        $sheet->getCell('I' . $index)->setValue('');
        $sheet->getCell('J' . $index)->setValue($data['total_dpp']);
        $sheet->getCell('K' . $index)->setValue($data['total_ppn']);
        $sheet->getCell('L' . $index)->setValue($data['total']);
        // Footer - END

        $sheet->getStyle('H6:H' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('I6:I' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('J6:J' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('K6:K' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('L6:L' . $index)->getNumberFormat()->setFormatCode("#,##0.00");

        $sheet->getStyle("A" . 6 . ":L" . $index)->applyFromArray(
          array(
              'borders' => array(
                  'allborders' => array(
                      'style' => PHPExcel_Style_Border::BORDER_THIN,
                  )
              )
          )
        );

        // $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel2007');
        $writer = new PHPExcel_Writer_Excel2007($xls);
       // header('Content-type: application/vnd.ms-excel');
       header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"RETUR PEMBELIAN ". date("F Y", strtotime($params['startDate'])) .".xlsx\"");
        // header("Content-Disposition: attachment;filename=export.xls");

        ob_end_clean();
        $writer->save('php://output');
        exit;


        // $view = twigView();
        // $content = $view->fetch("laporan/retur_pembelian.html", [
        //     'data' => $data,
        //     'detail' => $detail,
        //     'css' => modulUrl() . '/assets/css/style.css',
        // ]);
        // header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        // header("Content-Disposition: attachment;Filename=\"Retur Pembelian (" . date("d F Y", strtotime($params['startDate'])) . "-" . date("d F Y", strtotime($params['endDate'])) . ").xls\"");
        // echo $content;
    } elseif (isset($params['print']) && $params['print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/retur_pembelian.html", [
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
