<?php

$app->get('/l_rekapan_pusat/laporan', function ($request, $response) {
    $params = $request->getParams();

    $db = $this->db;

//    pd($params);

    $tanggal_start = $params['tanggal'] . '-01-01';
    $tanggal_end = $params['tanggal'] . '-12-31';
    $tanggal_end_saldo = $params['tanggal'] . '-12-01';
//    $data['tanggal'] = date("d/m/Y", strtotime($tanggal_start)) . ' Sampai ' . date("d/m/Y", strtotime($tanggal_end));
    $data['disiapkan'] = date("d/m/Y, H:i");
    $data['lokasi'] = $params['nama_lokasi'];
//    $data['akun'] = strtoupper($params['nama_akun']);
    $data['tahun'] = $params['tanggal'];
    if (isset($params['m_lokasi_id'])) {
        $lokasiId = getChildId("acc_m_lokasi", $params['m_lokasi_id']);
        /*
         * jika lokasi punya child
         */
        if (!empty($lokasiId)) {
            $lokasiId[] = $params['m_lokasi_id'];
            $lokasiId = implode(",", $lokasiId);
        } else {
            $lokasiId = $params['m_lokasi_id'];
        }
    }

    if (isset($params['m_akun_id'])) {
        $akunId = getChildId("acc_m_akun", $params['m_akun_id']);
        /*
         * jika lokasi punya child
         */
        if (!empty($akunId)) {
            $akunId = implode(",", $akunId);
        } else {
            $akunId = $params['m_akun_id'];
        }
    }

    $list = [
        "PENERIMAAN" => [
                [
                "nama" => "TARIKAN TUNAI DARI BCA 2583262628 - AFU",
                "akun" => 114,
            ],
                [
                "nama" => "TITIPAN PPh Ps 21 PUSAT",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "TITIPAN PPh Ps 21 CABANG SURABAYA",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PENERIMAAN DARI PIUTANG USAHA",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "TITIPAN PPH SEWA",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PPH Ps. 23 (Catatan)",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "Catatan Piutang Ps. 23 Jasa Angkutan",
                "akun" => rand(2, 200),
            ],
        ],
        "PENGELUARAN" => [
                [
                "nama" => "PEMBELIAN KERTAS SEMEN (NON PPN)",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PEMBAYAR PASAL 23",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "SETORAN TUNAI KE BCA 2583262628 - AFU",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "SETORAN TUNAI KE BCA 2589288828 - AFU",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BIAYA UPAH TENAGA KERJA LANGSUNG",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "GAJI BAGIAN PRODUKSI",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "THR",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "AIR BAWAH TANAH",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "Pemeliharaan Bangunan",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "Peralatan Kecil",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "Peralatan mesin  (non PPn)",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "LIMBAH",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "LIMBAH (Test Laboratorium)",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "GAJI BAGIAN PENJUALAN ",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "THR",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BIAYA PEMASARAN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BIAYA PENGIRIMAN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BAHAN BAKAR, PARKIR, TOL",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BONGKAR MUAT",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PERJALANAN DINAS",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BIAYA KENDARAAN /SPARE PART KENDARAAN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "SURAT - SURAT KENDARAAN ",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "GAJI BAGIAN ADMIN & UMUM ",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "THR",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "TELEPHONE",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PBB",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BBM, PARKIR, TOL",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KONSUMSI",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PENGOBATAN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "ALAT TULIS &KEPERLUAN KANTOR",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "IURAN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "SUMBANGAN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PERIZINAN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BIAYA GEDUNG",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BIAYA KIRIM PAKET",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PULSA",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KEBERSIHAN & KEAMANAN ",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "JAMSOSTEK",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BPJS",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PENERANGAN JALAN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BIAYA KONSULTAN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BIAYA INVENTARIS",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "LAIN-LAIN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PERJALANAN DINAS",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "HUTANG GAJI",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BAYAR HUTANG USAHA (NON PPN)",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BAYAR HUTANG DAGANG",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BAYAR HUTANG BIAYA PELABUHAN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PPH PASAL 22",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BEA MASUK",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PPN IMPORT",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BAYAR HUTANG IMPORT ",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BAYAR HUTANG BIAYA (EKSPEDISI)",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KAS KEDIRI",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KAS BLORA",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KAS MAGETAN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KAS SUBANG",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KAS CAMPLONG",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KAS CIAMIS",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KAS BOJONEGORO",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KAS SURABAYA",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KAS MALANG",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KAS JEMBER",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KAS BALI",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PEMBULATAN",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "KROSS DARI BANK BII B3 ATAS CN OTO CREDIT BPJS Ketenagakerjaan JKK",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "PENGEMBALIAN TITIPAN SOEKARTONO",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BIAYA TRANSFER IMPORT",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "Bayar PPH 21 (Probolinggo)",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BAYAR PPH 21 (Surabaya)",
                "akun" => rand(2, 200),
            ],
                [
                "nama" => "BAYAR PPH 21 - TENAGA AHLI",
                "akun" => rand(2, 200),
            ],
        ],
    ];

//    pd($list);

    $arr = [];
    $akunId = [];
    foreach ($list as $key => $value) {
//        $arr[$key] = $value;
        $index = 0;
        foreach ($value as $k => $v) {
            $arr[$key]['detail'][$index] = $v;
            $akunId[] = $v['akun'];
            for ($i = 1; $i <= 12; $i++) {
                $arr[$key]['detail'][$index]['detail'][date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'))] = [];
                $arr[$key]['detail'][$index]['total'] = 0;
            }
            $index++;
        }

        for ($i = 1; $i <= 12; $i++) {
            $arr[$key]['total'][date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'))]['total'] = 0;
        }

        $arr[$key]['total2'] = 0;
    }

    for ($i = 1; $i <= 12; $i++) {
        $data['tanggal'][] = date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'));
        $data['saldo_awal'][date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'))] = 0;
        $data['saldo_akhir'][date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'))] = 0;
    }

    $akunId = implode(", ", array_unique($akunId));

//    pd($arr);
//    pd($akunId);

    /*
     * Saldo awal
     */

    $db->select("acc_trans_detail.id, acc_trans_detail.kode, 
        acc_trans_detail.m_akun_id,
                acc_trans_detail.debit, 
                acc_trans_detail.kredit, 
                acc_trans_detail.keterangan, 
                acc_trans_detail.tanggal, 
                acc_m_akun.kode as kodeAkun, 
                acc_m_akun.nama as namaAkun,
                acc_m_lokasi.kode as kodeLokasi,
                acc_m_lokasi.nama as namaLokasi,
                lokasi_saldo.nama as namaLokasiSaldo
        ")->from("acc_trans_detail");
    if (isset($params['m_lokasi_id']) && !empty($params['m_lokasi_id'])) {
        $db->customWhere("acc_trans_detail.m_lokasi_id IN ($lokasiId)");
    }
    if (isset($akunId) && !empty($akunId)) {
        $db->customWhere("acc_trans_detail.m_akun_id IN ($akunId)", "AND");
    }
    $db->leftJoin("acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
            ->leftJoin("acc_m_lokasi", "acc_m_lokasi.id = acc_trans_detail.m_lokasi_jurnal_id")
            ->leftJoin("acc_m_lokasi as lokasi_saldo", "lokasi_saldo.id = acc_trans_detail.m_lokasi_id")
            ->andWhere('date(acc_trans_detail.tanggal)', '<', $tanggal_end_saldo)
            ->orderBy("acc_trans_detail.tanggal ASC, acc_trans_detail.kode ASC, acc_trans_detail.id ASC");
    $trans_detail_awal = $db->findAll();

//    $arr_awal = [];
    $data['total_saldo_awal'] = 0;
    foreach ($trans_detail_awal as $key => $value) {
        $month = date('m', strtotime($value->tanggal));

        for ($i = ($month + 1); $i <= 12; $i++) {
            $date = date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'));
            $data['saldo_awal'][$date] += $value->debit - $value->kredit;
            $data['total_saldo_awal'] += $value->debit - $value->kredit;
        }
    }

//    pd($data['saldo_awal']);

    /*
     * ambil trans detail dari akun
     */
    $db->select("acc_trans_detail.id, acc_trans_detail.kode, 
        acc_trans_detail.m_akun_id,
                acc_trans_detail.debit, 
                acc_trans_detail.kredit, 
                acc_trans_detail.keterangan, 
                acc_trans_detail.tanggal, 
                acc_m_akun.kode as kodeAkun, 
                acc_m_akun.nama as namaAkun,
                acc_m_lokasi.kode as kodeLokasi,
                acc_m_lokasi.nama as namaLokasi,
                lokasi_saldo.nama as namaLokasiSaldo
        ")->from("acc_trans_detail");
    if (isset($params['m_lokasi_id']) && !empty($params['m_lokasi_id'])) {
        $db->customWhere("acc_trans_detail.m_lokasi_id IN ($lokasiId)");
    }
    if (isset($akunId) && !empty($akunId)) {
        $db->customWhere("acc_trans_detail.m_akun_id IN ($akunId)", "AND");
    }
    $db->leftJoin("acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
            ->leftJoin("acc_m_lokasi", "acc_m_lokasi.id = acc_trans_detail.m_lokasi_jurnal_id")
            ->leftJoin("acc_m_lokasi as lokasi_saldo", "lokasi_saldo.id = acc_trans_detail.m_lokasi_id")
            ->andWhere('date(acc_trans_detail.tanggal)', '>=', $tanggal_start)
            ->andWhere('date(acc_trans_detail.tanggal)', '<=', $tanggal_end)
            ->orderBy("acc_trans_detail.tanggal ASC, acc_trans_detail.kode ASC, acc_trans_detail.id ASC");
    $trans_detail = $db->findAll();

//    pd($trans_detail);

    $temp_arr = [];
    foreach ($trans_detail as $key => $value) {
        if (isset($temp_arr[$value->m_akun_id][date("F", strtotime($value->tanggal))]) && !empty($temp_arr[$value->m_akun_id][date("F", strtotime($value->tanggal))])) {
            $temp_arr[$value->m_akun_id][date("F", strtotime($value->tanggal))] += $value->debit - $value->kredit;
        } else {
            $temp_arr[$value->m_akun_id][date("F", strtotime($value->tanggal))] = $value->debit - $value->kredit;
        }
    }

//    pd($temp_arr);
//    pd($arr);
    $data['total_saldo_akhir'] = $data['total_saldo_awal'];
    $data['saldo_akhir'] = $data['saldo_awal'];
    foreach ($arr as $key => $value) {
        foreach ($value['detail'] as $k => $v) {
            foreach ($v['detail'] as $kk => $vv) {
                $arr[$key]['detail'][$k]['detail'][$kk]['total'] = isset($temp_arr[$v['akun']][$kk]) ? $temp_arr[$v['akun']][$kk] : 0;
                $arr[$key]['detail'][$k]['total'] += isset($temp_arr[$v['akun']][$kk]) ? $temp_arr[$v['akun']][$kk] : 0;
                $arr[$key]['total'][$kk]['total'] += isset($temp_arr[$v['akun']][$kk]) ? $temp_arr[$v['akun']][$kk] : 0;
                $arr[$key]['total2'] += isset($temp_arr[$v['akun']][$kk]) ? $temp_arr[$v['akun']][$kk] : 0;

                if ($key == 'PENERIMAAN') {
                    $data['saldo_akhir'][$kk] += isset($temp_arr[$v['akun']][$kk]) ? $temp_arr[$v['akun']][$kk] : 0;
                    $data['total_saldo_akhir'] += isset($temp_arr[$v['akun']][$kk]) ? $temp_arr[$v['akun']][$kk] : 0;
                } else {
                    $data['saldo_akhir'][$kk] -= isset($temp_arr[$v['akun']][$kk]) ? $temp_arr[$v['akun']][$kk] : 0;
                    $data['total_saldo_akhir'] -= isset($temp_arr[$v['akun']][$kk]) ? $temp_arr[$v['akun']][$kk] : 0;
                }
            }
        }
    }

//    $data['total_saldo_akhir'] = $data['total_saldo_awal'] + $arr['PENERIMAAN']['total2'] - $arr['PENGELUARAN']['total2'];
//    pd($arr);

    if (isset($params['export']) && $params['export'] == 1) {
        $view = twigView();
        $content = $view->fetch('laporan/rekapan_pusat.html', [
            "data" => $data,
            "detail" => $arr,
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment;Filename=laporan-rekapan-pusat.xls");
        echo $content;
    } elseif (isset($params['print']) && $params['print'] == 1) {
        $view = twigView();
        $content = $view->fetch('laporan/rekapan_pusat.html', [
            "data" => $data,
            "detail" => $arr,
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, ["data" => $data, "detail" => $arr]);
    }
});
