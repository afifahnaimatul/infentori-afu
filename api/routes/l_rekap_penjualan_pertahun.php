<?php

$app->get("/l_rekap_penjualan_pertahun/laporan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $params['tanggal_awal'] = $params['tahun'] . "-01-01";
    $params['tanggal_akhir'] = $params['tahun'] . "-12-31";

    $list_bulan = [];
    for ($m = 1; $m <= 12; $m++) {
        $month = date('F', mktime(0, 0, 0, $m, 1, date('Y')));
        $list_bulan[] = $month;
    }

    $kategori = [3];
    $a = getChildId('inv_m_kategori', 2);
    $kategori = array_merge($kategori, $a);

    $db->select("
      inv_m_kategori.nama,
      inv_m_kategori.id,
      inv_m_kategori.parent_id,
      parent.nama as nama_parent
    ")
    ->from("inv_m_kategori")
    ->join("LEFT JOIN", "inv_m_kategori parent", "parent.id = inv_m_kategori.parent_id")
    ->join("JOIN", "inv_m_barang", "inv_m_barang.inv_m_kategori_id = inv_m_kategori.id")
    ->customWhere("inv_m_kategori.id IN (" . implode(", ", $kategori) . ")")
    ->groupBy("inv_m_kategori.nama")
    ->orderBy("inv_m_kategori.id DESC");
    $kategori = $db->findAll();

    $data_kategori = [];

    foreach ($kategori as $key => $value) {
        $kat      = isset($params['detail']) && !empty($params['detail']) ? $value->id : ($value->id != 3 ? $value->parent_id : $value->id);
        $kat_nama = isset($params['detail']) && !empty($params['detail']) ? $value->nama : ($value->id != 3 ? $value->nama_parent : $value->nama);

        $data_kategori[$kat_nama]['nama'] = $kat_nama;
    }

    $arr_tes = [];
    foreach ($list_bulan as $key => $val) {
        $arr_tes[$val] = [];
        $arr_tes[$val]['total'] = [];
        foreach ($kategori as $k => $v) {

            $kat = isset($params['detail']) && !empty($params['detail']) ? $v->id : ($v->parent_id != 0 ? $v->parent_id : $v->id);
            $kat_nama = isset($params['detail']) && !empty($params['detail']) ? $v->nama : ($v->parent_id != 0 ? $v->nama_parent : $v->nama);

            $arr_tes[$val]['stok'][$kat_nama] = [
                'nama' => $kat_nama
            ];
            $arr_tes[$val]['retur'][$kat_nama] = [
                'nama' => $kat_nama
            ];
        }
    }

    $arr = $arr_tes;
//    echo json_encode($arr);die();

    // Get id yg sudah proses akhir
    $tutupan_id = $db->select("id")
    ->from("inv_penjualan")
    ->customWhere("inv_proses_akhir_id IS NOT NULL")
    ->findAll();

    $listIDtutupan = [];
    if( !empty($tutupan_id) ){
      foreach ($tutupan_id as $key => $value) {
        $listIDtutupan[] = $value->id;
      }
      $listIDtutupan = implode(",", $listIDtutupan);
    }
    // Get id yg sudah proses akhir - End

    //stok setahun
    $db->select("
        inv_kartu_stok.trans_id,
        inv_kartu_stok.jenis_kas,
        inv_kartu_stok.jumlah_masuk,
        inv_kartu_stok.jumlah_keluar,
        inv_kartu_stok.inv_m_barang_id,
        FROM_UNIXTIME(inv_kartu_stok.tanggal, '%M') as tanggal,
        inv_m_barang.nama as  barang,
        inv_m_barang.inv_m_kategori_id,
        inv_kartu_stok.harga_keluar as harga_jual,
        inv_kartu_stok.harga_masuk,
        inv_m_kategori.parent_id,
        inv_m_kategori.nama as kategori,
        parent.nama as parent
      ")
    ->from("inv_kartu_stok")
    ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
    ->join("JOIN", "inv_m_kategori", "inv_m_barang.inv_m_kategori_id = inv_m_kategori.id")
    ->join("LEFT JOIN", "inv_m_kategori parent", "parent.id= inv_m_kategori.parent_id")
    ->where("inv_kartu_stok.tanggal", "<=", strtotime($params['tanggal_akhir']))
    ->where("inv_kartu_stok.tanggal", ">=", strtotime($params['tanggal_awal']))
    ->where("inv_kartu_stok.trans_tipe", "=", "inv_penjualan_id")
    ->where("inv_kartu_stok.trans_tipe", "=", "inv_penjualan_id")
    ->where("inv_m_kategori.is_dijual", "=", "ya");

    if( !empty($listIDtutupan) ){
      $db->customWhere("trans_id IN(". $listIDtutupan .")", "AND");
    }

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("inv_kartu_stok.acc_m_lokasi_id", "=", $params['lokasi']);
    }

    $db->orderBy("inv_kartu_stok.id ASC");
    $kartu_stok = $db->findAll();

    foreach ($kartu_stok as $key => $val) {

        $kat = isset($params['detail']) && !empty($params['detail']) ? $val->kategori : ($val->parent_id != 0 ? $val->parent : $val->kategori);

        // Jika koreksi maka keluarkan stoknya
        if($val->jenis_kas == "masuk"){

          $arr[$val->tanggal]['stok'][$kat]['qty']    -= intval($val->jumlah_masuk);
          $arr[$val->tanggal]['stok'][$kat]['total']  -= intval($val->jumlah_masuk) * intval($val->harga_jual);
          $arr[$val->tanggal]['total']['qty']         -= intval($val->jumlah_masuk);
          $arr[$val->tanggal]['total']['total']       -= intval($val->jumlah_masuk) * intval($val->harga_jual);
//            @$arr[$val->tanggal]['stok'][$kat]['detail'][$val->barang] -= intval($val->jumlah_masuk) ;
        } else {
          if (isset($arr[$val->tanggal]['stok'][$kat]['qty'])) {
            $arr[$val->tanggal]['stok'][$kat]['qty'] += intval($val->jumlah_keluar);
            $arr[$val->tanggal]['stok'][$kat]['total'] += intval($val->jumlah_keluar) * intval($val->harga_jual);
          } else {
            $arr[$val->tanggal]['stok'][$kat]['qty'] = intval($val->jumlah_keluar);
            $arr[$val->tanggal]['stok'][$kat]['total'] = intval($val->jumlah_keluar) * intval($val->harga_jual);
          }

          if (isset($arr[$val->tanggal]['total']['qty'])) {
            $arr[$val->tanggal]['total']['qty'] += intval($val->jumlah_keluar);
            $arr[$val->tanggal]['total']['total'] += intval($val->jumlah_keluar) * intval($val->harga_jual);
          } else {
            $arr[$val->tanggal]['total']['qty'] = intval($val->jumlah_keluar);
            $arr[$val->tanggal]['total']['total'] = intval($val->jumlah_keluar) * intval($val->harga_jual);
          }
//            @$arr[$val->tanggal]['stok'][$kat]['detail'][$val->barang] += intval($val->jumlah_keluar);
        }

    }

    // Getlist Retur Penjualan
    // Get tanggal terakhir tutup bulan
    $getDateTutupan = $db->find("SELECT MAX(tanggal_akhir) tanggal_akhir FROM inv_proses_akhir");
    $dateTutupan    = strtotime($params['tanggal_akhir']);
    if( !empty($getDateTutupan) ){
      $dateTutupan = strtotime($getDateTutupan->tanggal_akhir);
    }
    // Get tanggal terakhir tutup bulan - END
    $db->select("
        inv_kartu_stok.inv_m_barang_id,
        inv_kartu_stok.jumlah_masuk,
        inv_kartu_stok.harga_keluar,
        inv_kartu_stok.harga_masuk,
        FROM_UNIXTIME(inv_kartu_stok.tanggal, '%M') as tanggal,
        inv_m_barang.nama as  barang,
        inv_m_barang.inv_m_kategori_id,
        inv_m_kategori.parent_id,
        inv_m_kategori.nama as kategori,
        parent.nama as parent")
        // inv_m_barang.harga_jual,
            ->from("inv_kartu_stok")
            ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
            ->join("JOIN", "inv_m_kategori", "inv_m_barang.inv_m_kategori_id = inv_m_kategori.id")
            ->join("LEFT JOIN", "inv_m_kategori parent", "parent.id= inv_m_kategori.parent_id")
            ->where("inv_kartu_stok.tanggal", "<=", $dateTutupan )
            ->where("inv_kartu_stok.tanggal", ">=", strtotime($params['tanggal_awal']))
//            ->where("inv_kartu_stok.acc_m_lokasi_id", "=", $params['lokasi'])
            ->where("inv_kartu_stok.trans_tipe", "=", "inv_retur_penjualan_id")
            ->where("inv_m_kategori.is_dijual", "=", "ya");

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("inv_kartu_stok.acc_m_lokasi_id", "=", $params['lokasi']);
    }
    $kartu_stok_retur = $db->findAll();

    foreach ($kartu_stok_retur as $key => $val) {
        $kat = isset($params['detail']) && !empty($params['detail']) ? $val->kategori : ($val->parent_id != 0 ? $val->parent : $val->kategori);

        @$arr[$val->tanggal]['retur'][$kat]['qty']     += intval($val->jumlah_masuk);
        @$arr[$val->tanggal]['retur'][$kat]['total']   += intval($val->jumlah_masuk) * intval($val->harga_masuk);

        @$arr[$val->tanggal]['total']['qty']    -= intval($val->jumlah_masuk);
        @$arr[$val->tanggal]['total']['total']  -= intval($val->jumlah_masuk) * intval($val->harga_masuk);
    }

    $data = [
        'tahun' => $params['tahun'],
        'lokasi' => isset($params['lokasi_nama']) && !empty($params['lokasi_nama']) ? $params['lokasi_nama'] : "PT. AMAK FIRDAUS UTOMO",
        'kategori' => $data_kategori,
        'qty' => [],
    ];
    // Getlist Retur Penjualan - END

    foreach ($arr as $key => $val) {
        foreach ($val['stok'] as $k => $v) {
            if (isset($data['qty']['stok'][$k])) {
                $data['qty']['stok'][$k]['qty'] += isset($v['qty']) ? $v['qty'] : 0;
                $data['qty']['stok'][$k]['total'] += isset($v['total']) ? $v['total'] : 0;
            } else {
                $data['qty']['stok'][$k]['qty'] = isset($v['qty']) ? $v['qty'] : 0;
                $data['qty']['stok'][$k]['total'] = isset($v['total']) ? $v['total'] : 0;
            }

            if (isset($data['qty']['total']['stok_qty'])) {
                $data['qty']['total']['stok_qty'] += isset($v['qty']) ? $v['qty'] : 0;
                $data['qty']['total']['stok_total'] += isset($v['total']) ? $v['total'] : 0;
            } else {
                $data['qty']['total']['stok_qty'] = isset($v['qty']) ? $v['qty'] : 0;
                $data['qty']['total']['stok_total'] = isset($v['total']) ? $v['total'] : 0;
            }
        }
        foreach ($val['retur'] as $k => $v) {
            if (isset($data['qty']['retur'][$k])) {
                $data['qty']['retur'][$k]['qty'] += isset($v['qty']) ? $v['qty'] : 0;
                $data['qty']['retur'][$k]['total'] += isset($v['total']) ? $v['total'] : 0;
            } else {
                $data['qty']['retur'][$k]['qty'] = isset($v['qty']) ? $v['qty'] : 0;
                $data['qty']['retur'][$k]['total'] = isset($v['total']) ? $v['total'] : 0;
            }

            if (isset($data['qty']['total']['retur_qty'])) {
                $data['qty']['total']['retur_qty'] += isset($v['qty']) ? $v['qty'] : 0;
                $data['qty']['total']['retur_total'] += isset($v['total']) ? $v['total'] : 0;
            } else {
                $data['qty']['total']['retur_qty'] = isset($v['qty']) ? $v['qty'] : 0;
                $data['qty']['total']['retur_total'] = isset($v['total']) ? $v['total'] : 0;
            }
        }

        if (isset($data['qty']['total']['qty'])) {
            $data['qty']['total']['qty'] += isset($val['total']['qty']) ? $val['total']['qty'] : 0;
            $data['qty']['total']['total'] += isset($val['total']['total']) ? $val['total']['total'] : 0;
        } else {
            $data['qty']['total']['qty'] = isset($val['total']['qty']) ? $val['total']['qty'] : 0;
            $data['qty']['total']['total'] = isset($val['total']['total']) ? $val['total']['total'] : 0;
        }
    }

    $detail = $arr;

    if (isset($params['is_export']) && $params['is_export'] == 1) {
//        echo json_encode($data);die();
        ob_start();
        $xls = PHPExcel_IOFactory::load("format_excel/penjualan_perTahun_detail.xlsx");
        // get the first worksheet
        $sheet = $xls->getSheet(0);

        $sheet->getCell('A1')->setValue($data['lokasi'] );
        $sheet->getCell('A3')->setValue( 'Periode : '.$data['tahun']);
        $header = 5;
        $sub = 6;
        $indexAwal = 7;
        $index = 7;
        $x = 'C';
        /** HEADER */
        if( isset($data['kategori']) ) {
            foreach ($data['kategori'] as $key => $value) {
                $next = $x;
                $next++;
                $sheet->mergeCells($x.($header).":$next".($header));
                $sheet->getCell($x . $header)->setValue($value['nama']);
                $sheet->getCell($x . $sub)->setValue('KWT');
                $x++;
                $sheet->getCell($x . $sub)->setValue('RP');
                $x++;
            }
            $awalRetur = $x;
            foreach ($data['kategori'] as $key => $value) {
                $next = $x;
                $next++;
                $sheet->mergeCells($x.($header).":$next".($header));
                $sheet->getCell($x . $header)->setValue($value['nama']);
                $sheet->getCell($x . $sub)->setValue('KWT');
                $x++;
                $sheet->getCell($x . $sub)->setValue('RP');
                $x++;
            }
        }
        $akhirRetur = chr(ord($x) - 1);
        $total = $x;
        $x++;
        $sheet->mergeCells($total.($header).":$x".($header));
        $sheet->getCell($total . $header)->setValue('Total');
        $sheet->getCell($total . $sub)->setValue('KWT');
        $sheet->getCell($x . $sub)->setValue('RP');


        $sheet->getStyle("C5:$x". $header)->getFont()->setBold( true );
        $sheet->getStyle("C6:$x". $sub)->getFont()->setBold( true );
        $style = array(
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            )
        );
        $sheet->getStyle("C5:$x". $sub)->applyFromArray($style);
        $sheet->getStyle("C6:$x". $sub)->applyFromArray($style);

        $styleRetur = array(
            'font'  => array(
                'color' => array('rgb' => 'FF0000'),
            ));
        $sheet->getStyle("$awalRetur" . $header .":$akhirRetur". $header)->applyFromArray($styleRetur);
        $sheet->getStyle("$awalRetur" . $sub .":$akhirRetur". $sub)->applyFromArray($styleRetur);


        /** ISI */

        if( isset($detail) ){
            $no = 1;
            foreach ($detail as $key => $value) {
                $value=(array)$value;
                $sheet->getCell('A' . $index)->setValue($no);
                $sheet->getCell('B' . $index)->setValue($key);
                $detail = 'C';
                foreach ($value['stok'] as $keys => $vals){
                    $sheet->getCell($detail . $index)->setValue(isset($vals['qty'])?$vals['qty']:"");
                    $detail++;
                    $sheet->getCell($detail . $index)->setValue(isset($vals['total'])?$vals['total']:"");
                    $detail++;
                }
                foreach ($value['retur'] as $keys => $vals){
                    $sheet->getCell($detail . $index)->setValue(isset($vals['qty'])?$vals['qty']:"");
                    $detail++;
                    $sheet->getCell($detail . $index)->setValue(isset($vals['total'])?$vals['total']:"");
                    $detail++;
                }
                foreach ($value['total'] as $keys => $vals) {
                    $sheet->getCell($detail . $index)->setValue(isset($vals)?$vals:"");
                    $detail++;
                }


                $index++;
                $no++;
            }

        }
        if( isset($data['qty']) ){

                $detail = 'C';
                foreach ($data['qty']['stok'] as $keys => $vals){
                    $sheet->getCell($detail . $index)->setValue(isset($vals['qty'])?$vals['qty']:"");
                    $detail++;
                    $sheet->getCell($detail . $index)->setValue(isset($vals['total'])?$vals['total']:"");
                    $detail++;
                }
                foreach ($data['qty']['retur'] as $keys => $vals){
                    $sheet->getCell($detail . $index)->setValue(isset($vals['qty'])?$vals['qty']:"");
                    $detail++;
                    $sheet->getCell($detail . $index)->setValue(isset($vals['total'])?$vals['total']:"");
                    $detail++;
                }

                    $sheet->getCell($detail . $index)->setValue(isset($data['qty']['total']['qty'])?$data['qty']['total']['qty']:"");
                    $detail++;
                    $sheet->getCell($detail . $index)->setValue(isset($data['qty']['total']['total'])?$data['qty']['total']['total']:"");

        }
        $sheet->getStyle("A" . 5 . ":$x" . $index)->applyFromArray(
            array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                    )
                )
            )
        );
        $sheet->getStyle("C6:$x". $index)->getNumberFormat()->setFormatCode("#,##0.00");

        $sheet->getStyle("A" . 6 . ":C" . $index)->applyFromArray(
            array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                    )
                )
            )
        );

        $sheet->getStyle("$awalRetur" . $indexAwal .":$akhirRetur". $index)->applyFromArray($styleRetur);
        $sheet->getStyle("$awalRetur" . $indexAwal .":$akhirRetur". $index)->applyFromArray($styleRetur);

        /**  TOTAL BAWAH */
        if( isset($data['qty']) ) {
            $index++;
//            echo json_encode($detailBawah);die();
            foreach ($data['qty']['stok'] as $keys => $vals) {
                $detailBawah = chr(ord($detail) - 2);
                $index++;
                $sheet->getCell($detailBawah . $index)->setValue(isset($keys) ? $keys : "");
                $detailBawah++;
                $sheet->getCell($detailBawah . $index)->setValue(isset($vals['qty']) ? $vals['qty'] : "" );
                $detailBawah++;
                $sheet->getCell($detailBawah . $index)->setValue(isset($vals['total']) ? $vals['total'] : "");
            }
            $detailBawah = chr(ord($detail) - 2);
            $sheet->getStyle("$detailBawah" . $index . ":$detail" . $index)->applyFromArray(
                array(
                    'borders' => array(
                        'bottom' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                        )
                    )
                )
            );
            $index++;
            $sheet->getCell($detailBawah . $index)->setValue( "");
            $detailBawah++;
            $sheet->getCell($detailBawah . $index)->setValue(isset($data['qty']['total']['stok_qty'])?$data['qty']['total']['stok_qty']:"");
            $detailBawah++;
            $sheet->getCell($detailBawah . $index)->setValue(isset($data['qty']['total']['stok_total'])?$data['qty']['total']['stok_total']:"");

            $index++;

            $detailBawah = chr(ord($detail) - 2);
            $sheet->getCell($detailBawah . $index)->setValue( "Retur Barang Jadi");
            $detailBawah++;
            $sheet->getCell($detailBawah . $index)->setValue(isset($data['qty']['total']['retur_qty'])?$data['qty']['total']['retur_qty']:"");
            $sheet->getStyle("$detailBawah" . $index)->applyFromArray($styleRetur);
            $detailBawah++;
            $sheet->getCell($detailBawah . $index)->setValue(isset($data['qty']['total']['retur_total'])?$data['qty']['total']['retur_total']:"");
            $sheet->getStyle("$detailBawah" . $index)->applyFromArray($styleRetur);



            $detailBawah = chr(ord($detail) - 2);
            $sheet->getStyle("$detailBawah" . $index . ":$detail" . $index)->applyFromArray(
                array(
                    'borders' => array(
                        'bottom' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                        )
                    )
                )
            );

            $index++;

            $sheet->getCell($detailBawah . $index)->setValue( "");
            $detailBawah++;
            $sheet->getCell($detailBawah . $index)->setValue(isset($data['qty']['total']['qty'])?$data['qty']['total']['qty']:"");
            $detailBawah++;
            $sheet->getCell($detailBawah . $index)->setValue(isset($data['qty']['total']['total'])?$data['qty']['total']['total']:"");


        }
//

        $writer = new PHPExcel_Writer_Excel2007($xls);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"REKAP PENJUALAN PERTAHUN (DETAIL) PERIODE : ".$data['tahun'] .".xlsx\"");

        ob_end_clean();
        $writer->save('php://output');
        exit;
//        $view = twigView();
//        $content = $view->fetch("laporan/rekap_penjualan_pertahun.html", [
//            'data' => $data,
//            'detail' => $detail,
//            'css' => modulUrl() . '/assets/css/style.css',
//        ]);
//        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
//        header("Content-Disposition: attachment;Filename=\"Rekap Penjualan Pertahun (" . $data['tahun'] . ").xls\"");
//        echo $content;
    } elseif (isset($params['is_print']) && $params['is_print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/rekap_penjualan_pertahun.html", [
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
