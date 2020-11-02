<?php

$app->get('/l_pembelian_perbulan/getKontak', function ($request, $response) {
    $db = $this->db;
    $params = $request->getParams();
    $db->select("*")
            ->from("acc_m_kontak")
            ->orderBy("acc_m_kontak.nama")
            ->where("is_deleted", "=", 0)
            ->customWhere("type IN ('supplier', 'angkutan', 'pelabuhan', 'pengurusan dokumen', 'pengelolaan gudang', 'lain-lain')", "AND");
    if (isset($params['nama']) && !empty($params['nama'])) {
        $db->customWhere("nama LIKE '%" . $params['nama'] . "%'", "AND");
    }
    $models = $db->findAll();
    foreach ($models as $key => $val) {
//        $val->type = ucfirst($val->type);
//        $val->acc_m_akun_id = !empty($val->acc_m_akun_id) ? $db->find("SELECT id, kode, nama FROM acc_m_akun WHERE id = " . $val->acc_m_akun_id) : [];
    }
    return successResponse($response, [
        'list' => $models,
    ]);
});

$app->get("/l_pembelian_perbulan/laporan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    //bulan
    $params['bulan_awal'] = $params['bulan'] . "-01";
    $params['bulan_akhir'] = date("Y-m-t", strtotime($params['bulan']));

//    pd($params);

    $db->select("
      inv_pembelian_det.inv_pembelian_id,
      inv_pembelian_det.inv_m_barang_id,
      inv_pembelian_det.jumlah as jumlah_masuk,
      inv_pembelian_det.harga as harga_beli,
      inv_pembelian_det.subtotal_edit,
      inv_pembelian_det.diskon,
      inv_pembelian.tanggal,
      inv_pembelian.kode,
      inv_pembelian.ppn_edit,
      inv_pembelian.total total_pembelian,
      inv_pembelian.no_invoice,
      inv_m_faktur_pajak.nomor as faktur_pajak,
      COALESCE(inv_m_barang_nama.nama, inv_m_barang.nama) as barang,
      inv_m_satuan.nama as satuan,
      acc_m_kontak.nama as kontak,
      acc_m_kontak.npwp
        ")
            ->from("inv_pembelian_det")
            ->join("LEFT JOIN", "inv_m_barang", "inv_m_barang.id = inv_pembelian_det.inv_m_barang_id")
            ->join("LEFT JOIN", "inv_m_barang_nama", "inv_m_barang_nama.id = inv_pembelian_det.inv_m_barang_nama_id")
            ->join("LEFT JOIN", "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->join("LEFT JOIN", "inv_pembelian", "inv_pembelian.id = inv_pembelian_det.inv_pembelian_id")
            ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_pembelian.inv_m_faktur_pajak_id = inv_m_faktur_pajak.id")
            ->join("LEFT JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_pembelian.acc_m_kontak_id")
            ->customWhere("FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') >= '" . $params['bulan_awal'] . "'", "AND")
            ->customWhere("FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') <= '" . $params['bulan_akhir'] . "'", "AND")
            ->where("inv_pembelian.is_import", "=", 0)
            ->where("inv_pembelian.is_ppn", "=", $params['is_ppn']);

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("inv_pembelian.acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {
        $db->where("inv_m_barang.inv_m_kategori_id", "=", $params['kategori']);
    }

    if (isset($params['barang']) && !empty($params['barang'])) {
        $db->where("inv_pembelian_det.inv_m_barang_id", "=", $params['barang']);
    }

    if (isset($params['supplier']) && !empty($params['supplier'])) {
        $db->where("inv_pembelian.acc_m_kontak_id", "=", $params['supplier']);
    }

    $db->andWhere("inv_pembelian.status", "!=", 'draft');

    $pembelian = $db->orderBy("inv_pembelian.tanggal, inv_pembelian.inv_m_faktur_pajak_id, inv_pembelian_det.id")->findAll();

//    pd($pembelian);

    $data['total_kwt'] = 0;
    $data['total_nilai'] = 0;
    $data['total_jumlah'] = 0;
    $data['total_dpp'] = 0;
    $data['total_ppn'] = 0;
    $data['total_total'] = 0;

    $totalPerBeli = [];
    $no = 0;
    foreach ($pembelian as $key => $val) {

      if($val->diskon > 0){
        $val->harga_beli_temp = $val->harga_beli;
        $val->harga_beli      = $val->harga_beli - floatval($val->diskon/$val->jumlah_masuk);
      }

        $val->tanggal_formated  = date("d/m/Y", $val->tanggal);
        $val->nilai             = !empty($val->subtotal_edit) ? floatval($val->subtotal_edit - $val->diskon) : floatval(($val->jumlah_masuk * $val->harga_beli) - $val->diskon);
        $val->jumlah            = $val->nilai;
        $val->dpp               = $val->nilai;
        $val->ppn               = round($val->dpp * (10 / 100));
        $val->total             = floatval($val->dpp + $val->ppn);

        // Total Per Pembelian
        if (empty($totalPerBeli[$val->inv_pembelian_id])) {
            $no++;
            $val->nomor_urut = $no;
            $totalPerBeli[$val->inv_pembelian_id]['jumlah'] = $val->jumlah;
            $totalPerBeli[$val->inv_pembelian_id]['dpp'] = $val->dpp;
            $totalPerBeli[$val->inv_pembelian_id]['ppn'] = $val->ppn;
            $totalPerBeli[$val->inv_pembelian_id]['total'] = $val->total;
        } else {
            $totalPerBeli[$val->inv_pembelian_id]['jumlah'] += $val->jumlah;
            $totalPerBeli[$val->inv_pembelian_id]['dpp'] += $val->dpp;
            $totalPerBeli[$val->inv_pembelian_id]['ppn'] += $val->ppn;
            $totalPerBeli[$val->inv_pembelian_id]['total'] += $val->total;
        }

        // $val->sum_jumlah  = $totalPerBeli[$val->inv_pembelian_id]['jumlah'];
        // $val->sum_dpp     = $totalPerBeli[$val->inv_pembelian_id]['dpp'];
        // $val->sum_ppn     = !empty($val->ppn_edit) ? $val->ppn_edit : $totalPerBeli[$val->inv_pembelian_id]['ppn'];
        // $val->sum_total   = !empty($val->ppn_edit) ? $totalPerBeli[$val->inv_pembelian_id]['total'] - $totalPerBeli[$val->inv_pembelian_id]['ppn'] + $val->ppn_edit : $totalPerBeli[$val->inv_pembelian_id]['total'];

        // Baru
        $val->sum_jumlah  = $val->total_pembelian;
        $val->sum_dpp     = $val->total_pembelian;
        $val->sum_ppn     = !empty($val->ppn_edit) ? $val->ppn_edit : $totalPerBeli[$val->inv_pembelian_id]['ppn'];
        $val->sum_total   = $val->total_pembelian + $val->sum_ppn;

        // Total Per Pembelian - END

        if (empty($pembelian[$key + 1]) || $pembelian[$key + 1]->inv_pembelian_id != $val->inv_pembelian_id) {
            // $data['total_jumlah'] += $totalPerBeli[$val->inv_pembelian_id]['jumlah'];
            // $data['total_dpp']    += $totalPerBeli[$val->inv_pembelian_id]['dpp'];
            // $data['total_ppn']    += !empty($val->ppn_edit) ? $val->ppn_edit : $totalPerBeli[$val->inv_pembelian_id]['total_ppn'];
            // $data['total_total']  += !empty($val->ppn_edit) ? $totalPerBeli[$val->inv_pembelian_id]['total'] - $totalPerBeli[$val->inv_pembelian_id]['ppn'] + $val->ppn_edit : $totalPerBeli[$val->inv_pembelian_id]['total'];

            // baru
            $data['total_jumlah']  += $val->total_pembelian;
            $data['total_dpp']     += $val->total_pembelian;
            $data['total_ppn']     += !empty($val->ppn_edit) ? $val->ppn_edit : $totalPerBeli[$val->inv_pembelian_id]['ppn'];
            $data['total_total']   += $val->total_pembelian + $val->sum_ppn;

            // baru
            $data['total_nilai']  += $val->total_pembelian;
        }

        $data['total_kwt']    += $val->jumlah_masuk;
        // $data['total_nilai']  += $val->nilai;
    }

    $data['bulan']      = strtoupper(date("F Y", strtotime($params['bulan'])));
    $data['lokasi']     = isset($params['lokasi_nama']) && !empty($params['lokasi_nama']) ? strtoupper($params['lokasi_nama']) : "PT. AMAK FIRDAUS UTOMO";
    $data['kategori']   = isset($params['kategori_nama']) && !empty($params['kategori_nama']) ? strtoupper($params['kategori_nama']) : "SEMUA KATEGORI";

    $detail = $pembelian;

    if (isset($params['is_export']) && $params['is_export'] == 1) {
        $xls = PHPExcel_IOFactory::load("format_excel/pembelian_perbulan.xlsx");

// get the first worksheet
        $sheet = $xls->getSheet(0);
        $sheet->getCell('A3')->setValue("Periode : " . $data['bulan']);

        $index = 6;

//        pd($detail);
        foreach ($detail as $k => $v) {
            $v = (array) $v;
            if ($k == 0) {
                $sheet->getCell('A' . $index)->setValue($v['nomor_urut']);
                $sheet->getCell('B' . $index)->setValue($v['tanggal_formated']);
                $sheet->setCellValueExplicit('C' . $index, $v['faktur_pajak'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->getCell('D' . $index)->setValue($v['kontak']);
                $sheet->setCellValueExplicit('E' . $index, $v['npwp'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->getCell('F' . $index)->setValue($v['barang']);
                $sheet->getCell('G' . $index)->setValue($v['jumlah_masuk']);
                $sheet->getCell('H' . $index)->setValue($v['satuan']);
                $sheet->getCell('I' . $index)->setValue($v['harga_beli']);
                $sheet->getCell('J' . $index)->setValue($v['nilai']);
                $sheet->getCell('K' . $index)->setValue(($v['inv_pembelian_id'] != $detail[$k + 1]->inv_pembelian_id) ? ($v['sum_jumlah']) : '');
                $sheet->getCell('L' . $index)->setValue(($v['inv_pembelian_id'] != $detail[$k + 1]->inv_pembelian_id) ? ($v['sum_dpp']) : '');
                $sheet->getCell('M' . $index)->setValue(($v['inv_pembelian_id'] != $detail[$k + 1]->inv_pembelian_id) ? ($v['sum_ppn']) : '');
                $sheet->getCell('N' . $index)->setValue(($v['inv_pembelian_id'] != $detail[$k + 1]->inv_pembelian_id) ? ($v['sum_total']) : '');
            } else {
                $sheet->getCell('A' . $index)->setValue(isset($v['nomor_urut']) ? $v['nomor_urut'] : '');
                $sheet->getCell('B' . $index)->setValue(($v['inv_pembelian_id'] != $detail[$k - 1]->inv_pembelian_id) ? $v['tanggal_formated'] : '');
                $sheet->setCellValueExplicit('C' . $index, ($v['inv_pembelian_id'] != $detail[$k - 1]->inv_pembelian_id) ? $v['faktur_pajak'] : '', PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->getCell('D' . $index)->setValue($v['inv_pembelian_id'] != $detail[$k - 1]->inv_pembelian_id ? $v['kontak'] : '');
                $sheet->setCellValueExplicit('E' . $index, ($v['inv_pembelian_id'] != $detail[$k - 1]->inv_pembelian_id) ? $v['npwp'] : '', PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->getCell('F' . $index)->setValue($v['barang']);
                $sheet->getCell('G' . $index)->setValue($v['jumlah_masuk']);
                $sheet->getCell('H' . $index)->setValue($v['satuan']);
                $sheet->getCell('I' . $index)->setValue($v['harga_beli']);
                $sheet->getCell('J' . $index)->setValue($v['nilai']);
                $sheet->getCell('K' . $index)->setValue(($k + 1) == count($detail) ? $v['sum_jumlah'] : ($v['inv_pembelian_id'] != $detail[$k + 1]->inv_pembelian_id ? $v['sum_jumlah'] : ''));
                $sheet->getCell('L' . $index)->setValue(($k + 1) == count($detail) ? $v['sum_dpp'] : ($v['inv_pembelian_id'] != $detail[$k + 1]->inv_pembelian_id ? $v['sum_dpp'] : ''));
                $sheet->getCell('M' . $index)->setValue(($k + 1) == count($detail) ? $v['sum_ppn'] : ($v['inv_pembelian_id'] != $detail[$k + 1]->inv_pembelian_id ? $v['sum_ppn'] : ''));
                $sheet->getCell('N' . $index)->setValue(($k + 1) == count($detail) ? $v['sum_total'] : ($v['inv_pembelian_id'] != $detail[$k + 1]->inv_pembelian_id ? $v['sum_total'] : ''));
            }

            if (($k + 1) != count($detail) && $v['inv_pembelian_id'] != $detail[$k + 1]->inv_pembelian_id) {
                $sheet->fromArray([], null, "A" . ($index + 1));
                $index += 2;
            } else {
                $index++;
            }
        }

        $sheet->getStyle('H6:H' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('I6:I' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('J6:J' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('K6:K' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('L6:L' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('M6:M' . $index)->getNumberFormat()->setFormatCode("#,##0.00");
        $sheet->getStyle('N6:N' . $index)->getNumberFormat()->setFormatCode("#,##0.00");

        $sheet->getCell('F' . ($index))->setValue("TOTAL");
        $sheet->getCell('G' . ($index))->setValue($data['total_kwt']);
        $sheet->getCell('J' . ($index))->setValue(($data['total_nilai']));
        $sheet->getCell('K' . ($index))->setValue(($data['total_jumlah']));
        $sheet->getCell('L' . ($index))->setValue($data['total_dpp']);
        $sheet->getCell('M' . ($index))->setValue(($data['total_ppn']));
        $sheet->getCell('N' . ($index))->setValue(($data['total_total']));

        $sheet->getStyle("A" . 5 . ":N" . $index)->applyFromArray(
                array(
                    'borders' => array(
                        'allborders' => array(
                            'style' => PHPExcel_Style_Border::BORDER_THIN,
                        )
                    )
                )
        );

        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel2007');
        // pd($writer);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"Pembelian Perbulan (" . $data['bulan'] . ").xlsx\"");
        $writer->save('php://output');
        echo $content;
    } elseif (isset($params['is_print']) && $params['is_print'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/pembelian_perbulan.html", [
            'data' => $data,
            'detail' => $detail,
            'totalPerbeli' => $totalPerBeli,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, [
            'data' => $data,
            'detail' => $detail,
            'totalPerbeli' => $totalPerBeli,
        ]);
    }
});
