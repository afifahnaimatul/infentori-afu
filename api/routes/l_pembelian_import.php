<?php

$app->get("/l_pembelian_import/laporan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    //bulan
    $params['bulan_awal'] = $params['bulan_awal'] . "-01";
    $params['bulan_akhir'] = date("Y-m-t", strtotime($params['bulan_akhir']));

    $pembelian = $db->select("
      inv_pembelian.*,
        SUM(inv_pembelian_det_biaya.ppn) as ppn,
        SUM(inv_pembelian_det_biaya.pph22) as pph22,
        SUM(inv_pembelian_det_biaya.bea_masuk) as bea_masuk,
        SUM(inv_pembelian_det_biaya.denda_pabean) as denda_pabean,
        FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') as tanggal,
        FROM_UNIXTIME(inv_pembelian.tanggal, '%b') as bulan,
        CAST(acc_m_kontak.nama AS CHAR) as nama
      ")
    ->from("inv_pembelian")
    ->join('LEFT JOIN', 'inv_pembelian_det_biaya', 'inv_pembelian_det_biaya.inv_pembelian_id = inv_pembelian.id')
    ->join('LEFT JOIN', 'acc_m_kontak', 'acc_m_kontak.id = inv_pembelian.acc_m_kontak_id')
    ->where("is_import", "=", 1)
    ->andWhere("inv_pembelian.tanggal", ">=", strtotime($params['bulan_awal']))
    ->andWhere("inv_pembelian.tanggal", "<=", strtotime($params['bulan_akhir']))
    ->andWhere("inv_pembelian.status", "!=", 'draft')
    ->groupBy("inv_pembelian.id")
    ->orderBy("inv_pembelian.tanggal ASC")
    ->findAll();

    $listPembelianImportID = [];
    if( !empty($pembelian) ){
      foreach ($pembelian as $key => $value) {
        $listPembelianImportID[] = $value->id;
      }

      $listPembelianImportID = implode(",", $listPembelianImportID);
    }

    $detail_pembelian = $db->select("
      inv_pembelian_det.*,
      inv_m_barang.nama
    ")
    ->from("inv_pembelian_det")
    ->join("LEFT JOIN", "inv_m_barang", "inv_m_barang.id = inv_pembelian_det.inv_m_barang_id")
    ->join("JOIN", "inv_pembelian", "inv_pembelian.id = inv_pembelian_det.inv_pembelian_id")
    ->andWhere("inv_pembelian.tanggal", ">=", strtotime($params['bulan_awal']))
    ->andWhere("inv_pembelian.tanggal", "<=", strtotime($params['bulan_akhir']))
    ->andWhere("inv_pembelian.is_import", "=", 1)
    ->andWhere("inv_pembelian.status", "!=", 'draft')
    // ->orderBy("inv_pembelian_det.id")
    ->orderBy("inv_pembelian.tanggal ASC")
    ->findAll();

    $faktur_pembelian = $db->select("
      inv_pembelian_det_faktur.*,
      COALESCE(inv_pembelian_det_faktur.total, inv_pembelian.total) as total,
      inv_pembelian_det_faktur.tanggal as tanggal_faktur,
      FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') as tanggal,
      acc_m_kontak.nama as nama_ppn,
      acc_m_kontak2.nama as nama_non_ppn,
      inv_m_faktur_pajak.nomor
    ")
    ->from('inv_pembelian_det_faktur')
    ->join('LEFT JOIN', 'inv_pembelian', 'inv_pembelian.id = inv_pembelian_det_faktur.inv_m_faktur_pajak_id')
    ->join('LEFT JOIN', 'acc_m_kontak acc_m_kontak2', 'acc_m_kontak2.id = inv_pembelian_det_faktur.acc_m_kontak_id')
    ->join('LEFT JOIN', 'acc_m_kontak', 'acc_m_kontak.id = inv_pembelian.acc_m_kontak_id')
    ->join('LEFT JOIN', 'inv_m_faktur_pajak', 'inv_m_faktur_pajak.id = inv_pembelian.inv_m_faktur_pajak_id')
    ->customWhere("inv_pembelian_det_faktur.inv_pembelian_id IN(". $listPembelianImportID .")")
    // ->andWhere("inv_pembelian.status", "!=", 'draft')
    ->orderBy("inv_pembelian_det_faktur.id")
    ->findAll();

    $arr = [];
    $arr_total = [
        'qty'               => 0,
        'usdcif'            => 0,
        'usdkurs'           => 0,
        'rupiah'            => 0,
        'ppn'               => 0,
        'pph22'             => 0,
        'bm'                => 0,
        'denda'             => 0,
        'pelabuhan_ppn'     => 0,
        'pelabuhan_non_ppn' => 0,
        'ongkos_kapal_rp'   => 0,
        'ongkos_kapal_usd'  => 0,
    ];

    foreach ($pembelian as $key => $value) {
        $value->index = $key;
        $arr[$value->id] = (array) $value;

        $arr_total['ppn']     += $value->ppn;
        $arr_total['pph22']   += $value->pph22;
        $arr_total['bm']      += $value->bea_masuk;
        $arr_total['denda']   += $value->denda_pabean;
    }

    foreach ($detail_pembelian as $key => $value) {
        if (isset($arr[$value->inv_pembelian_id])) {
            $arr[$value->inv_pembelian_id]['detail'][] = (array) $value;

            $arr_total['qty']       += $value->jumlah;
            $arr_total['usdcif']    += $value->subtotal_edit / $value->kurs;
            $arr_total['usdkurs']   += $value->kurs;
            $arr_total['rupiah']    += $value->subtotal_edit;
        }
    }
    foreach ($faktur_pembelian as $key => $value) {
        if (isset($arr[$value->inv_pembelian_id])) {
            if ($value->type == 'ppn') {
                $arr[$value->inv_pembelian_id]['faktur_ppn'][] = (array) $value;
                $arr_total['pelabuhan_ppn'] += $value->total;

            } else if($value->type == 'non ppn'){
              $value_             = (array) $value;
              $value_['tanggal']  = $value->tanggal_faktur;

              $arr[$value->inv_pembelian_id]['faktur_non_ppn'][]  = $value_;
              $arr_total['pelabuhan_non_ppn']                     += $value->total;
            }
        }
    }


    foreach ($arr as $key => $value) {

        $detail           = isset($value['detail']) ? count($value['detail']) : 0;
        $faktur_ppn       = isset($value['faktur_ppn']) ? count($value['faktur_ppn']) : 0;
        $faktur_non_ppn   = isset($value['faktur_non_ppn']) ? count($value['faktur_non_ppn']) : 0;

        $arr[$key]['rowspan'] = max([$detail, $faktur_ppn, $faktur_non_ppn]);

        if ($faktur_ppn < $arr[$key]['rowspan']) {
            for ($i = 0; $i < $arr[$key]['rowspan']; $i++) {
                if (!isset($arr[$key]['faktur_ppn'][$i])) {
                    $arr[$key]['faktur_ppn'][$i] = [];
                }
            }
        }
        if ($faktur_non_ppn < $arr[$key]['rowspan']) {
            for ($i = 0; $i < $arr[$key]['rowspan']; $i++) {
                if (!isset($arr[$key]['faktur_non_ppn'][$i])) {
                    $arr[$key]['faktur_non_ppn'][$i] = [];
                }
            }
        }
        if ($detail < $arr[$key]['rowspan']) {
            for ($i = 0; $i < $arr[$key]['rowspan']; $i++) {
                if (!isset($arr[$key]['detail'][$i])) {
                    $arr[$key]['detail'][$i] = [];
                }
            }
        }

      // Total Ongkos Kirim / Kapal
      @$arr_total['ongkos_kapal_rp']   += !empty($value['ongkos_kapal_rp']) ? $value['ongkos_kapal_rp'] : 0;
      @$arr_total['ongkos_kapal_usd']  += !empty($value['ongkos_kapal_usd']) ? $value['ongkos_kapal_usd'] : 0;
    }

// pd([$arr_total, $arr]);

    $data = [
        "periode" => date("F Y", strtotime($params['bulan_awal'])) . "-" . date("F Y", strtotime($params['bulan_akhir'])),
        "total"   => $arr_total,
        "lokasi"  => "PT. AMAK FIRDAUS UTOMO",
    ];

    $indexData=0;
    $detail=[];
    foreach ($arr as $key => $value) {
      // code...
      $detail[] = $value;
      $indexData++;
    }

    if (isset($params['is_export']) && $params['is_export'] == 1) {

      ob_start();
      $xls = PHPExcel_IOFactory::load("format_excel/pembelian_import_pajak.xlsx");

      // alignment
      $text_center = array(
        'alignment' => array(
           'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
           'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        )
      );

      $text_left = array(
        'alignment' => array(
           'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
           'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        )
      );

      $text_right = array(
        'alignment' => array(
           'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
           'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
        )
      );

      $styleFont = array(
        'font'  => array(
          'bold'  => false,
          'color' => array('rgb' => '000000'),
          'size'  => 10,
          'name'  => 'Arial'
        )
      );

      // get the first worksheet
      $sheet = $xls->getSheet(0);
      $sheet->getCell('E4')->setValue( date('Y', strtotime($params['bulan_awal'])) );
      $index = 8;
      foreach ($detail as $key => $value) {
        foreach ($value['detail'] as $key2 => $value2) {
          if($key2 == 0){
            $sheet->mergeCells('B' . $index . ":B" . ($index-1+$value['rowspan']) );
            $sheet->mergeCells('C' . $index . ":C" . ($index-1+$value['rowspan']) );
            $sheet->mergeCells('D' . $index . ":D" . ($index-1+$value['rowspan']) );
            $sheet->mergeCells('E' . $index . ":E" . ($index-1+$value['rowspan']) );
            $sheet->getCell('B' . $index)->setValue($value['index']+1);
            $sheet->getCell('C' . $index)->setValue($value['nama']);
            $sheet->getCell('D' . $index)->setValue($value['bulan']);
            $sheet->getCell('E' . $index)->setValue($value['bulan']);
          }
          $sheet->getCell('F' . $index)->setValue($value2['nama']);
          if($key2 == 0){
            $sheet->mergeCells('G' . $index . ":G" . ($index-1+$value['rowspan']) );
            $sheet->mergeCells('H' . $index . ":H" . ($index-1+$value['rowspan']) );
            $sheet->getCell('G' . $index)->setValue($value['pib']);
            $sheet->getCell('H' . $index)->setValue( date("d/m/Y", strtotime($value['tanggal'])) );
          }

          $sheet->getCell('I' . $index)->setValue( $value2['jumlah'] );
          $sheet->getCell('J' . $index)->setValue( isset($value2['subtotal_edit']) ? ($value2['subtotal_edit'] / $value2['kurs']) : '' );
          $sheet->getCell('K' . $index)->setValue( $value2['kurs'] );
          $sheet->getCell('L' . $index)->setValue( $value2['subtotal_edit'] );
          if($key2 == 0){
            $sheet->mergeCells('M' . $index . ":M" . ($index-1+$value['rowspan']) );
            $sheet->mergeCells('N' . $index . ":N" . ($index-1+$value['rowspan']) );
            $sheet->mergeCells('O' . $index . ":O" . ($index-1+$value['rowspan']) );
            $sheet->mergeCells('P' . $index . ":P" . ($index-1+$value['rowspan']) );
            $sheet->getCell('M' . $index)->setValue( $value['ppn'] );
            $sheet->getCell('N' . $index)->setValue( $value['pph22'] );
            $sheet->getCell('O' . $index)->setValue( $value['bea_masuk'] );
            $sheet->getCell('P' . $index)->setValue( $value['denda_pabean'] );
          }

          $sheet->getCell('Q' . $index)->setValue( !empty($value['faktur_ppn'][$key2]['tanggal']) ? date("d/m/Y", strtotime($value['faktur_ppn'][$key2]['tanggal']) ) : '');
          $sheet->getCell('R' . $index)->setValue( $value['faktur_ppn'][$key2]['nama_ppn'] );
          $sheet->getCell('S' . $index)->setValue( $value['faktur_ppn'][$key2]['nomor'] );
          $sheet->getCell('T' . $index)->setValue( $value['faktur_ppn'][$key2]['total'] );

          $sheet->getCell('U' . $index)->setValue( !empty($value['faktur_non_ppn'][$key2]['tanggal']) ?  date("d/m/Y", strtotime($value['faktur_non_ppn'][$key2]['tanggal']) ) : '');
          $sheet->getCell('V' . $index)->setValue( $value['faktur_non_ppn'][$key2]['nama_non_ppn'] );
          $sheet->getCell('W' . $index)->setValue( $value['faktur_non_ppn'][$key2]['total'] );

          $sheet->getCell('Y' . $index)->setValue( $value['ongkos_kapal_rp'] );
          $sheet->getCell('X' . $index)->setValue( $value['ongkos_kapal_usd'] );
          $index++;
        }
      }

      // Total footer formula
      $sheet->getCell('I' . $index)->setValue('=SUM(I8:I'.($index-1).')');
      $sheet->getCell('J' . $index)->setValue('=SUM(J8:J'.($index-1).')');
      $sheet->getCell('K' . $index)->setValue('=SUM(K8:K'.($index-1).')');
      $sheet->getCell('L' . $index)->setValue('=SUM(L8:L'.($index-1).')');
      $sheet->getCell('M' . $index)->setValue('=SUM(M8:M'.($index-1).')');
      $sheet->getCell('N' . $index)->setValue('=SUM(N8:N'.($index-1).')');
      $sheet->getCell('O' . $index)->setValue('=SUM(O8:O'.($index-1).')');
      $sheet->getCell('P' . $index)->setValue('=SUM(P8:P'.($index-1).')');
      $sheet->getCell('T' . $index)->setValue('=SUM(T8:T'.($index-1).')');
      $sheet->getCell('W' . $index)->setValue('=SUM(W8:W'.($index-1).')');
      $sheet->getCell('X' . $index)->setValue('=SUM(X8:X'.($index-1).')');
      $sheet->getCell('Y' . $index)->setValue('=SUM(Y8:Y'.($index-1).')');

      // Format Nominal
      $sheet->getStyle('I8:I' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
      $sheet->getStyle('J8:J' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
      $sheet->getStyle('K8:K' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
      $sheet->getStyle('L8:L' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
      $sheet->getStyle('M8:M' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
      $sheet->getStyle('N8:N' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
      $sheet->getStyle('O8:O' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
      $sheet->getStyle('P8:P' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
      $sheet->getStyle('T8:T' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
      $sheet->getStyle('W8:W' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
      $sheet->getStyle('X8:X' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
      $sheet->getStyle('Y8:Y' . $index)->getNumberFormat()->setFormatCode("#,##0.00");

      // Set Alignment
      $sheet->getStyle("B1:B" . $index)->applyFromArray($text_center);
      $sheet->getStyle("C1:C" . $index)->applyFromArray($text_left);
      $sheet->getStyle("D8:E" . $index)->applyFromArray($text_center);
      $sheet->getStyle("F8:F" . $index)->applyFromArray($text_center);
      $sheet->getStyle("G8:G" . $index)->applyFromArray($text_center);
      $sheet->getStyle("H8:H" . $index)->applyFromArray($text_center);
      $sheet->getStyle("I8:P" . $index)->applyFromArray($text_right);
      $sheet->getStyle("Q8:Q" . $index)->applyFromArray($text_center);
      $sheet->getStyle("R8:R" . $index)->applyFromArray($text_left);
      $sheet->getStyle("S8:S" . $index)->applyFromArray($text_center);
      $sheet->getStyle("T8:T" . $index)->applyFromArray($text_right);
      $sheet->getStyle("U8:U" . $index)->applyFromArray($text_center);
      $sheet->getStyle("V8:V" . $index)->applyFromArray($text_left);
      $sheet->getStyle("W8:Y" . $index)->applyFromArray($text_right);

      $sheet->getStyle("E8:Y" . $index)->applyFromArray($styleFont);

      $sheet->getStyle("B" . 8 . ":Y" . $index)->applyFromArray(
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
      header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
      header("Content-Disposition: attachment;Filename=\"PEMBELIAN IMPORT ". date("Y", strtotime($params['bulan_awal'])) .".xlsx\"");

      ob_end_clean();
      $writer->save('php://output');
      exit;

        // $view = twigView();
        // $content = $view->fetch("laporan/pembelian_import.html", [
        //     'data' => $data,
        //     'detail' => $detail,
        //     'css' => modulUrl() . '/assets/css/style.css',
        // ]);
        // header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        // header("Content-Disposition: attachment;Filename=\"Pembelian Import (" . $data['periode'] . ").xls\"");
        // echo $content;
    } elseif (isset($params['is_print']) && $params['is_print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/pembelian_import.html", [
            'data'    => $data,
            'detail'  => $detail,
            'css'     => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, [
            'data'    => $data,
            'detail'  => $detail,
//            'totalPerbeli' => $totalPerBeli,
        ]);
    }
});
