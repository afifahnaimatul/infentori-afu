<?php

$app->get('/l_penjualan_perbarang/laporan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    // $tgl_mulai    = $params['startDate'];
    // $tgl_selesai  = $params['endDate'];

    $tgl_mulai    = date("Y-m-01", strtotime($params['bulan']) );
    $tgl_selesai  = date("Y-m-t", strtotime($params['bulan']) );

    $params['sub_kategori'] = getChildId('inv_m_kategori', 2);
    $params['sub_kategori'][] = 3;
    $params['sub_kategori'] = implode(", ", $params['sub_kategori']);

    /** Ambil Saldo Awal */
    $db->select('
            inv_penjualan.id,
            inv_penjualan.tanggal,
            inv_penjualan.acc_m_kontak_id,
            inv_penjualan.acc_m_lokasi_id,
            inv_penjualan_det.inv_m_barang_id,
            inv_penjualan_det.diskon,
            inv_penjualan_det.harga,
             inv_penjualan_det.jumlah as KWT,
            inv_m_barang.nama as namaBarang,
            inv_m_kategori.nama as namaKategori,
            inv_m_kategori.id as idKategori,
            acc_m_kontak.nama as namaCustomer,
            acc_m_kontak.npwp,
            acc_m_lokasi.nama as lokasi,
            inv_m_faktur_pajak.nomor as no_faktur
        ')
            ->from('inv_penjualan_det')
            ->leftJoin('inv_m_barang', 'inv_m_barang.id = inv_penjualan_det.inv_m_barang_id')
            ->leftJoin('inv_m_kategori', 'inv_m_kategori.id = inv_m_barang.inv_m_kategori_id')
            ->leftJoin('inv_penjualan', 'inv_penjualan.id = inv_penjualan_det.inv_penjualan_id')
            ->leftJoin('inv_m_faktur_pajak', 'inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id')
            ->leftJoin('acc_m_kontak', 'acc_m_kontak.id = inv_penjualan.acc_m_kontak_id')
            ->leftJoin('acc_m_lokasi', 'acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id')
            ->customWhere("inv_m_kategori.id IN($params[sub_kategori])", "AND")
            ->orderBy("inv_m_kategori.id DESC, inv_m_barang.nama, inv_penjualan.tanggal");

    if (isset($tgl_mulai) && isset($tgl_selesai)) {

        $db->customWhere("FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') >= '$tgl_mulai'", 'AND')
        ->customWhere("FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') <= '$tgl_selesai'", 'AND');
    }
    if (isset($params['m_customer_id']) && $params['m_customer_id'] != 0) {
        $db->andWhere('inv_penjualan.acc_m_kontak_id', '=', $params['m_customer_id']);
    }
    if (isset($params['acc_m_lokasi_id']) && $params['acc_m_lokasi_id'] != 1) {
        $db->andWhere('inv_penjualan.acc_m_lokasi_id', '=', $params['acc_m_lokasi_id']);
    }
    if (isset($params['barang_id']) && $params['barang_id'] != 0) {
        $db->andWhere('inv_penjualan_det.inv_m_barang_id', '=', $params['barang_id']);
    }

    $db->orderBy("
      FIELD(inv_m_kategori.id,2,3),
      inv_m_barang.nama ASC,
      inv_penjualan.tanggal ASC,
      inv_m_faktur_pajak.nomor ASC
    ");
    $data = $db->findAll();

    $hasil = $hitung = $semua = [];
    $id = 0;
    foreach ($data as $key => $val) {
        $id = $val->acc_m_kontak_id;

        $kat = $val->idKategori != 3 ? 2 : 3;
        $kat_nama = $val->idKategori != 3 ? 'Barang Jadi' : 'Barang Dagangan';

        $data[$key]->status = $val->KWT > 0 ? '' : 'Dibatalkan';

        $data[$key]->npwp = !empty($data[$key]->npwp) ? $data[$key]->npwp : '00.000.000.0-000.000';
        $data[$key]->harga_satuan = $val->harga - $val->diskon;
        $data[$key]->tanggal = date("d/m/Y", $val->tanggal);
        $data[$key]->nilai = $data[$key]->harga_satuan * $val->KWT;
        $hasil[$kat]['detail'][$val->inv_m_barang_id]->data[] = $val;
        $hitung[$kat]['detail'][$val->inv_m_barang_id]->data[] = $val;


        @$hitung[$kat]['detail'][$val->inv_m_barang_id]->data['Total'] += round($data[$key]->nilai);
        @$hitung[$kat]['detail'][$val->inv_m_barang_id]->data['jumlah_barang'] += round($data[$key]->KWT);

        @$hasil[$kat]['total']['kwt'] += round($data[$key]->KWT);
        @$hasil[$kat]['total']['nilai'] += round($data[$key]->nilai);

        @$hasil[$kat]['nama'] = $kat_nama;

        $data[$key]->Total = $hitung[$kat]['detail'][$val->inv_m_barang_id]->data['Total'];
        $data[$key]->jumlah_barang = $hitung[$kat]['detail'][$val->inv_m_barang_id]->data['jumlah_barang'];
        $data[$key]->panjang = count($hasil[$kat]['detail'][$val->inv_m_barang_id]->data);

        @$semua['Total']    += round($data[$key]->nilai);
        @$semua['kwantum']  += round($data[$key]->KWT);
    }

    if (isset($semua) && $semua != []) {
        $total[] = $semua;
    } else {
        $total = [];
    }

    $data_px = [
        'data'            => $hasil,
        'total'           => $total,
        'tgl_mulai'       => date("d F Y", strtotime($tgl_mulai)),
        'tgl_selesai'     => date("d F Y", strtotime($tgl_selesai)),
        'lokasi'          => "PT. AMAK FIRDAUS UTOMO",
    ];

    if (isset($params['export']) && $params['export'] == 1) {
        $xls = PHPExcel_IOFactory::load("format_excel/penjualan_per_nama_barang.xlsx");

// get the first worksheet
        $sheet = $xls->getSheet(0);
        $sheet->getCell('A3')->setValue("Periode : " . $data_px['tgl_mulai'] . " sampai " . $data_px['tgl_selesai']);

        $index = 7;

        foreach ($data_px['total'] as $key => $value) {
            $sheet->getCell('H' . ($index - 1))->setValue("TOTAL");
            $sheet->getCell('I' . ($index - 1))->setValue($value['Total']);
        }

//        pd($data_px['data']);
        foreach ($data_px['data'] as $key => $value) {
            foreach ($value['detail'] as $keys => $values) {
                foreach ($values->data as $k => $v) {
                    $v = (array) $v;
                    $sheet->getCell('A' . $index)->setValue($v['tanggal']);
                    $sheet->setCellValueExplicit('B' . $index, $v['no_faktur'], PHPExcel_Cell_DataType::TYPE_STRING);
                    // $sheet->getCell('C' . $index)->setValue($v['status']);
                    $sheet->getCell('C' . $index)->setValue($v['namaCustomer']);
                    $sheet->setCellValueExplicit('D' . $index, $v['npwp'], PHPExcel_Cell_DataType::TYPE_STRING);
                    $sheet->getCell('E' . $index)->setValue($v['namaBarang']);
                    $sheet->getCell('F' . $index)->setValue($v['KWT']);
                    $sheet->getCell('G' . $index)->setValue($v['panjang'] == count($values->data) ? ($v['jumlah_barang']) : '');
                    $sheet->getCell('H' . $index)->setValue($v['harga_satuan']);
                    $sheet->getCell('I' . $index)->setValue($v['nilai']);
                    $sheet->getCell('J' . $index)->setValue($v['panjang'] == count($values->data) ? ($v['Total']) : '');

                    if ($v['panjang'] == count($values->data)) {
                        $sheet->fromArray([], null, "A" . ($index + 1));
                        $index += 2;
                    } else {
                        $index++;
                    }
                }
            }
            $sheet->getCell('F' . ($index - 1))->setValue("Jumlah " . $value['nama']);
            $sheet->getCell('G' . ($index - 1))->setValue($value['total']['kwt']);
            $sheet->getCell('H' . ($index - 1))->setValue($value['total']['nilai']);
            $index++;
        }

        $sheet->getStyle('H6:H' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('I6:I' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('J6:J' . $index)->getNumberFormat()->setFormatCode("#,##0.00");

        $sheet->getStyle("A" . 5 . ":J" . $index)->applyFromArray(
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
        header("Content-Disposition: attachment;Filename=\"Penjualan Per Nama Barang (" . $data_px['tgl_mulai'] . "-" . $data_px['tgl_selesai'] . ").xlsx\"");
        $writer->save('php://output');
    } elseif (isset($params['print']) && $params['print'] == 1) {
      // pd($data_px);
        $view = twigView();
        $content = $view->fetch('laporan/penjualan_perbarang.html', [
            "data" => $data_px,
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;

        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, $data_px);
    }
});

$app->get('/l_penjualan_perbarang/listbarang', function ($request, $response) {
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
