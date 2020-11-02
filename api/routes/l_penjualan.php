<?php

$app->get('/l_penjualan/getPenjualan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    if (isset($params['bulan']) && !empty($params['bulan'])) {
        $bulan = date("m", strtotime($params['bulan']));
    }

    $params['sub_kategori'] = [];

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $params['sub_kategori'] = getChildId('inv_m_kategori', $params['kategori']);
    }

    if (isset($params['kategori']) && $params['kategori'] == 2) {
        $db->select("inv_penjualan.id,
                        SUM(inv_penjualan_det.jumlah * inv_penjualan_det.harga) as harga_total,
        inv_penjualan.tanggal,
        inv_penjualan.kode,
        inv_penjualan.acc_m_kontak_id,
        acc_m_kontak.nama as nama_pembeli,
        acc_m_kontak.npwp,
        inv_penjualan_det.inv_m_barang_id,
        inv_m_barang.nama,
        SUM(inv_penjualan_det.jumlah) as jumlah,
        MONTH(FROM_UNIXTIME(inv_penjualan.tanggal)) as bulan,
        inv_m_barang.inv_m_kategori_id,
        inv_m_kategori.nama as nama_kategori,
        inv_m_faktur_pajak.nomor as no_faktur")
                ->from("inv_penjualan")
                ->join("left join", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
                ->join("left join", "inv_penjualan_det", "inv_penjualan_det.inv_penjualan_id = inv_penjualan.id")
                ->join("left join", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
                ->join("left join", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
                ->join("left join", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
                ->where("is_draft", "=", 0)
                ->groupBy("inv_m_barang_id")
//                ->groupBy("inv_m_barang_id, MONTH(FROM_UNIXTIME(inv_penjualan.tanggal))")
                ->orderBy("inv_m_barang.nama");

        if (isset($bulan) && !empty($bulan)) {
            $db->where("MONTH(FROM_UNIXTIME(inv_penjualan.tanggal))", "=", $bulan);
        }

        if (!empty($params['sub_kategori'])) {
            $db->customWhere("inv_m_kategori.id IN(" . implode(", ", $params['sub_kategori']) . ")", "AND");
        } else {
            $db->andWhere("inv_m_kategori.id", "=", $params['kategori']);
        }

        if (isset($params['lokasi']) && !empty($params['lokasi'])) {
            $db->andWhere("inv_penjualan.acc_m_lokasi_id", "=", $params['lokasi']);
        }

        $barang_jadi = $db->findAll();

//        pd($barang_jadi);

        $qt_jadi = 0;
        $dpp_jadi = 0;
        foreach ($barang_jadi as $key => $val) {
            @$qt_jadi += $val->jumlah;
            @$dpp_jadi += $val->harga_total;
        }

        $models = [
            'jadi'        => $barang_jadi,
            'qt_jadi'     => $qt_jadi,
            'dpp_jadi'    => $dpp_jadi,
            'kategori'    => 'jadi'
        ];
    }

    if (isset($params['kategori']) && $params['kategori'] == 3) {
        $db->select("inv_penjualan.id,
                        SUM(inv_penjualan_det.jumlah * inv_penjualan_det.harga) as harga_total,
        inv_penjualan.tanggal,
        inv_penjualan.kode,
        inv_penjualan.acc_m_kontak_id,
        acc_m_kontak.nama as nama_pembeli,
        acc_m_kontak.npwp,
        inv_penjualan_det.inv_m_barang_id,
        inv_m_barang.nama,
        SUM(inv_penjualan_det.jumlah) as jumlah,
        MONTH(FROM_UNIXTIME(inv_penjualan.tanggal)) as bulan,
        inv_m_barang.inv_m_kategori_id,
        inv_m_kategori.nama as nama_kategori,
        inv_m_faktur_pajak.nomor as no_faktur")
                ->from("inv_penjualan")
                ->join("left join", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
                ->join("left join", "inv_penjualan_det", "inv_penjualan_det.inv_penjualan_id = inv_penjualan.id")
                ->join("left join", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
                ->join("left join", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
                ->join("left join", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
                ->where("is_draft", "=", 0)
                ->groupBy("inv_m_barang_id")
//                ->groupBy("inv_m_barang_id, MONTH(FROM_UNIXTIME(inv_penjualan.tanggal))")
                ->orderBy("inv_m_barang.nama");

        if (isset($bulan) && !empty($bulan)) {
            $db->where("MONTH(FROM_UNIXTIME(inv_penjualan.tanggal))", "=", $bulan);
        }

        if (!empty($params['sub_kategori'])) {
            $db->customWhere("inv_m_kategori.id IN(" . implode(", ", $params['sub_kategori']) . ")", "AND");
        } else {
            $db->andWhere("inv_m_kategori.id", "=", $params['kategori']);
        }

        if (isset($params['lokasi']) && !empty($params['lokasi'])) {
            $db->andWhere("inv_penjualan.acc_m_lokasi_id", "=", $params['lokasi']);
        }


        $barang_dagangan = $db->findAll();

        $qt_dagangan = 0;
        $dpp_dagangan = 0;
        foreach ($barang_dagangan as $key => $val) {
            @$qt_dagangan += $val->jumlah;
            @$dpp_dagangan += $val->harga_total;
        }

        $models = [
            'dagangan'        => $barang_dagangan,
            'qt_dagangan'     => $qt_dagangan,
            'dpp_dagangan'    => $dpp_dagangan,
            'kategori'        => 'dagangan'
        ];
    }

    if (!isset($params['kategori']) || empty($params['kategori'])) {

        $params['sub_kategori'] = getChildId('inv_m_kategori', 2);

        $db->select("inv_penjualan.id,
                        SUM(inv_penjualan_det.jumlah * inv_penjualan_det.harga) as harga_total,
        inv_penjualan.tanggal,
        inv_penjualan.kode,
        inv_penjualan.acc_m_kontak_id,
        acc_m_kontak.nama as nama_pembeli,
        acc_m_kontak.npwp,
         inv_penjualan_det.harga as harga_barang,
        inv_penjualan_det.inv_m_barang_id,
        inv_m_barang.nama,
        SUM(inv_penjualan_det.jumlah) as jumlah,
        MONTH(FROM_UNIXTIME(inv_penjualan.tanggal)) as bulan,
        inv_m_barang.inv_m_kategori_id,
        inv_m_kategori.nama as nama_kategori,
        inv_m_faktur_pajak.nomor as no_faktur")
                ->from("inv_penjualan")
                ->join("left join", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
                ->join("left join", "inv_penjualan_det", "inv_penjualan_det.inv_penjualan_id = inv_penjualan.id")
                ->join("left join", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
                ->join("left join", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
                ->join("left join", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
                ->where("is_draft", "=", 0)
                ->customWhere("inv_m_kategori.id IN(" . implode(', ', $params['sub_kategori']) . ")", "AND")
//      ->groupBy("inv_m_barang_id, MONTH(FROM_UNIXTIME(inv_penjualan.tanggal))")
                ->groupBy("inv_m_barang_id")
                ->orderBy("inv_m_barang.nama");

        if (isset($bulan) && !empty($bulan)) {
            $db->where("MONTH(FROM_UNIXTIME(inv_penjualan.tanggal))", "=", $bulan);
        }

        $barang_jadi = $db->findAll();

//        pd($barang_jadi);

        $qt_jadi = 0;
        $dpp_jadi = 0;
        foreach ($barang_jadi as $key => $val) {
            @$qt_jadi += $val->jumlah;
            @$dpp_jadi += $val->harga_total;
        }

        $db->select("inv_penjualan.id,
            SUM(inv_penjualan_det.jumlah * inv_penjualan_det.harga) as harga_total,
        inv_penjualan.tanggal,
        inv_penjualan_det.harga as harga_barang,
        inv_penjualan.kode,
        inv_penjualan.acc_m_kontak_id,
        acc_m_kontak.nama as nama_pembeli,
        acc_m_kontak.npwp,
        inv_penjualan_det.inv_m_barang_id,
        inv_m_barang.nama,
        SUM(inv_penjualan_det.jumlah) as jumlah,
        MONTH(FROM_UNIXTIME(inv_penjualan.tanggal)) as bulan,
        inv_m_barang.inv_m_kategori_id,
        inv_m_kategori.nama as nama_kategori,
        inv_m_faktur_pajak.nomor as no_faktur")
                ->from("inv_penjualan_det")
                ->join("left join", "inv_penjualan", "inv_penjualan_det.inv_penjualan_id = inv_penjualan.id")
                ->join("left join", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
                ->join("left join", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
                ->join("left join", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
                ->join("left join", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
                ->where("is_draft", "=", 0)
//                ->groupBy("acc_m_kontak.id")
                ->where("inv_m_kategori.id", "=", 3)
                ->groupBy("inv_m_barang_id")
                ->orderBy("inv_m_barang.nama");

        if (isset($bulan) && !empty($bulan)) {
            $db->where("MONTH(FROM_UNIXTIME(inv_penjualan.tanggal))", "=", $bulan);
        }

        if (isset($params['lokasi']) && !empty($params['lokasi'])) {
            $db->andWhere("inv_penjualan.acc_m_lokasi_id", "=", $params['lokasi']);
        }

        $barang_dagangan = $db->findAll();

//        echo json_encode($barang_dagangan);
//        die;

        $qt_dagangan = 0;
        $dpp_dagangan = 0;
        foreach ($barang_dagangan as $key => $val) {
            @$qt_dagangan += $val->jumlah;
            @$dpp_dagangan += $val->harga_total;
        }

        $models = [
            'jadi'          => $barang_jadi,
            'dagangan'      => $barang_dagangan,
            'qt_jadi'       => $qt_jadi,
            'qt_dagangan'   => $qt_dagangan,
            'qt_total'      => $qt_dagangan + $qt_jadi,
            'dpp_jadi'      => $dpp_jadi,
            'dpp_dagangan'  => $dpp_dagangan,
            'dpp_total'     => $dpp_dagangan + $dpp_jadi,
        ];
    }

    if ($params['is_export'] != 0 || $params['is_print'] != 0) {
        if (isset($params['kategori']) && $params['kategori'] == 2) {
            $data = [
                'jadi'        => $barang_jadi,
                'qt_jadi'     => $qt_jadi,
                'qt_total'    => 0,
                'dpp_jadi'    => $dpp_jadi,
                'dpp_total'   => 0,
                'bulan'       => $params['bulan'],
                'lokasi'      => isset($params['lokasi']) && !empty($params['lokasi']) ? $params['lokasi_nama'] : "PT. AMAK FIRDAUS UTOMO",
            ];
        } elseif (isset($params['kategori']) && $params['kategori'] == 3) {
            $data = [
                'dagangan'      => $barang_dagangan,
                'qt_dagangan'   => $qt_dagangan,
                'qt_total'      => 0,
                'dpp_dagangan'  => $dpp_dagangan,
                'dpp_total'     => 0,
                'bulan'         => $params['bulan'],
                'lokasi'        => isset($params['lokasi']) && !empty($params['lokasi']) ? $params['lokasi_nama'] : "PT. AMAK FIRDAUS UTOMO",
            ];
        } elseif (!isset($params['kategori']) || $params['kategori'] == "") {
            $data = [
                'jadi'          => $barang_jadi,
                'dagangan'      => $barang_dagangan,
                'qt_jadi'       => $qt_jadi,
                'qt_dagangan'   => $qt_dagangan,
                'qt_total'      => $qt_dagangan + $qt_jadi,
                'dpp_jadi'      => $dpp_jadi,
                'dpp_dagangan'  => $dpp_dagangan,
                'dpp_total'     => $dpp_dagangan + $dpp_jadi,
                'bulan'         => $params['bulan'],
                'lokasi'        => isset($params['lokasi']) && !empty($params['lokasi']) ? $params['lokasi_nama'] : "PT. AMAK FIRDAUS UTOMO",
            ];
        }
    }


    if (isset($params['is_export']) && $params['is_export'] == 1) {

      ob_start();
      $xls = PHPExcel_IOFactory::load("format_excel/rekap_jadi_dagang.xlsx");
      // get the first worksheet
      $sheet = $xls->getSheet(0);

      $sheet->getCell('A3')->setValue( date('F Y', strtotime($params['bulan'] )) );
      $index = 6;

      if( isset($data['jadi']) ){
        foreach ($data['jadi'] as $key => $value) {
          $value=(array)$value;
          $sheet->getCell('A' . $index)->setValue($value['nama']);
          $sheet->getCell('B' . $index)->setValue($value['jumlah']);
          $sheet->getCell('C' . $index)->setValue($value['harga_total']);

          $index++;
        }

        $sheet->getCell('A' . $index)->setValue('TOTAL BARANG JADI');
        $sheet->getCell('B' . $index)->setValue($data['qt_jadi']);
        $sheet->getCell('C' . $index)->setValue($data['dpp_jadi']);

        $sheet->getStyle('A'.$index.":C".$index)->applyFromArray(
          array(
            'fill' => array(
              'type'  => PHPExcel_Style_Fill::FILL_SOLID,
              'color' => array('rgb' => '00ff19')
            )
          )
        );
        $index++;
      }

      if( isset($data['dagangan']) ){
        foreach ($data['dagangan'] as $key => $value) {
          $value=(array)$value;
          $sheet->getCell('A' . $index)->setValue($value['nama']);
          $sheet->getCell('B' . $index)->setValue($value['jumlah']);
          $sheet->getCell('C' . $index)->setValue($value['harga_total']);

          $index++;
        }

        $sheet->getCell('A' . $index)->setValue('TOTAL BARANG DAGANGAN ');
        $sheet->getCell('B' . $index)->setValue($data['qt_dagangan']);
        $sheet->getCell('C' . $index)->setValue($data['dpp_dagangan']);

        $sheet->getStyle('A'.$index.":C".$index)->applyFromArray(
          array(
            'fill' => array(
              'type'  => PHPExcel_Style_Fill::FILL_SOLID,
              'color' => array('rgb' => 'ff6100')
            )
          )
        );
        $index++;
      }

      // Total ALL
      $sheet->getCell('A' . $index)->setValue('TOTAL BARANG JADI DAN DAGANGAN ');
      $sheet->getCell('B' . $index)->setValue($data['qt_total']);
      $sheet->getCell('C' . $index)->setValue($data['dpp_total']);

      $sheet->getStyle('B6:B' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
      $sheet->getStyle('C6:C' . $index)->getNumberFormat()->setFormatCode("#,##0.00");

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
      header("Content-Disposition: attachment;Filename=\"REKAP PENJUALAN BARANG JADI DAN DAGANGAN ". date("F Y", strtotime($params['bulan'])) .".xlsx\"");

      ob_end_clean();
      $writer->save('php://output');
      exit;

        $view = twigView();
        $content = $view->fetch("laporan/penjualan.html", [
            'data' => $data,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"Rekap Penjualan B. Jadi & Dagangan (" . $params['bulan'] . ").xls\"");
        echo $content;
    } elseif (isset($params['is_print']) && $params['is_print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/penjualan.html", [
            'data' => $data,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, $models);
    }
});
