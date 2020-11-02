<?php

$app->get("/l_rekap_pembelian_perbulan/laporan", function($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    //bulan
    $params['bulan_awal'] = $params['bulan'] . "-01";
    $params['bulan_akhir'] = date("Y-m-t", strtotime($params['bulan']));

    $pembelian_no = $db->select("
      id,
      month(FROM_UNIXTIME(tanggal)) month,
      year(FROM_UNIXTIME(tanggal)) year
    ")
    ->from("inv_pembelian")
    ->where("Month(FROM_UNIXTIME(tanggal))", "=", date("m", strtotime($params['bulan_awal'])))
    ->andWhere("Year(FROM_UNIXTIME(tanggal))", "=", date("Y", strtotime($params['bulan_awal'])))
    ->where("inv_pembelian.is_import", "=", 0)
    ->where("inv_pembelian.is_ppn", "=", $params['is_ppn'])
    ->orderBy("tanggal")
    ->findAll();

    $arr_no = [];
    foreach ($pembelian_no as $key => $value) {
        $arr_no[$value->id]['no'] = $key + 1;
    }

    $db->select("inv_pembelian_det.inv_m_barang_id,
        inv_pembelian_det.jumlah as jumlah_masuk,
        inv_pembelian_det.harga as harga_beli,
        inv_pembelian_det.diskon,
        inv_pembelian_det.subtotal_edit,
        inv_pembelian.id as pembelian_id,
        inv_pembelian.tanggal,
        inv_pembelian.created_at,
        inv_pembelian.kode,
        inv_pembelian.no_invoice,
        inv_pembelian.jenis_pembelian,
        inv_pembelian.cash,
        inv_m_faktur_pajak.nomor as nomor_faktur,
        COALESCE(inv_m_barang_nama.nama, inv_m_barang.nama) as barang,
        inv_m_barang.is_pakai,
        inv_m_satuan.nama as satuan,
        acc_m_kontak.nama as kontak,
        inv_m_kategori.id as inv_m_kategori_id,
        inv_m_kategori.nama as kategori,
        inv_m_kategori.parent_id as parent_kategori
        ")
    ->from("inv_pembelian_det")
    ->join("LEFT JOIN", "inv_m_barang", "inv_m_barang.id = inv_pembelian_det.inv_m_barang_id")
    ->join("LEFT JOIN", "inv_m_barang_nama", "inv_m_barang_nama.id = inv_pembelian_det.inv_m_barang_nama_id")
    ->join("LEFT JOIN", "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
    ->join("LEFT JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
    ->join("LEFT JOIN", "inv_pembelian", "inv_pembelian.id = inv_pembelian_det.inv_pembelian_id")
    ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_pembelian.inv_m_faktur_pajak_id = inv_m_faktur_pajak.id")
    ->join("LEFT JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_pembelian.acc_m_kontak_id")
    ->customWhere("FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') >= '" . $params['bulan_awal'] . "'", "AND")
    ->customWhere("FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') <= '" . $params['bulan_akhir'] . "'", "AND")
    ->where("inv_pembelian.status", "!=", "draft")
    ->where("inv_pembelian.is_ppn", "=", $params['is_ppn'])
    ->where("inv_pembelian.is_import", "=", "0");

    if (isset($params['lokasi']) && !empty($params['lokasi'])) {
        $db->where("inv_pembelian.acc_m_lokasi_id", "=", $params['lokasi']);
    }

    if (isset($params['kategori']) && !empty($params['kategori'])) {

        $katId = getChildId("inv_m_kategori", $params['kategori']);
        if (!empty($katId)) {
            array_push($katId, $params['kategori']);
            $katId = implode(",", $katId);
        } else {
            $katId = $params['kategori'];
        }

        $db->customWhere("inv_m_barang.inv_m_kategori_id IN($katId)", "AND");
    }

    $pembelian = $db->orderBy("inv_m_barang.inv_m_kategori_id, inv_m_barang.is_pakai, inv_pembelian.jenis_pembelian, inv_m_barang.id, inv_pembelian.tanggal, inv_pembelian_det.id")->findAll();

   // pd(['rekap', $pembelian]);

    $total_kwt = 0;
    $total_nilai = 0;

    $count = count($pembelian);
    $index = 0;
    $arr = [];
    $data['total_kwt'] = 0;
    $data['total_nilai'] = 0;
    foreach ($pembelian as $key => $val) {

      if($val->diskon > 0){
        $val->harga_beli_temp = $val->harga_beli;
        $val->harga_beli      = $val->harga_beli - floatval($val->diskon/$val->jumlah_masuk);
      }

        $pembelian[$key] = (array) $val;

        $val->no                  = $arr_no[$val->pembelian_id]['no'];
        $val->tanggal_formated2   = $val->tanggal;
        $val->tanggal_formated3   = $val->created_at;
        $val->tanggal_formated    = date("d/m/Y", $val->tanggal);
        $val->nilai               = !empty($val->subtotal_edit) ? $val->subtotal_edit : round($val->jumlah_masuk * $val->harga_beli);

        if($val->diskon > 0){
          $val->nilai -= $val->diskon;
        }

        if ($val->parent_kategori != 18) {
            if ($val->is_pakai == 0) {
                if ($val->jenis_pembelian == "barang") {
                    if ($key + 1 != $count && $val->inv_m_barang_id != $pembelian[$key + 1]->inv_m_barang_id) {
                        $val->total_kwt       = $total_kwt + $val->jumlah_masuk;
                        $total_kwt            = 0;
                        $val->total_nilai     = $total_nilai + $val->nilai;
                        $total_nilai          = 0;
                        $data['total_kwt']    += $val->total_kwt;
                        $data['total_nilai']  += $val->total_nilai;
                        $arr[$index]          = $val;
                        $arr[$index + 1]      = [];
                        $index                += 2;

                    } else if ($key + 1 == $count) {
                        $val->total_kwt       = $total_kwt + $val->jumlah_masuk;
                        $total_kwt            = 0;
                        $val->total_nilai     = $total_nilai + $val->nilai - $val->cash;
                        $total_nilai          = 0;
                        $data['total_kwt']    += $val->total_kwt;
                        $data['total_nilai']  += $val->total_nilai;
                        $arr[$index]          = $val;
                        $arr[$index + 1]      = [];

                    } else {
                        $total_kwt      += $val->jumlah_masuk;
                        $total_nilai    += $val->nilai;
                        $arr[$index]    = $val;
                        $index          += 1;

                    }
                } else {
                    if ($key + 1 == $count) {
                        $val->total_kwt = $total_kwt + $val->jumlah_masuk;
                        $total_kwt = 0;
                        $val->total_nilai = $total_nilai + $val->nilai;
                        $total_nilai = 0;
                        $data['total_kwt'] += $val->total_kwt;
                        $data['total_nilai'] += $val->total_nilai;
                        $arr[$index] = $val;

                    } else {
                        $total_kwt += $val->jumlah_masuk;
                        $total_nilai += $val->nilai;
                        $arr[$index] = $val;
                        $index += 1;
                    }
                }
            } else {
              // Barang sekali pakai
                if ($val->jenis_pembelian == "barang") {
                    if ($key + 1 != $count && $val->inv_m_kategori_id != $pembelian[$key + 1]->inv_m_kategori_id) {
                        $val->total_kwt       = $total_kwt + $val->jumlah_masuk;
                        $total_kwt            = 0;
                        $val->total_nilai     = $total_nilai + $val->nilai;
                        $total_nilai          = 0;
                        $data['total_kwt']    += $val->total_kwt;
                        $data['total_nilai']  += $val->total_nilai;
                        $arr[$index]          = $val;
                        $arr[$index + 1]      = [];
                        $index                += 2;

                    } else if ($key + 1 == $count) {
                        $val->total_kwt       = $total_kwt + $val->jumlah_masuk;
                        $total_kwt            = 0;
                        $val->total_nilai     = $total_nilai + $val->nilai - $val->cash;
                        $total_nilai          = 0;
                        $data['total_kwt']    += $val->total_kwt;
                        $data['total_nilai']  += $val->total_nilai;
                        $arr[$index]          = $val;
                        $arr[$index + 1]      = [];

                    } else {
                        $total_kwt            += $val->jumlah_masuk;
                        $total_nilai          += $val->nilai;
                        $arr[$index]          = $val;
                        $index                += 1;

                    }
                } else {
                    if ($key + 1 == $count) {
                        $val->total_kwt = $total_kwt + $val->jumlah_masuk;
                        $total_kwt = 0;

                        $val->total_nilai = $total_nilai + $val->nilai;
                        $total_nilai = 0;

                        $data['total_kwt'] += $val->total_kwt;
                        $data['total_nilai'] += $val->total_nilai;

                        $arr[$index] = $val;
//                $arr[$index + 1] = [];
//            $arr[$index + 1] = [
//                'jumlah_masuk' => $val->total_kwt,
//                'nilai' => $val->total_nilai,
//            ];
                    } else {
                        $total_kwt += $val->jumlah_masuk;
                        $total_nilai += $val->nilai;

//                $data['total_kwt'] += $val->jumlah_masuk;
//                $data['total_nilai'] += $val->nilai;

                        $arr[$index] = $val;

                        $index += 1;
                    }
                }
            }
        } else {
            if ($key + 1 != $count && $val->inv_m_kategori_id != $pembelian[$key + 1]->inv_m_kategori_id) {
                $val->total_kwt = $total_kwt + $val->jumlah_masuk;
                $total_kwt = 0;

                $val->total_nilai = $total_nilai + $val->nilai;
                $total_nilai = 0;

                $data['total_kwt'] += $val->total_kwt;
                $data['total_nilai'] += $val->total_nilai;

                $arr[$index] = $val;
//            $arr[$index + 1] = [
//                'jumlah_masuk' => $val->total_kwt,
//                'nilai' => $val->total_nilai,
//            ];
                $arr[$index + 1] = [];
                $index += 2;
            } else if ($key + 1 == $count) {
                $val->total_kwt = $total_kwt + $val->jumlah_masuk;
                $total_kwt = 0;

                $val->total_nilai = $total_nilai + $val->nilai;
                $total_nilai = 0;

                $data['total_kwt'] += $val->total_kwt;
                $data['total_nilai'] += $val->total_nilai;

                $arr[$index] = $val;
//            $arr[$index + 1] = [
//                'jumlah_masuk' => $val->total_kwt,
//                'nilai' => $val->total_nilai,
//            ];
                $arr[$index + 1] = [];
            } else {
                $total_kwt += $val->jumlah_masuk;
                $total_nilai += $val->nilai;

//                $data['total_kwt'] += $val->jumlah_masuk;
//                $data['total_nilai'] += $val->nilai;

                $arr[$index] = $val;

                $index += 1;
            }
        }
    }

    $data['bulan'] = strtoupper(date("F Y", strtotime($params['bulan'])));
    $data['lokasi'] = isset($params['lokasi_nama']) && !empty($params['lokasi_nama']) ? strtoupper($params['lokasi_nama']) : "PT. AMAK FIRDAUS UTOMO";
    $data['kategori'] = isset($params['kategori_nama']) && !empty($params['kategori_nama']) ? strtoupper($params['kategori_nama']) : "SEMUA KATEGORI";

    $detail = $arr;

    if (isset($params['is_export']) && $params['is_export'] == 1) {
        $view = twigView();
        $content = $view->fetch("laporan/rekap_pembelian_perbulan.html", [
            'data' => $data,
            'detail' => $detail,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header("Content-Disposition: attachment;Filename=\"Rekap Pembelian Perbulan (" . $data['bulan'] . ").xls\"");

        echo $content;
    } elseif (isset($params['is_print']) && $params['is_print'] == 1) {

        $view = twigView();
        $content = $view->fetch("laporan/rekap_pembelian_perbulan.html", [
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
