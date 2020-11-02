<?php

$app->get("/l_rekap_penjualan/getRekapPenjualan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $bulan_awal = new DateTime($params['bulan_awal']);
    $bulan_awal->modify("first day of this month");
    $bulan_akhir = new DateTime($params['bulan_akhir']);
    $bulan_akhir->modify("first day of next month");

    $interval = DateInterval::createFromDateString('1 month');
    $period = new DatePeriod($bulan_awal, $interval, $bulan_akhir);

    $bulan = $listBulan = [];
    foreach ($period as $dt) {
        $bulan[$dt->format("Y-m")]['format'] = $dt->format("Y-n");
        $bulan[$dt->format("Y-m")]['nama'] = $dt->format("F") . " (kwt)";
        $bulan[$dt->format("Y-m") . '-b']['format'] = $dt->format("Y-n");
        $bulan[$dt->format("Y-m") . '-b']['nama'] = $dt->format("F") . " (Rp)";

        $listBulan[$dt->format("Y-m")]['format'] = $dt->format("Y-n");
    }

    $tanggal_awal = strtotime($params['bulan_awal']);
    $tanggal_akhir = date('Y-m-t', strtotime($params['bulan_akhir']));

    $db->select("
      SUM(jumlah) as total,
      SUM(inv_penjualan_det.jumlah * inv_penjualan_det.harga) as harga_total,
      inv_m_barang.nama,
      MONTH(FROM_UNIXTIME(inv_penjualan.tanggal)) as bulan,
      YEAR(FROM_UNIXTIME(inv_penjualan.tanggal)) as tahun,
      inv_penjualan.tanggal
    ")
    ->from("inv_penjualan_det")
    ->join("left join", "inv_penjualan", "inv_penjualan.id = inv_penjualan_det.inv_penjualan_id")
    ->join("left join", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
    ->where("inv_penjualan.is_draft", "=", 0)
    ->where("inv_penjualan.tanggal", ">=", $tanggal_awal)
    ->where("inv_m_barang.inv_m_kategori_id", "=", 3)
    ->andWhere("inv_penjualan.tanggal", "<=", strtotime($tanggal_akhir))
    ->customWhere("inv_penjualan.inv_proses_akhir_id > 0", "AND")
    ->groupBy("inv_m_barang_id, MONTH(FROM_UNIXTIME(inv_penjualan.tanggal))");

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("inv_penjualan.acc_m_lokasi_id", "=", $params['lokasi']);
    }

    $models = $db->orderBy("inv_penjualan_det.id")->findAll();

    $arr_detail     = [];
    $totalPerBulan  = [];
    foreach ($models as $val) {
        $arr_detail[$val->nama][$val->tahun . '-' . $val->bulan] = $val;
    }

    $arr_laporan = [];
    foreach ($models as $value) {
        $arr_laporan[$value->nama]['nama'] = $value->nama;
        $arr_laporan[$value->nama]['total'] = 0;
        $arr_laporan[$value->nama]['harga_total'] = 0;

        foreach ($listBulan as $b) {
            // Detail jumlah item dan harga per bulan
            if (isset($arr_detail[$value->nama][$b['format']])) {
                $arr_laporan[$value->nama]['detail'][$b['format']]        = $arr_detail[$value->nama][$b['format']]->total;
                $arr_laporan[$value->nama]['detail'][$b['format'] . "-b"] = $arr_detail[$value->nama][$b['format']]->harga_total;
                $arr_laporan[$value->nama]['total']       += $arr_laporan[$value->nama]['detail'][$b['format']];
                $arr_laporan[$value->nama]['harga_total'] += $arr_laporan[$value->nama]['detail'][$b['format'] . "-b"];
            } else {
                $arr_laporan[$value->nama]['detail'][$b['format']]        = 0;
                $arr_laporan[$value->nama]['detail'][$b['format'] . "-b"] = 0;
                $arr_laporan[$value->nama]['total']       += $arr_laporan[$value->nama]['detail'][$b['format']];
                $arr_laporan[$value->nama]['harga_total'] += $arr_laporan[$value->nama]['detail'][$b['format'] . "-b"];
            }

        }
    }

    // Total Per bulan
    $db->select("
      SUM(jumlah) as total,
      SUM(inv_penjualan_det.jumlah * inv_penjualan_det.harga) as harga_total,
      inv_m_barang.nama,
      MONTH(FROM_UNIXTIME(inv_penjualan.tanggal)) as bulan,
      YEAR(FROM_UNIXTIME(inv_penjualan.tanggal)) as tahun,
      inv_penjualan.tanggal
    ")
    ->from("inv_penjualan_det")
    ->join("left join", "inv_penjualan", "inv_penjualan.id = inv_penjualan_det.inv_penjualan_id")
    ->join("left join", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
    ->where("inv_penjualan.is_draft", "=", 0)
    ->where("inv_penjualan.tanggal", ">=", $tanggal_awal)
    ->where("inv_m_barang.inv_m_kategori_id", "=", 3)
    ->andWhere("inv_penjualan.tanggal", "<=", strtotime($tanggal_akhir))
    ->customWhere("inv_penjualan.inv_proses_akhir_id > 0", "AND")
    ->groupBy("
      YEAR(FROM_UNIXTIME(inv_penjualan.tanggal)),
      MONTH(FROM_UNIXTIME(inv_penjualan.tanggal)) DESC
      ");

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("inv_penjualan.acc_m_lokasi_id", "=", $params['lokasi']);
    }

    $getTotalPerbulan = $db->orderBy("inv_penjualan_det.id")->findAll();

    $totalPerbulan = [];
    $allTotal = [
      'kwt'     => 0,
      'total'   => 0,
    ];
    foreach ($getTotalPerbulan as $key => $value) {
      $totalPerbulan[date("Y-m", $value->tanggal)] = [
        'kwt'       => $value->total,
        'subtotal'  => $value->harga_total,
      ];
      $totalPerbulan[date("Y-m-b", $value->tanggal)] = [
        'kwt'       => $value->total,
        'subtotal'  => $value->harga_total,
      ];

      $allTotal['kwt']    += $value->total;
      $allTotal['total']  += $value->harga_total;
    }

    foreach ($bulan as $key => $value) {
      if( !isset($totalPerbulan[$key]) ){
        $totalPerbulan[$key] = [
          'kwt'       => 0,
          'subtotal'  => 0,
        ];
      }
    }
    // Total Per bulan - END
    ksort($totalPerbulan);
// pd($totalPerbulan);
    $data = [
        'allTotal'    => $allTotal,
        'bulan'       => $bulan,
        'list'        => $arr_laporan,
        'totalPerbulan' => $totalPerbulan,
        'bulan_awal'  => $params['bulan_awal'],
        'bulan_akhir' => $params['bulan_akhir'],
        'lokasi'      => isset($params['lokasi_nama']) && !empty($params['lokasi_nama']) ? $params['lokasi_nama'] : "PT. AMAK FIRDAUS UTOMO",
    ];
// echo json_encode($data);die();
    if (isset($params['is_export']) && $params['is_export'] == 1) {
        ob_start();
        $xls = PHPExcel_IOFactory::load("format_excel/rekap_penjualan_barang_dagang.xlsx");
        // get the first worksheet
        $sheet = $xls->getSheet(0);

        $sheet->getCell('A2')->setValue( 'REKAP PENJUALAN '.$data['bulan_awal']." s/d ".$data['bulan_akhir'] );
        $header = 5;
        $index = 6;
        $x = 'B';
        if( isset($data['bulan']) ) {
            foreach ($data['bulan'] as $key => $value) {
                $x++;
                $sheet->getCell($x . $header)->setValue('Jual '.$value['nama']);
            }
        }

        $end1 = $x;
        $end1++;
        $end2 = $end1;
        $end2++;
//        echo json_encode($end2);die();
        $sheet->getStyle("C5:$end2". $header)->getFont()->setBold( true );
        $style = array(
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            )
        );

        $sheet->getStyle("C5:$end2". $header)->applyFromArray($style);

        $sheet->getCell($end1 . $header)->setValue('Jumlah Jual Kwantum');
        $sheet->getCell($end2 . $header)->setValue('Jumlah Jual Harga');

        if( isset($data['list']) ){
            $no = 1;
            foreach ($data['list'] as $key => $value) {
                $value=(array)$value;
                $sheet->getCell('A' . $index)->setValue($no);
                $sheet->getCell('B' . $index)->setValue($value['nama']);
                $detail = 'C';
                foreach ($value['detail'] as $keys => $vals){
                    $sheet->getCell($detail . $index)->setValue($vals);
                    $detail++;
                }
                $sheet->getCell($detail . $index)->setValue($value['total']);
                $detail++;
                $sheet->getCell($detail . $index)->setValue($value['harga_total']);


                $index++;
                $no++;
            }

//            $sheet->getCell('A' . $index)->setValue('TOTAL BARANG JADI');
//            $sheet->getCell('B' . $index)->setValue($data['qt_jadi']);
//            $sheet->getCell('C' . $index)->setValue($data['dpp_jadi']);
//
//            $sheet->getStyle('A'.$index.":C".$index)->applyFromArray(
//                array(
//                    'fill' => array(
//                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
//                        'color' => array('rgb' => '00ff19')
//                    )
//                )
//            );
//            $index++;
        }
        $sheet->getStyle("A" . 5 . ":$end2" . $index)->applyFromArray(
            array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                    )
                )
            )
        );

        // Total ALL
        $sheet->getCell('B' . $index)->setValue('Total ');
        if(isset($data['totalPerbulan'])){
            $total = 'C';
            $noIndex = 1 ;
            foreach ($data['totalPerbulan'] as $key => $val){
                if($noIndex % 2 == 1) {
                    $sheet->getCell($total . $index)->setValue($val['kwt']);
                    $total++;
                }
                if($noIndex % 2 == 0 ){
                    $sheet->getCell($total . $index)->setValue($val['subtotal']);
                    $total++;

                }
                $noIndex++;
            }
            $sheet->getCell($total . $index)->setValue($data['allTotal']['kwt']);
            $total++;
            $sheet->getCell($total . $index)->setValue($data['allTotal']['total']);

        }
        $sheet->getStyle("C6:$end2". $index)->getNumberFormat()->setFormatCode("#,##0.00");

        $sheet->getStyle("A" . 6 . ":C" . $index)->applyFromArray(
            array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                    )
                )
            )
        );

        $writer = new PHPExcel_Writer_Excel2007($xls);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"REKAP PENJUALAN BARANG DAGANG ".$data['bulan_awal']." s/d ".$data['bulan_akhir'] .".xlsx\"");

        ob_end_clean();
        $writer->save('php://output');
        exit;
//        $view = twigView();
//        $content = $view->fetch("laporan/rekap_penjualan.html", [
//            'data' => $data,
//            'css' => modulUrl() . '/assets/css/style.css',
//        ]);
//        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
//        header("Content-Disposition: attachment;Filename=\"Rekap Penjualan (" . date('F Y', strtotime($tanggal_awal)) . "-" . date('F Y', strtotime($tanggal_akhir)) . ").xls\"");
//        echo $content;
    } elseif (isset($params['is_print']) && $params['is_print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/rekap_penjualan.html", [
            'data' => $data,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, [
          'allTotal'      => $allTotal,
          'data'          => $data,
          'bulan'         => $bulan,
          'totalPerbulan' => $totalPerbulan,
          'list'          => $arr_laporan
        ]);
    }
});
