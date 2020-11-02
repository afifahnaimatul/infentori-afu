<?php

$app->get('/l_retur_penjualan/listbarang', function ($request, $response) {
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

//    echo json_encode($barang);
//    die();
    return successResponse($response, $barang);
});

$app->get("/l_retur_penjualan/laporan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $params['startDate'] = $params['tanggal'] . "-01";
    $params['endDate'] = date("Y-m-t", strtotime($params['tanggal']));

    //stok setahun
    $db->select("
        inv_retur_penjualan_det.inv_m_barang_id,
        inv_retur_penjualan_det.jumlah_retur as jumlah_masuk,
        inv_retur_penjualan_det.harga_retur as harga_masuk,
        inv_retur_penjualan.catatan as no_surat,
        inv_retur_penjualan.no_nota,
        inv_retur_penjualan.no_faktur_pajak,
        inv_retur_penjualan.id as inv_retur_penjualan_id,
        FROM_UNIXTIME(inv_retur_penjualan.tanggal, '%d/%m/%Y') as tanggal,
        inv_m_barang.nama as barang,
        acc_m_kontak.nama as kontak,
        acc_m_kontak.npwp")
            ->from("inv_retur_penjualan_det")
            ->join("JOIN", "inv_retur_penjualan", "inv_retur_penjualan.id = inv_retur_penjualan_det.inv_retur_penjualan_id")
            ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_retur_penjualan_det.inv_m_barang_id")
            ->join("JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_retur_penjualan.acc_m_kontak_id")
            ->where("inv_retur_penjualan.tanggal", "<=", strtotime($params['endDate']))
            ->where("inv_retur_penjualan.tanggal", ">=", strtotime($params['startDate']));
//            ->customWhere("acc_m_kontak.npwp IS NOT NULL", "AND")
//            ->where("inv_kartu_stok.trans_tipe", "=", "inv_retur_penjualan_id");

    if (isset($params['m_customer_id']) && !empty($params['m_customer_id'])) {

        $db->andWhere('inv_penjualan.acc_m_kontak_id', '=', $params['m_customer_id']);
    }
    if (isset($params['acc_m_lokasi_id']) && !empty($params['acc_m_lokasi_id'])) {
        $db->andWhere('inv_retur_penjualan.acc_m_lokasi_id', '=', $params['acc_m_lokasi_id']);
    }
    if (isset($params['barang_id']) && !empty($params['barang_id'])) {
        $db->andWhere('inv_retur_penjualan_det.inv_m_barang_id', '=', $params['barang_id']);
    }

    $kartu_stok = $db->orderBy("acc_m_kontak.id, inv_retur_penjualan.tanggal")->findAll();

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
        'lokasi' => 'PT. AMAK FIRDAUS UTOMO'
    ];

    $arr = [];
    $totalPerRetur = [];
    $index = 0;
    $total = 0;
    $no = 0;
    foreach ($kartu_stok as $key => $val) {
        $val->dpp = $val->jumlah_masuk * $val->harga_masuk;
        $val->ppn = round(10 / 100 * $val->dpp);
        $val->total = floatval($val->dpp + $val->ppn);

        if (empty($totalPerRetur[$val->inv_retur_penjualan_id])) {
            $no++;
            $val->no_urut = $no;

            $totalPerRetur[$val->inv_retur_penjualan_id]['dpp'] = $val->dpp;
            $totalPerRetur[$val->inv_retur_penjualan_id]['ppn'] = $val->ppn;
            $totalPerRetur[$val->inv_retur_penjualan_id]['total'] = $val->total;
        } else {
            $totalPerRetur[$val->inv_retur_penjualan_id]['dpp'] += $val->dpp;
            $totalPerRetur[$val->inv_retur_penjualan_id]['ppn'] += $val->ppn;
            $totalPerRetur[$val->inv_retur_penjualan_id]['total'] += $val->total;
        }

        $val->sum_dpp   = $totalPerRetur[$val->inv_retur_penjualan_id]['dpp'];
        $val->sum_ppn   = $totalPerRetur[$val->inv_retur_penjualan_id]['ppn'];
        $val->sum_total = $totalPerRetur[$val->inv_retur_penjualan_id]['total'];

        if (empty($kartu_stok[$key + 1]) || $kartu_stok[$key + 1]->inv_retur_penjualan_id != $val->inv_retur_penjualan_id) {
            $data['total_kwt']    += $val->jumlah_masuk;
            $data['total_harga']  += $val->harga_masuk;
            $data['total_dpp']    += $totalPerRetur[$val->inv_retur_penjualan_id]['dpp'];
            $data['total_ppn']    += $totalPerRetur[$val->inv_retur_penjualan_id]['ppn'];
            $data['total']        += $totalPerRetur[$val->inv_retur_penjualan_id]['total'];
        }
    }

    $detail = $kartu_stok;

    if (isset($params['export']) && $params['export'] == 1) {
      ob_start();
      $xls = PHPExcel_IOFactory::load("format_excel/retur_penjualan.xlsx");

      // get the first worksheet
      $sheet = $xls->getSheet(0);
      $sheet->getCell('A3')->setValue( date('F Y', strtotime($params['startDate'])) );
      $index = 6;

      $jumlahArray = count($detail)-1;

      foreach ($detail as $k => $v) {
          $v = (array) $v;
          if( $k == 0 || (isset($detail[$k-1]) && $v['inv_retur_penjualan_id'] != $detail[$k-1]->inv_retur_penjualan_id) ){
            $sheet->getCell('A' . $index)->setValue($v['no_urut']);
            $sheet->getCell('B' . $index)->setValue($v['tanggal']);
            $sheet->getCell('C' . $index)->setValue($v['no_nota']);
            $sheet->getCell('D' . $index)->setValue($v['no_faktur_pajak']);
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
          $sheet->getCell('H' . $index)->setValue($v['jumlah_masuk']);
          $sheet->getCell('I' . $index)->setValue($v['harga_masuk']);
          if( ($jumlahArray == $k) || (isset($detail[$k+1]) && $v['inv_retur_penjualan_id'] != $detail[$k+1]->inv_retur_penjualan_id) ){
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
      $sheet->getCell('H' . $index)->setValue($data['total_kwt']);
      $sheet->getCell('I' . $index)->setValue('');
      $sheet->getCell('J' . $index)->setValue($data['total_dpp']);
      $sheet->getCell('K' . $index)->setValue($data['total_ppn']);
      $sheet->getCell('L' . $index)->setValue($data['total']);
      // Footer - END

      // $sheet->getStyle('H6:H' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
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
      header("Content-Disposition: attachment;Filename=\"RETUR PENJUALAN ". date("F Y", strtotime($params['startDate'])) .".xlsx\"");
      // header("Content-Disposition: attachment;filename=export.xls");

      ob_end_clean();
      $writer->save('php://output');
      exit;
        // $view = twigView();
        // $content = $view->fetch("laporan/retur_penjualan.html", [
        //     'data' => $data,
        //     'detail' => $detail,
        //     'css' => modulUrl() . '/assets/css/style.css',
        // ]);
        // header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        // header("Content-Disposition: attachment;Filename=\"Retur Penjualan (" . date("d F Y", strtotime($params['startDate'])) . "-" . date("d F Y", strtotime($params['endDate'])) . ").xlsx\"");
        // echo $content;
    } elseif (isset($params['print']) && $params['print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/retur_penjualan.html", [
            'data' => $data,
            'detail' => $detail,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, ['data' => $data, 'detail' => $detail, "totalPerRetur" => $totalPerRetur]);
    }
});
