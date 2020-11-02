<?php

$app->get('/l_penjualan_perpembeli/laporan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $header['tanggal'] = false;
    $header['no'] = false;
    $header['depo'] = false;
    $header['tanggal_faktur_pajak'] = false;
    $header['tanggal_penyerahan'] = false;
    $header['no_surat_jalan'] = false;
    $header['no_nota'] = false;
    $header['no_faktur'] = false;
    $header['no_invoice'] = false;
    $header['pembeli'] = false;
    $header['npwp'] = false;
    $header['barang'] = false;
    $header['kwt'] = false;
    $header['harga_satuan'] = false;
    $header['nilai'] = false;
    $header['dpp'] = false;
    $header['ppn'] = false;
    $header['jumlah'] = false;
    $header['alamat'] = false;
    $header['tanggal_bayar'] = false;

    if (isset($params['header']) && $params['header'] != '{}') {
        if ($params['export'] == 1 || $params['print'] == 1) {
            $data = $params['header'];
        } else {
            $data = (array) json_decode($params['header']);
        }

        foreach ($data as $val) {
            $header[$val] = true;
        }

        $header['paraf'] = isset($params['tipe']) && $params['tipe'] == 'buku' ? true : false;
        $header['alamat'] = isset($params['tipe']) && $params['tipe'] == 'buku' ? false : true;
    } else {

        $header['no'] = true;
        $header['depo'] = true;
        $header['tanggal'] = true;
        $header['tanggal_faktur_pajak'] = false;
//        $header['tanggal_penyerahan'] = true;
        $header['no_surat_jalan'] = true;
//        $header['no_nota'] = true;
        $header['no_faktur'] = true;
        $header['no_invoice'] = true;
        $header['pembeli'] = true;
        $header['npwp'] = true;
        $header['barang'] = true;
        $header['kwt'] = true;
        $header['harga_satuan'] = true;
        $header['nilai'] = true;
        $header['dpp'] = true;
        $header['ppn'] = true;
        $header['jumlah'] = true;
        $header['paraf'] = isset($params['tipe']) && $params['tipe'] == 'buku' ? true : false;
        $header['alamat'] = isset($params['tipe']) && $params['tipe'] == 'buku' ? false : true;
//        $header['tanggal_bayar'] = true;
    }
    $panjang_header = 0;

    foreach ($header as $val) {
        if ($val == true) {
            @$panjang_header += 1;
        }
    }

    $panjang_pembatas = $panjang_header;
    if ($header['alamat'])
        $panjang_header = $panjang_header - 4;
    else
        $panjang_header = $panjang_header - 4;

//    $barang_id = $params['barang_id'];
    $tgl_mulai = $params['startDate'];
    $tgl_selesai = $params['endDate'];

    /** Ambil Saldo Awal */
    $db->select('
            inv_penjualan.kode,
            inv_penjualan_det.inv_m_barang_id,
            inv_penjualan_det.inv_penjualan_id,
            inv_penjualan.tanggal,
            inv_penjualan.id,
            inv_penjualan.no_invoice,
            inv_penjualan.acc_m_kontak_id,
            inv_penjualan.acc_m_lokasi_id,
            inv_penjualan_det.diskon,
            inv_penjualan_det.harga,
            inv_penjualan_det.jumlah,
            inv_m_barang.nama as namaBarang,
            inv_m_barang.kode as kodeBarang,
            acc_m_kontak.nama as namaCustomer,
            acc_m_kontak.alamat,
            acc_m_kontak.nik,
            inv_penjualan.no_surat_jalan,
            acc_m_kontak.npwp,
            acc_m_lokasi.nama as lokasi,
            inv_m_faktur_pajak.nomor as nomor_faktur,
            inv_penjualan.total as total_dpp
        ')
            ->from('inv_penjualan_det')
            ->join('join', 'inv_penjualan', 'inv_penjualan.id = inv_penjualan_det.inv_penjualan_id')
            ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
            ->join('join', 'inv_m_barang', 'inv_m_barang.id = inv_penjualan_det.inv_m_barang_id')
            ->join('join', 'acc_m_kontak', 'acc_m_kontak.id = inv_penjualan.acc_m_kontak_id')
            ->join('join', 'acc_m_lokasi', 'acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id')
            ->customWhere("inv_penjualan_det.jumlah > 0 OR inv_penjualan.status = 'dibatalkan'", "AND")
            ->orderBy("inv_penjualan.tanggal ASC, acc_m_lokasi.id, inv_penjualan.no_surat_jalan, inv_penjualan_det.id");

    if (isset($tgl_mulai) && isset($tgl_selesai)) {
        $db->customWhere("FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') >= '$tgl_mulai'", 'AND')
                ->customWhere("FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') <= '$tgl_selesai'", 'AND');
    }

    if (isset($params['m_customer_id']) && $params['m_customer_id'] != 0) {
        $db->andWhere('inv_penjualan.acc_m_kontak_id', '=', $params['m_customer_id']);
    }
// if (isset($params['acc_m_lokasi_id'])){
//
    //     $db->andWhere('inv_penjualan.acc_m_lokasi_id', '=', $params['acc_m_lokasi_id']);
//
    //
   // }
    if (isset($params['barang_id']) && $params['barang_id'] != 0) {
        $db->andWhere('inv_penjualan_det.inv_m_barang_id', '=', $params['barang_id']);
    }

    if (isset($params['jenis']) && !empty($params['jenis']) && $params['jenis'] != 'semua') {
        if ($params['jenis'] == 'std') {
            $db->customWhere("acc_m_kontak.npwp != ''", "AND");
        } else if ($params['jenis'] == 'sederhana') {
            $db->customWhere("acc_m_kontak.npwp = ''", "AND");
        }
    }

    $data = $db->findAll();

    $hasil = $hitung = $semua = $listDPP = $listPPNAll =  [];
    $id = $kode = $no = $index = 0;
    foreach ($data as $key => $val) {
        $val->a                   = $val->tanggal;
        $data[$key]->harga_satuan = $val->harga - $val->diskon;
        $data[$key]->tanggal      = date("d/m/Y", $val->tanggal);
        $data[$key]->nilai        = $data[$key]->harga_satuan * $val->jumlah;
        $hasil[$val->tanggal][$val->no_surat_jalan]->data[] = $val;

        $hitung[$val->tanggal][$val->no_surat_jalan]->data[] = $val;

        @$hitung[$val->tanggal][$val->no_surat_jalan]->data['DPP']    += round($data[$key]->nilai);

        // Tampung DPP
        $listDPP[$val->no_surat_jalan]['list'][]  = round($data[$key]->nilai);
        $listDPP[$val->no_surat_jalan]['ppn']     = round(array_sum($listDPP[$val->no_surat_jalan]['list']) * 10/100);
        // Tampung DPP - END

        $hitung[$val->tanggal][$val->no_surat_jalan]->data['PPN']     = round(0.1 * $hitung[$val->tanggal][$val->no_surat_jalan]->data['DPP']);
        $hitung[$val->tanggal][$val->no_surat_jalan]->data['DPP&PPN'] = round($hitung[$val->tanggal][$val->no_surat_jalan]->data['PPN'] + $hitung[$val->tanggal][$val->no_surat_jalan]->data['DPP']);

        $data[$key]->DPP      = $hitung[$val->tanggal][$val->no_surat_jalan]->data['DPP'];
        $data[$key]->PPN      = $hitung[$val->tanggal][$val->no_surat_jalan]->data['PPN'];
        $data[$key]->DPP_PPN  = $hitung[$val->tanggal][$val->no_surat_jalan]->data['DPP&PPN'];
        $data[$key]->panjang  = count($hasil[$val->tanggal][$val->no_surat_jalan]->data);

        if (isset($hasil[$val->tanggal][$val->no_surat_jalan]->no)) {
            $hasil[$val->tanggal][$val->no_surat_jalan]->no = $index;
        } else {
            $index += 1;
            $hasil[$val->tanggal][$val->no_surat_jalan]->no = $index;
        }

        @$semua['DPP']    += round($data[$key]->nilai);

        //template penjulaan
        $data[$key]->KD_JENIS_TRANSAKSI = !empty($val->nomor_faktur) ? substr($val->nomor_faktur, 0, 2) : '';
        $data[$key]->FG_PENGGANTI       = !empty($val->nomor_faktur) ? substr($val->nomor_faktur, 2, 1) : '';
        $data[$key]->NOMOR_FAKTUR       = !empty($val->nomor_faktur) ? substr(str_replace('-', '', str_replace('.', '', $val->nomor_faktur)), 3) : '';
        $data[$key]->NPWP               = !empty($val->npwp) ? str_replace('-', '', str_replace('.', '', $val->npwp)) : '000000000000000';
        $data[$key]->NIK                = $val->nik;
        $data[$key]->MASA_PAJAK         = date("m", $val->a);
        $data[$key]->TAHUN_PAJAK        = date("Y", $val->a);

        $data[$key]->JUMLAH_DPP         = $val->total_dpp;
        $data[$key]->JUMLAH_PPN         = round(0.1 * $val->total_dpp);
        $data[$key]->JUMLAH_DPP_PPN     = $data[$key]->JUMLAH_DPP + $data[$key]->JUMLAH_PPN;
    }

    // Get ALl PPN
    $allPPn = 0;
    foreach ($listDPP as $key => $value) {
      $allPPn += $value['ppn'];
    }

    $semua['PPN']     = $allPPn;
    $semua['DPP_PPN'] = $allPPn + $semua['DPP'];

    if (isset($semua) && $semua != []) {
        $total[] = $semua;
    } else {
        $total = [];
    }
    // Get ALl PPN - END

    $data_px = [
        'data'              => $hasil,
        'total'             => $total,
        'tgl_mulai'         => date("d/m/Y", strtotime($tgl_mulai)),
        'tgl_selesai'       => date("d/m/Y", strtotime($tgl_selesai)),
        'header'            => $header,
        'panjang_header'    => $panjang_header,
        'panjang_pembatas'  => $panjang_pembatas,
        'lokasi'            => "PT. AMAK FIRDAUS UTOMO",
    ];

    if (isset($params['export']) && $params['export'] == 1) {
        if (isset($params['path'])) {

          // create a new document
          // $xls = new PHPExcel();

            $xls = PHPExcel_IOFactory::load("import_data/template_template.xlsx");

            // get the first worksheet
            $sheet = $xls->getSheet(0);

            $index = 2;
            foreach ($data_px['data'] as $key => $value) {
                foreach ($value as $keys => $values) {
                    foreach ($values->data as $k => $v) {
                        $v = (array) $v;
                        $sheet->getCell('A' . $index)->setValue($v['KD_JENIS_TRANSAKSI']);
                        $sheet->getCell('B' . $index)->setValue($v['FG_PENGGANTI']);
                        $sheet->setCellValueExplicit('C' . $index, $v['NOMOR_FAKTUR'], PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->getCell('D' . $index)->setValue($v['MASA_PAJAK']);
                        $sheet->getCell('E' . $index)->setValue($v['TAHUN_PAJAK']);
                        $sheet->getCell('F' . $index)->setValue($v['tanggal']);
                        $sheet->setCellValueExplicit('G' . $index, $v['NPWP'], PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->getCell('H' . $index)->setValue($v['NIK']);
                        $sheet->getCell('I' . $index)->setValue($v['namaCustomer']);
                        $sheet->getCell('J' . $index)->setValue($v['alamat']);
                        $sheet->getCell('K' . $index)->setValue($v['JUMLAH_DPP']);
                        $sheet->setCellValue('L' . $index, '=K' . $index . '*10%');
                        $sheet->getCell('M' . $index)->setValue(0);
                        $sheet->getCell('N' . $index)->setValue('');
                        $sheet->getCell('O' . $index)->setValue(0);
                        $sheet->getCell('P' . $index)->setValue(0);
                        $sheet->getCell('Q' . $index)->setValue(0);
                        $sheet->getCell('R' . $index)->setValue(0);
                        $sheet->getCell('S' . $index)->setValue('');
                        $sheet->getCell('T' . $index)->setValue($v['kodeBarang']);
                        $sheet->getCell('U' . $index)->setValue($v['namaBarang']);
                        $sheet->getCell('V' . $index)->setValue($v['harga']);
                        $sheet->getCell('W' . $index)->setValue($v['jumlah']);
                        $sheet->setCellValue('X' . $index, '=V' . $index . '*W' . $index . '- Y' . $index);
                        $sheet->getCell('Y' . $index)->setValue($v['diskon']);
                        $sheet->setCellValue('Z' . $index, '=X' . $index);
                        $sheet->setCellValue('AA' . $index, '=Z' . $index . '*10%');
                        $sheet->getCell('AB' . $index)->setValue(0);
                        $sheet->getCell('AC' . $index)->setValue(0);

                        $index++;
                    }
                }
            }

            $sheet->getStyle('K2:K' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('L2:L' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('M2:M' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('O2:O' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('P2:P' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('Q2:Q' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('R2:R' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('V2:V' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('W2:W' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('X2:X' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('Y2:Y' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('Z2:Z' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('AA2:AA' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('AB2:AB' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('AC2:AC' . $index)->getNumberFormat()->setFormatCode("#,##0.00");

            $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel2007');
            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header("Content-Disposition: attachment;Filename=\"Template Penjualan (" . date('d F Y', strtotime($tgl_mulai)) . "-" . date('d F Y', strtotime($tgl_selesai)) . ").xlsx\"");
//          pd($writer);
            $writer->save('php://output');
        } else {
            $xls = PHPExcel_IOFactory::load("format_excel/copy_penjualan.xlsx");

// get the first worksheet
            $sheet = $xls->getSheet(0);
            $sheet->getCell('A3')->setValue("Periode : " . $data_px['tgl_mulai'] . " sampai " . $data_px['tgl_selesai']);

            $index = 7;

            // foreach ($data_px['total'] as $key => $value) {
            //     $sheet->getCell('M' . ($index - 1))->setValue('=SUM(M7:M'.($index).')');
            //     $sheet->getCell('N' . ($index - 1))->setValue('=SUM(N8:N'.($index).')');
            //     $sheet->getCell('O' . ($index - 1))->setValue('=SUM(Y8:Y'.($index).')');
            // }

            if ($data_px['header']['alamat'] == true) {
                $sheet->getCell('P' . ($index - 2))->setValue("Alamat");
            } else if ($data_px['header']['paraf'] == true) {
                $sheet->getCell('P' . ($index - 2))->setValue('Paraf');
            }

//            pd($data_px['data']);
            foreach ($data_px['data'] as $key => $value) {
                foreach ($value as $keys => $values) {
                    foreach ($values->data as $k => $v) {
                        $v = (array) $v;
                        $sheet->getCell('A' . $index)->setValue(($k == 0) ? $values->no : '');
                        $sheet->getCell('B' . $index)->setValue($v['tanggal']);
                        $sheet->setCellValueExplicit('C' . $index, $v['no_surat_jalan'], PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->getCell('D' . $index)->setValue($v['lokasi']);
                        $sheet->getCell('E' . $index)->setValue($v['nomor_faktur']);
                        $sheet->getCell('F' . $index)->setValue($v['no_invoice']);
                        $sheet->getCell('G' . $index)->setValue($v['namaCustomer']);
                        $sheet->setCellValueExplicit('H' . $index, !empty($v['npwp']) ? $v['npwp'] : '00.000.000.0-000.000', PHPExcel_Cell_DataType::TYPE_STRING);
                        $sheet->getCell('I' . $index)->setValue($v['namaBarang']);
                        $sheet->getCell('J' . $index)->setValue($v['jumlah']);
                        $sheet->getCell('K' . $index)->setValue($v['harga_satuan']);
                        $sheet->getCell('L' . $index)->setValue('=J'.$index.'*K'.$index);

                        if ($data_px['header']['alamat'] == true) {
                            $sheet->getCell('P' . $index)->setValue($v['alamat']);
                        } else if ($data_px['header']['paraf'] == true) {
                            $sheet->getCell('P' . $index)->setValue('');
                        }
                        if ($v['panjang'] == count($values->data)) {
                            $awal = $index-$v['panjang'];
                            $sheet->getCell('M' . $index)->setValue('=SUM(L'.($awal+1).':L'.$index.')');
                            $sheet->getCell('N' . $index)->setValue('=ROUND(SUM(M'.$index.'*10/100),0)');
                            $sheet->getCell('O' . $index)->setValue('=M'. $index.'+N'. $index);
                            $sheet->fromArray([], null, "A" . ($index + 1));
                            $index += 2;
                        } else {

                            $sheet->getCell('M' . $index)->setValue('');
                            $sheet->getCell('N' . $index)->setValue('');
                            $sheet->getCell('O' . $index)->setValue('');

                            $index++;
                        }
                    }
                }
            }
            $sheet->getCell('M6')->setValue('=SUM(M7:M'.($index).')');
            $sheet->getCell('N6')->setValue('=SUM(N7:N'.($index).')');
            $sheet->getCell('O6')->setValue('=SUM(O7:O'.($index).')');
            $sheet->getStyle('K6:K' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('L6:L' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('M6:M' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('N6:N' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
            $sheet->getStyle('O6:O' . $index)->getNumberFormat()->setFormatCode("#,##0.00");

            $sheet->getStyle("A" . 5 . ":P" . $index)->applyFromArray(
                    array(
                        'borders' => array(
                            'allborders' => array(
                                'style' => PHPExcel_Style_Border::BORDER_THIN,
                            )
                        )
                    )
            );

            $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel2007');
            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");

            $text = isset($params['tipe']) && $params['tipe'] == 'buku' ? "Buku" : "Copy";
            $title = isset($params['tipe']) && $params['tipe'] == 'buku' ? "Buku" : "";
            $sheet->getCell('A2')->setValue("LAPORAN " .strtoupper($title) . " PENJUALAN");
            $sheet->setTitle(date("M", strtotime($tgl_mulai)));
            header("Content-Disposition: attachment;Filename=\"" . $text . " Penjualan (" . date('d F Y', strtotime($tgl_mulai)) . "-" . date('d F Y', strtotime($tgl_selesai)) . ").xlsx\"");
//          pd($writer);
            $writer->save('php://output');
        }
    } elseif (isset($params['print']) && $params['print'] == 1) {

//        pd($data_px['data']);

        $page = 1;
        $limit = 37;
        $index = 0;
        $no = 1;
        $fix = [];
        foreach ($data_px['data'] as $key => $value) {
            foreach ($value as $keys => $values) {
                if (count($values->data) > $limit) {
                    $page += 1;
                    $limit = 45;
                    $index = 0;
                }
                foreach ($values->data as $k => $v) {
                    if ($k + 1 == count($values->data)) {
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
            }
        }

//        pd($fix);

        $view = twigView();
        $content = $view->fetch('laporan/penjualan_perpembeli_fix.html', [
            "data" => $data_px,
            'detail' => $fix,
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, $data_px);
    }
});

$app->get('/l_penjualan_perpembeli/listbarang', function ($request, $response) {
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
