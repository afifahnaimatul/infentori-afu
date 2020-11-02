<?php

$app->get('/l_faktur_sederhana/laporan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;


//
//
//echo json_encode($params);
//die();
//    $barang_id = $params['barang_id'];
    $tgl_mulai = strtotime($params['startDate']);
    $tgl_selesai = strtotime($params['endDate']);



    /** Ambil Saldo Awal */
    $db->select('
            inv_penjualan_det.inv_m_barang_id,
            inv_penjualan.is_ppn,
            inv_penjualan.tanggal,
            inv_penjualan.id,
            inv_penjualan.acc_m_kontak_id,
            inv_penjualan.acc_m_lokasi_id,
            inv_penjualan_det.diskon,
            inv_penjualan_det.harga,
             inv_penjualan_det.jumlah,
             inv_penjualan.no_surat_jalan,
            inv_m_barang.nama as namaBarang,
            acc_m_kontak.nama as namaCustomer,
            acc_m_kontak.kota,
            acc_m_lokasi.nama as lokasi,
            acc_m_kontak.npwp,
          inv_m_faktur_pajak.nomor as no_faktur,
         inv_penjualan.no_surat_jalan
        ')
            ->from('inv_penjualan_det')
            ->leftJoin('inv_penjualan', 'inv_penjualan.id = inv_penjualan_det.inv_penjualan_id')
            ->leftJoin('inv_m_barang', 'inv_m_barang.id = inv_penjualan_det.inv_m_barang_id')
            ->leftJoin('inv_m_faktur_pajak', 'inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id')
            ->leftJoin('acc_m_kontak', 'acc_m_kontak.id = inv_penjualan.acc_m_kontak_id')
            ->leftJoin('acc_m_lokasi', 'acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id')
            ->where("inv_penjualan.is_draft", "=", 0)
            ->where("inv_penjualan_det.jumlah", ">", 0)
            ->orderBy("acc_m_lokasi.id, inv_penjualan.tanggal ASC, inv_penjualan.no_surat_jalan, inv_penjualan_det.id");

    if (isset($tgl_mulai) && isset($tgl_selesai)) {

        $db->andWhere('inv_penjualan.tanggal', '>=', $tgl_mulai)
                ->andWhere('inv_penjualan.tanggal', '<=', $tgl_selesai);
    }
    if (isset($params['m_customer_id']) && !empty($params['m_customer_id'])) {

        $db->andWhere('inv_penjualan.acc_m_kontak_id', '=', $params['m_customer_id']);
    }
    if (isset($params['acc_m_lokasi_id']) && !empty($params['acc_m_lokasi_id'])) {

        $db->andWhere('inv_penjualan.acc_m_lokasi_id', '=', $params['acc_m_lokasi_id']);
    }
    if (isset($params['barang_id']) && !empty($params['barang_id'])) {

        $db->andWhere('inv_penjualan_det.inv_m_barang_id', '=', $params['barang_id']);
    }


    $data = $db->findAll();
//echo json_encode($data);
//die();
    $hasil = [];
    $hitung = [];
    $semua = [];

    $index = 0;
    $id = 0;
    foreach ($data as $key => $val) {
        $id = $val->acc_m_lokasi_id;
        $val->type = '';
        if ($val->is_ppn == 1) {
            $val->type = 'std';
        }

        $data[$key]->harga_satuan = $val->harga - $val->diskon;
        $data[$key]->tanggal = date("d/m/Y", $val->tanggal);
        $data[$key]->nilai = $data[$key]->harga_satuan * $val->jumlah;
        $hasil[$val->no_surat_jalan]->data[] = $val;
        $hitung[$val->no_surat_jalan]->data[] = $val;

        if (isset($hasil[$val->no_surat_jalan]->no)) {
            $hasil[$val->no_surat_jalan]->no = $index;
        } else {
            $index += 1;
            $hasil[$val->no_surat_jalan]->no = $index;
        }


        @$hitung[$val->no_surat_jalan]->data['DPP'] += round($data[$key]->nilai);
        $hitung[$val->no_surat_jalan]->data['PPN'] = round(0.1 * $hitung[$val->no_surat_jalan]->data['DPP']);
        $hitung[$val->no_surat_jalan]->data['DPP&PPN'] = round($hitung[$val->no_surat_jalan]->data['PPN'] + $hitung[$val->no_surat_jalan]->data['DPP']);

        if ($key + 1 != count($data)) {
            if ($data[$key]->tanggal != date("d/m/Y", $data[$key + 1]->tanggal)) {
                $data[$key]->DPP = $hitung[$val->no_surat_jalan]->data['DPP'];
                $data[$key]->PPN = $hitung[$val->no_surat_jalan]->data['PPN'];
                $data[$key]->DPP_PPN = $hitung[$val->no_surat_jalan]->data['DPP&PPN'];
                $data[$key]->panjang = count($hasil[$val->no_surat_jalan]->data);

                @$data[$key + 1]->DPP = 0;
                @$data[$key + 1]->PPN = 0;
                @$data[$key + 1]->DPP_PPN = 0;
                @$data[$key + 1]->panjang = 0;

                @$hitung[$val->no_surat_jalan]->data['DPP'] = 0;
                @$hitung[$val->no_surat_jalan]->data['PPN'] = 0;
                @$hitung[$val->no_surat_jalan]->data['DPP&PPN'] = 0;
//                @count($hasil[$val->no_surat_jalan]->data);
//                print_r($data);die;
            } else {
                $data[$key]->DPP = $hitung[$val->no_surat_jalan]->data['DPP'];
                $data[$key]->PPN = $hitung[$val->no_surat_jalan]->data['PPN'];
                $data[$key]->DPP_PPN = $hitung[$val->no_surat_jalan]->data['DPP&PPN'];
                $data[$key]->panjang = count($hasil[$val->no_surat_jalan]->data);
            }
        } else {
            $data[$key]->DPP = $hitung[$val->no_surat_jalan]->data['DPP'];
            $data[$key]->PPN = $hitung[$val->no_surat_jalan]->data['PPN'];
            $data[$key]->DPP_PPN = $hitung[$val->no_surat_jalan]->data['DPP&PPN'];
            $data[$key]->panjang = count($hasil[$val->no_surat_jalan]->data);
        }




        @$semua['KWT'] += $data[$key]->jumlah;
        @$semua['DPP'] += round($data[$key]->nilai);
        $semua['PPN'] = round(0.1 * $semua['DPP']);
        $semua['DPP_PPN'] = round($semua['PPN'] + $semua['DPP']);
    }
    if (isset($semua) && $semua != []) {

        $total[] = $semua;
    } else {
        $total = [];
    }

//    foreach ($hasil as $key => $val){
//        foreach ($hasil[$key] as $keys => $vals) {
//            $hasil[$vals-][] = $val;
//
//        }
//    }
//    pd($hasil);
    //
    $data_px = [
        'data' => $hasil,
        'total' => $total,
        'tgl_mulai' => date("d F Y", $tgl_mulai),
        'tgl_selesai' => date("d F Y", $tgl_selesai),
//        'barang' => $produk->nama,
        'lokasi' => 'PT. AMAK FIRDAUS UTOMO',
//        'harga_pokok' => $produk->harga_pokok,
//        'disiapkan' => date("d-m-Y, H:i");
    ];
//    echo json_encode($data_px);
//    die();




    if (isset($params['export']) && $params['export'] == 1) {

        $xls = PHPExcel_IOFactory::load("format_excel/faktur_standart_sederhana.xlsx");
        
// get the first worksheet
        $sheet = $xls->getSheet(0);
        $sheet->getCell('A3')->setValue("Periode : " . $data_px['tgl_mulai'] . " sampai " . $data_px['tgl_selesai']);

        $index = 7;

        foreach ($data_px['total'] as $key => $value) {
            $sheet->getCell('I' . ($index - 1))->setValue($value['KWT']);
            $sheet->getCell('L' . ($index - 1))->setValue($value['DPP']);
            $sheet->getCell('M' . ($index - 1))->setValue($value['PPN']);
            $sheet->getCell('N' . ($index - 1))->setValue($value['DPP_PPN']);
        }

//        pd($data_px['data']);
        foreach ($data_px['data'] as $key => $value) {
            foreach ($value->data as $k => $v) {
                $v = (array) $v;
                $sheet->getCell('A' . $index)->setValue(($k == 0) ? $value->no : '');
                $sheet->getCell('B' . $index)->setValue($v['tanggal']);
                $sheet->setCellValueExplicit('C' . $index, $v['no_surat_jalan'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->getCell('D' . $index)->setValue($v['lokasi']);
                $sheet->getCell('E' . $index)->setValue($v['type']);
                $sheet->setCellValueExplicit('F' . $index, $v['no_faktur'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->getCell('G' . $index)->setValue($v['namaCustomer']);
                $sheet->getCell('H' . $index)->setValue($v['namaBarang']);
                $sheet->getCell('I' . $index)->setValue($v['jumlah']);
                $sheet->getCell('J' . $index)->setValue($v['harga_satuan']);
                $sheet->getCell('K' . $index)->setValue($v['nilai']);

                if ($v['panjang'] == count($value->data)) {
                    $sheet->getCell('L' . $index)->setValue($v['DPP']);
                    $sheet->getCell('M' . $index)->setValue($v['PPN']);
                    $sheet->getCell('N' . $index)->setValue($v['DPP_PPN']);
                    $sheet->fromArray([], null, "A" . ($index + 1));
                    $index += 2;
                } else {
                    $sheet->getCell('L' . $index)->setValue('');
                    $sheet->getCell('M' . $index)->setValue('');
                    $sheet->getCell('N' . $index)->setValue('');

                    $index++;
                }
            }
        }

        $sheet->getStyle('J6:J' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('K6:K' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('L6:L' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('M6:M' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('N6:O' . $index)->getNumberFormat()->setFormatCode("#,##0.00");

        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel2007');
//        pd($writer);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"Faktur Std&Sederhana (" . $data_px['tgl_mulai'] . "-" . $data_px['tgl_selesai'] . ").xlsx\"");
        $writer->save('php://output');
    } elseif (isset($params['print']) && $params['print'] == 1) {

//        pd($data_px['data']);

        $page = 1;
        $limit = 39;
        $index = 0;
        $no = 1;
        $fix = [];
        foreach ($data_px['data'] as $key => $value) {
//            foreach ($value as $keys => $values) {
            if (count($value->data) > $limit) {
                $page += 1;
                $limit = 45;
                $index = 0;
            }
            foreach ($value->data as $k => $v) {
                if ($k + 1 == count($value->data)) {
                    $fix[$page]['data'][$index] = $v;
                    $fix[$page]['data'][$index + 1] = ['class' => 'empty'];
                    $fix[$page]['data'][$index]->tampilkan = true;
                    $fix[$page]['data'][$index]->no = $k == 0 ? $no : '';
                    $index += 2;
                    $limit -= 2;
                } else {
                    $fix[$page]['data'][$index] = $v;
                    $fix[$page]['data'][$index]->no = $k == 0 ? $no : '';
                    $index += 1;
                    $limit -= 1;
                }
            }
            $no += 1;
//            }
        }

//        pd($fix);

        $view = twigView();
        $content = $view->fetch('laporan/faktur_sederhana_fix.html', [
            "data" => $data_px,
            "detail" => $fix,
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
//        echo json_encode($data_px);die;
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, $data_px
        );
    }
});

$app->get('/l_faktur_sederhana/listbarang', function ($request, $response) {
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
?>