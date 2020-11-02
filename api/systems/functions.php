<?php

function get_proses_akhir() {
    $db = config('DB');
    $db = new Cahkampung\Landadb($db['db']);

    $a = $db->select("tanggal")->from("inv_proses_akhir")->orderBy("id DESC")->find();

    return $a->tanggal;
}

// function pd($data = []) {
//     echo "<pre>";
//     print_r($data);
//     die;
// }

function getAllData($db, $tableName, $groupBy = NULL) {
    $model = $db->select("*")->from($tableName)->findAll();

    $result = [];

    if (!empty($model) && !empty($groupBy)) {
        foreach ($model as $key => $value) {
            $data = (array) $value;
            $result[$data[$groupBy]] = $data;
        }
    }

    return $result;
}

function getStok($db, $inv_m_barang_id, $acc_m_lokasi_id, $allLokasi = false, $tglMulai=null, $tglSelesai=null) {
    $result = 0;

    $db->select("
      (COALESCE(SUM(jumlah_masuk), 0) - COALESCE(SUM(jumlah_keluar), 0)) as stok
    ")
        ->from("inv_kartu_stok");
    $db->where('inv_m_barang_id', "=", $inv_m_barang_id);

    if( !empty($tglMulai) ){
        $tglMulai = date('Y-m-d', strtotime($tglMulai));
        $db->customWhere("FROM_UNIXTIME(inv_kartu_stok.tanggal, '%Y-%m-%d') >= '" . $tglMulai . "'", "AND");
    }

    if( !empty($tglSelesai) ){
        $tglSelesai = date('Y-m-d', strtotime($tglSelesai));
        $db->customWhere("FROM_UNIXTIME(inv_kartu_stok.tanggal, '%Y-%m-%d') <= '" . $tglSelesai . "'", "AND");
    }

    if ($allLokasi == false) {
        $db->andWhere('acc_m_lokasi_id', "=", $acc_m_lokasi_id);
    }

    $getStok = $db->find();

    if (!empty($getStok))
        $result = $getStok->stok;

    return $result;
}

function pos_stok($barang_id, $lokasi_id, $allCabang = false, $tgl_mulai = null, $tgl_selesai = null) {
    $db = config('DB');
    $db = new Cahkampung\Landadb($db['db']);

    $db->select("SUM(stok) AS stok_barang")
        ->from("inv_kartu_stok")
        ->where("inv_m_barang_id", "=", $barang_id);

    if (!empty($tgl_mulai)) {
        $db->customWhere("FROM_UNIXTIME(tanggal, '%Y-%m-%d') >= '" . date("Y-m-d", strtotime($tgl_mulai)) . "'", "AND");
        $db->customWhere("FROM_UNIXTIME(tanggal, '%Y-%m-%d') <= '" . date("Y-m-d", strtotime($tgl_selesai)) . "'", "AND");
    }

    if ($allCabang == false) {
        $db->andWhere("acc_m_lokasi_id", "=", $lokasi_id);
    } else {
        if (isset($_SESSION['user']['akses_cabang'])) {
            $id = [];
            foreach ($_SESSION['user']['akses_cabang'] as $key => $val) {
                $id[] = $val->m_cabang_id;
            }
            if (!empty($id)) {
                $db->customWhere("acc_m_lokasi_id IN (" . join(",", $id) . ")", "AND");
            }
        }
    }

    $data = $db->find();
    $stok = $data->stok_barang;
    if ($stok == null) {
        $stok = 0;
    }

    return $stok;
}

function pos_hpp($produk_id, $cabang_id, $tanggal = null, $type = null, $jumlah = null, $unpostAvg = false, $hargaUnpost = 0, $typeUnpost = '', $cek = false) {
    $db = config('DB');
    $db = new Cahkampung\Landadb($db['db']);

    $db->select("inv_kartu_stok.*, FROM_UNIXTIME(inv_kartu_stok.tanggal, '%Y-%m-%d') as tgl, inv_m_barang.type_barcode")
        ->from("inv_kartu_stok")
        ->leftJoin("inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
        ->where("inv_m_barang_id", "=", $produk_id)
        ->andWhere("jenis_kas", "=", 'masuk')
        ->andWhere("stok", ">", 0);

    if (!empty($cabang_id)) {
        $db->andWhere("acc_m_lokasi_id", "=", $cabang_id);
    }

    if (!empty($tanggal)) {
        $db->customWhere("FROM_UNIXTIME(inv_kartu_stok.tanggal, '%Y-%m-%d') <= '" . date("Y-m-d", $tanggal) . "'", "AND");
    }

    $hpp = [];
    if ($type == 'fifo') {
        $db->orderBy("tgl ASC, inv_kartu_stok.id ASC");
        $cek_data = $db->findAll();
        foreach ($cek_data as $key => $value) {
            $hpp_ = [
                'id' => $value->id,
                'hpp' => $value->harga_masuk,
                'jumlah' => $jumlah > $value->stok ? $value->stok : $jumlah,
            ];
            array_push($hpp, $hpp_);

            if ($jumlah <= $value->stok) {
                break;
            }
            $jumlah -= $value->stok;
        }
    } else if ($type == 'lifo') {
        $db->orderBy("tgl DESC, inv_kartu_stok.id DESC");
        $cek_data = $db->findAll();
        foreach ($cek_data as $key => $value) {
            $hpp_ = [
                'id' => $value->id,
                'hpp' => $value->harga_masuk,
                'jumlah' => $jumlah > $value->stok ? $value->stok : $jumlah,
            ];
            array_push($hpp, $hpp_);

            if ($jumlah <= $value->stok) {
                break;
            }
            $jumlah -= $value->stok;
        }
    } else if ($type == 'average') {
        $db->orderBy("inv_kartu_stok.id ASC, tgl ASC, inv_kartu_stok.created_at ASC");
        $data = $db->findAll();

        $saldo['jumlah'] = 0;
        $saldo['harga'] = 0;
        $saldo['saldo'] = 0;
        foreach ($data as $key => $value) {
            if ($value->jumlah_masuk > 0) {
                if (!empty($value->hpp)) {
                    $valhpp = $value->hpp;
                } else {
                    $valhpp = $value->harga_masuk;
                }

                $jml = $value->stok;
                $harga = $valhpp;
                $saldoAkhir = $valhpp * $value->stok;

                $saldo['jumlah'] = (isset($saldo['jumlah']) ? $saldo['jumlah'] : 0) + $jml;
                $saldo['saldo'] = (isset($saldo['saldo']) ? $saldo['saldo'] : 0) + $saldoAkhir;

                if ($saldo['jumlah'] > 0) {
                    $saldo['harga'] = round($saldo['saldo'] / $saldo['jumlah']);
                } else {
                    $saldo['harga'] = 0;
                }
            } else {
                $saldo['harga'] = ($value->catatan == 'Unpost Pembelian') ? $value->hpp : $saldo['harga'];
                $saldo['jumlah'] = (isset($saldo['jumlah']) ? $saldo['jumlah'] : 0) - $jml;
                // $saldo['saldo']  = $saldo['jumlah'] * $saldo['harga'];
                $saldo['saldo'] = $saldo['jumlah'] * $saldo['harga'];
            }
        }

        if ($unpostAvg) {
            if ($typeUnpost == 'jual') {
                $saldo['saldo'] = $saldo['saldo'] + ($hargaUnpost * $jumlah);
                $saldo['jumlah'] = (isset($saldo['jumlah']) ? $saldo['jumlah'] : 0) + $jumlah;
            } else {
                $saldo['saldo'] = $saldo['saldo'] - ($hargaUnpost * $jumlah);
                $saldo['jumlah'] = (isset($saldo['jumlah']) ? $saldo['jumlah'] : 0) - $jumlah;
            }

            if ($saldo['jumlah'] > 0) {
                $saldo['harga'] = ceil($saldo['saldo'] / $saldo['jumlah']);
            } else {
                $saldo['harga'] = 0;
            }
        }

        if ($cek === false) {
            foreach ($data as $key => $val) {
                $db->update("inv_kartu_stok", ["hpp" => $saldo['harga']], ["id" => $val->id]);
            }
        }

        $hpp_ = [
            'hpp' => $saldo['harga'],
            'jumlah' => $jumlah,
            'saldo' => $saldo['saldo'],
        ];

        array_push($hpp, $hpp_);
    }

    $total_hpp = 0;

    if (!empty($type)) {
        return $hpp;
    } else {
        return round($total_hpp);
    }
}

function pos_pengurangan_stok($jumlah_pengurangan, $produk_id, $cabang_id, $tanggal = null, $unpost = false, $harga = 0) {
    $db = config('DB');
    $db = new Cahkampung\Landadb($db['db']);


    $perulangan = true;
    $pengurangan_stok = $jumlah_pengurangan;

    $getHargaPokok = $db->find("select harga_pokok from inv_m_barang where id = '" . $produk_id . "'");
    $harga_pokok = isset($getHargaPokok->harga_pokok) ? $getHargaPokok->harga_pokok : '';

    do {
        $db->select("id,stok,harga_masuk,jumlah_masuk, FROM_UNIXTIME(inv_kartu_stok.tanggal, '%Y-%m-%d') as tgl")
            ->from("inv_kartu_stok")
            ->where("inv_m_barang_id", "=", $produk_id)
            ->andWhere("stok", ">", 0)
            ->andWhere("acc_m_lokasi_id", "=", $cabang_id);

        if ($unpost && $harga > 0) {
            $db->andWhere("harga_masuk", "=", $harga);
        }

        if ($harga_pokok == 'lifo') {
            $db->orderBy("tgl DESC, created_at DESC, id DESC");
        } else {
            $db->orderBy("tgl ASC, created_at ASC, id ASC");
        }

        // ->orderBy("FROM_UNIXTIME(inv_kartu_stok.tanggal,'%Y-%m-%d') ASC, id ASC");
        // if (!empty($tanggal)) {
        //     $db->andWhere("tanggal", "<=", $tanggal);
        // }

        $cekStok = $db->find();
        if (isset($cekStok->id)) {
            $update_stok = $cekStok->stok - $pengurangan_stok;

            if ($update_stok >= 0) {
                $perulangan = false;
                if ($pengurangan_stok == 0) {
                    $update = $db->run("UPDATE inv_kartu_stok SET stok = 0 WHERE id = $cekStok->id");
                } else {
                    $update = $db->run("UPDATE inv_kartu_stok SET stok = $update_stok WHERE id = $cekStok->id");
                }
            } else {
                $pengurangan_stok -= $cekStok->stok;
                $update = $db->run("UPDATE inv_kartu_stok SET stok = 0 WHERE id = $cekStok->id");
            }
        } else {
            $perulangan = false;
        }
    } while ($perulangan);
}

function pos_hpp_retur($produk_id, $cabang_id, $value, $key, $type = null, $jumlah = null, $jenis_kas = 'keluar') {
    $db = config('DB');
    $db = new Cahkampung\Landadb($db['db']);
    // print_r($produk_id);exit();
    $db->select("*, FROM_UNIXTIME(inv_kartu_stok.tanggal, '%Y-%m-%d') as tgl")
        ->from("inv_kartu_stok")
        ->where("inv_m_barang_id", "=", $produk_id)
        ->andWhere("jenis_kas", "=", $jenis_kas)
        ->andWhere("acc_m_lokasi_id", "=", $cabang_id);

    if (!empty($value)) {
        $db->andWhere("trans_tipe", "=", $key)
            ->andWhere("trans_id", "=", $value);
    } else if ($jenis_kas == 'masuk') {
        $db->andWhere('stok', '>', 0);
    }

    if (!empty($tanggal)) {
        $db->customWhere("FROM_UNIXTIME(inv_kartu_stok.tanggal, '%Y-%m-%d') <= '" . date("Y-m-d", $tanggal) . "'", "AND");
    }

    $hpp = [];
    if ($type == 'fifo') {
        $db->orderBy("tgl ASC");
        $cek_data = $db->findAll();
        foreach ($cek_data as $key => $value) {
            if ($jenis_kas == 'keluar') {
                $valhpp = $value->hpp;
                $value->jumlah_keluar = $value->jumlah_keluar;
            } else {
                $valhpp = $value->harga_masuk;
                $value->jumlah_keluar = $value->jumlah_masuk;
            }

            $hpp_ = [
                'id' => $value->id,
                'hpp' => $valhpp,
                'jumlah' => $jumlah > $value->jumlah_keluar ? $value->jumlah_keluar : $jumlah,
            ];
            array_push($hpp, $hpp_);

            if ($jumlah <= $value->jumlah_keluar) {
                break;
            }
            $jumlah -= $value->jumlah_keluar;
        }
    } else if ($type == 'lifo') {
        $db->orderBy("tgl DESC");
        $cek_data = $db->findAll();
        foreach ($cek_data as $key => $value) {
            if ($jenis_kas == 'keluar') {
                $valhpp = $value->hpp;
                $value->jumlah_keluar = $value->jumlah_keluar;
            } else {
                $valhpp = $value->harga_masuk;
                $value->jumlah_keluar = $value->jumlah_masuk;
            }

            $hpp_ = [
                'id' => $value->id,
                'hpp' => $valhpp,
                'jumlah' => $jumlah > $value->jumlah_keluar ? $value->jumlah_keluar : $jumlah,
            ];
            array_push($hpp, $hpp_);

            if ($jumlah <= $value->jumlah_keluar) {
                break;
            }
            $jumlah -= $value->jumlah_keluar;
        }
    } else if ($type == 'average') {
        $db->orderBy("tgl ASC, created_at ASC");
        $data = $db->findAll();
        $jumlah_masuk = 0;
        $hpp_avg = 0;
        if (count($data) > 0) {
            foreach ($data as $val) {
                if ($jenis_kas == 'keluar') {
                    $valhpp = $val->hpp;
                    $val->jumlah_keluar = $val->jumlah_keluar;
                } else {
                    $valhpp = $val->harga_masuk;
                    $val->jumlah_keluar = $val->jumlah_masuk;
                }

                $hpp_avg += $val->jumlah_keluar * $valhpp;
                $jumlah_masuk += $val->jumlah_keluar;
            }
        }

        if ($jumlah_masuk == 0) {
            $total_hpp = 0;
        } else {
            $total_hpp = $hpp_avg / $jumlah_masuk;
        }
        $hpp_ = [
            'hpp' => $total_hpp,
            'jumlah' => $jumlah,
        ];
        array_push($hpp, $hpp_);
    } else {

        $db->orderBy("tgl ASC, created_at ASC");
        $data = $db->findAll();
        $jumlah_keluar = 0;
        $hpp_avg = 0;
        $brg = [];
        if (count($data) > 0) {
            foreach ($data as $val) {
                $hpp_avg += $val->jumlah_keluar * $val->hpp;
                $jumlah_keluar += $val->jumlah_keluar;
                $brg[$val->id]['hpp'] = $val->hpp;
            }
        }

        if ($jumlah_keluar == 0) {
            $total_hpp = 0;
        } else {
            $total_hpp = $hpp_avg / $jumlah_keluar;
        }
    }

    if (!empty($type)) {
        return $hpp;
    } else {
        return round($total_hpp);
    }
}

function cek_hpp_stok($produk_id, $cabang_id, $harga, $jumlah) {
    $db = config('DB');
    $db = new Cahkampung\Landadb($db['db']);

    $db->select("sum(stok) as stok")
        ->from("inv_kartu_stok")
        ->where("inv_m_barang_id", "=", $produk_id)
        ->andWhere("stok", ">", 0)
        ->andWhere("harga_masuk", "=", $harga)
        ->andWhere("acc_m_lokasi_id", "=", $cabang_id);
    $model = $db->find();

    if ($model->stok >= $jumlah) {
        return 1;
    }
    return 0;
}

function generatePenjualan($tipe, $tanggal, $lokasi = null) {
    $db = config('DB');
    $db = new Cahkampung\Landadb($db['db']);

//    $tanggal = get_proses_akhir();

    $bulan = date("m", strtotime($tanggal));
    $tahun = date("Y", strtotime($tanggal));
    if ($tipe == 'invoice') {
        $cek = $db->find("SELECT no_invoice FROM inv_penjualan WHERE inv_proses_akhir_id IS NULL AND no_invoice <> '' ORDER BY no_invoice DESC");
//    print_r($cek);die;
        $urut = (empty($cek)) ? 1 : ((int) substr($cek->no_invoice, 0, 5)) + 1;
        $no_urut = substr('00000' . $urut, -5);
        $no_transaksi = $no_urut . "/" . $bulan . "/" . $tahun;
    } else if ($tipe == 'surat_jalan') {
        $kode = $db->find("SELECT kode FROM acc_m_lokasi WHERE id = '{$lokasi}'");
        $cek = $db->find("SELECT no_surat_jalan FROM inv_penjualan WHERE inv_proses_akhir_id IS NULL AND acc_m_lokasi_id = '{$lokasi}' ORDER BY no_surat_jalan DESC");
//        print_r($cek);
//        die;
        $urut = (empty($cek)) ? 1 : ((int) substr($cek->no_surat_jalan, 0, 5)) + 1;
        $no_urut = substr('00000' . $urut, -5);
        $no_transaksi = $no_urut . " " . $kode->kode;
    } else if ($tipe == 'kode') {
        $cek = $db->find("select kode from inv_penjualan order by id desc");
        $urut = (empty($cek)) ? 1 : ((int) substr($cek->kode, -5)) + 1;
        $no_urut = substr('00000' . $urut, -5);
        $no_transaksi = "B/" . $no_urut;
    } else if ($tipe == 'faktur_pajak') {
        $no_transaksi = $db->select("inv_m_faktur_pajak.id")->from("inv_m_faktur_pajak")
            ->join("left join", "inv_penjualan", "inv_penjualan.inv_m_faktur_pajak_id = inv_m_faktur_pajak.id AND inv_m_faktur_pajak.jenis_faktur = 'penjualan'")
            ->where("jenis_faktur", "=", "penjualan")
            ->where("terpakai", "=", "tidak")
            ->customWhere("inv_penjualan.id IS NULL", "AND")
            ->orderBy("id ASC")
            ->limit(1)
            ->find();
        $no_transaksi = $no_transaksi->id;
    }
//    echo $no_transaksi;
//    die;

    return $no_transaksi;
}

/*
  1. Get Trans Detail
  2. Sum total
  3. Simpan jurnal ke jurnal_umum
  4. Simpan ke trans_detail
 */
function simpanJurnal($params=[]){

    $db = config('DB');
    $db = new Cahkampung\Landadb($db['db']);
    $totalDebit = $totalKredit = 0;

    // Get Pemetaan Akun
    $db->select("*")->from("acc_m_akun_peta");
    $getPetaAkun = $db->findAll();

    $pemetaanAkun = [];
    foreach ($getPetaAkun as $key => $value) {
        $pemetaanAkun[$value->type] = $value->m_akun_id;
    }
    // Get Pemetaan Akun - END

    // Jurnal Pembelian
    if($params['reff_type'] == 'inv_pembelian' && !empty($params['reff_id']) ){
        $getDetPembelian = $db->select("
      inv_pembelian.id as inv_pembelian_id,
      inv_pembelian.acc_m_lokasi_id,
      inv_pembelian.acc_m_kontak_id,
      acc_m_lokasi.kode as acc_m_lokasi_kode,
      acc_m_kontak.nama as acc_m_kontak_nama,
      FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') as tanggal,
      inv_pembelian.kode,
      inv_pembelian.no_invoice,
      inv_pembelian.pib,
      inv_pembelian.jenis_pembelian,
      inv_pembelian.is_import,
      inv_pembelian.is_ppn,
      inv_pembelian.ppn_edit,
      inv_pembelian.total_edit,
      inv_pembelian.cash,
      inv_m_faktur_pajak.nomor as nomor_fp,
      inv_pembelian_det.*,
      COALESCE(inv_pembelian_det.diskon, 0) AS diskon,
      inv_m_kategori.is_dijual,
      inv_m_kategori.akun_pembelian_id,
      inv_m_kategori.akun_persediaan_brg_id,
      inv_m_kategori.akun_penjualan_id,
      inv_m_kategori.akun_hpp_id
    ")
            ->from("inv_pembelian_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_pembelian_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_pembelian", "inv_pembelian.id = inv_pembelian_det.inv_pembelian_id")
            ->join('LEFT JOIN', "acc_m_kontak", "acc_m_kontak.id = inv_pembelian.acc_m_kontak_id")
            ->join('LEFT JOIN', "acc_m_lokasi", "acc_m_lokasi.id = inv_pembelian.acc_m_lokasi_id")
            ->join('LEFT JOIN', "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_pembelian.inv_m_faktur_pajak_id")
            ->join('LEFT JOIN', "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->where("inv_pembelian_det.inv_pembelian_id", "=", $params['reff_id'])
            ->orderBy("inv_pembelian_det.id")
            ->findAll();

        /*
          Skema jurnal Pembelian
          - Persediaan
          - PPn Masukan (Jika ada PPn)
              - Hutang Usaha
              - Kas / Piutang (Jika ada uang muka)"

          ----------------------------------------------

          Skema jurnal Pembelian Import
          - Persediaan
              - Hutang Usaha
        */

        $detJurnal = $listJurnal = [];
        $totalPembelian = $totalBiayaPK = 0;
        if(!empty($getDetPembelian)){
          $nomor_transaksi    = '';
          $nomor_transaksi    = isset($getDetPembelian[0]->no_invoice) && $getDetPembelian[0]->no_invoice != '' ? $getDetPembelian[0]->no_invoice : $nomor_transaksi;
          $nomor_transaksi    = isset($getDetPembelian[0]->nomor_fp) && $getDetPembelian[0]->nomor_fp != '' ? $getDetPembelian[0]->nomor_fp : $nomor_transaksi;
          $nomor_transaksi    = isset($getDetPembelian[0]->is_import) && $getDetPembelian[0]->is_import == 1 ? $getDetPembelian[0]->pib : $nomor_transaksi;

            foreach ($getDetPembelian as $key => $value) {
                $totalPembelian += $value->subtotal_edit - $value->diskon;
                $m_akun_id = ($value->is_dijual == 'ya') ? $value->akun_persediaan_brg_id : $value->akun_hpp_id;

                if( !empty($detJurnal[$m_akun_id]) ){
                    @$detJurnal[$m_akun_id]['debit']      += $value->subtotal_edit - $value->diskon;
                } else {
                    $detJurnal[$m_akun_id]['debit']       = $value->subtotal_edit - $value->diskon;
                    $detJurnal[$m_akun_id]['kredit']      = 0;
                    $detJurnal[$m_akun_id]['type']        = 'debit';
                    $detJurnal[$m_akun_id]['tanggal']     = $value->tanggal;
                    $detJurnal[$m_akun_id]['m_lokasi_id'] = $value->acc_m_lokasi_id;
                    $detJurnal[$m_akun_id]['m_akun_id']   = $m_akun_id;
                    $detJurnal[$m_akun_id]['keterangan']  = 'Pembelian (' . $nomor_transaksi .') - ' . $value->acc_m_kontak_nama;
                    $detJurnal[$m_akun_id]['kode']        = $nomor_transaksi;
                    $detJurnal[$m_akun_id]['reff_type']   = 'inv_pembelian';
                    $detJurnal[$m_akun_id]['reff_id']     = $value->inv_pembelian_id;
                }
            }

            // Memasukkan list jurnal final
            $indexJurnal = 0;
            foreach ($detJurnal as $key => $value) {
                $listJurnal[$indexJurnal] = $value;
                $indexJurnal++;
            }

            // PPN jika ada
            if( ($getDetPembelian[0]->ppn_edit > 0) && $getDetPembelian[0]->is_ppn == 1 && $getDetPembelian[0]->is_import == 0){
                $indexJurnal++;
                $listJurnal[$indexJurnal] = [
                    'debit'       => $getDetPembelian[0]->ppn_edit,
                    'kredit'      => 0,
                    'type'        => 'debit',
                    'tanggal'     => $getDetPembelian[0]->tanggal,
                    'm_lokasi_id' => $getDetPembelian[0]->acc_m_lokasi_id,
                    'm_akun_id'   => $pemetaanAkun['PPN Masukan'],
                    'keterangan'  => 'Pembelian (' . $nomor_transaksi .') - ' . $getDetPembelian[0]->acc_m_kontak_nama,
                    'kode'        => $nomor_transaksi,
                    'reff_type'   => 'inv_pembelian',
                    'reff_id'     => $getDetPembelian[0]->inv_pembelian_id,
                ];

                $totalPembelian += $getDetPembelian[0]->ppn_edit;
            }
            // PPN jika ada - END

            // Biaya Import
            if($getDetPembelian[0]->is_import == 1){

            }
            // Biaya Import - END
            // Hutang Usaha
            $indexJurnal++;
            $listJurnal[$indexJurnal] = [
                'debit'       => 0,
                'kredit'      => $totalPembelian,
                'type'        => 'kredit',
                'tanggal'     => $getDetPembelian[0]->tanggal,
                'm_lokasi_id' => $getDetPembelian[0]->acc_m_lokasi_id,
                'm_akun_id'   => $pemetaanAkun['Hutang Usaha'],
                'keterangan'  => ('Pembelian (' . $nomor_transaksi .') - ' . $getDetPembelian[0]->acc_m_kontak_nama),
                'kode'        => $nomor_transaksi,
                'reff_type'   => 'inv_pembelian',
                'reff_id'     => $getDetPembelian[0]->inv_pembelian_id,
            ];
            // Hutang Usaha - END

            $totalDebit   = $totalKredit = $totalPembelian;
            $kodeLokasi   = $getDetPembelian[0]->acc_m_lokasi_kode;
            $m_kontak_id  = $getDetPembelian[0]->acc_m_kontak_id;
            // Memasukkan list jurnal final - END
        } else {
          $indexJurnal = 0;
          // Jika Saldo Hutang
          $getPembelian = $db->select("
            inv_pembelian.id as inv_pembelian_id,
            inv_pembelian.acc_m_lokasi_id,
            inv_pembelian.acc_m_kontak_id,
            acc_m_lokasi.kode as acc_m_lokasi_kode,
            acc_m_kontak.nama as acc_m_kontak_nama,
            FROM_UNIXTIME(inv_pembelian.tanggal, '%Y-%m-%d') as tanggal,
            inv_pembelian.kode,
            inv_pembelian.no_invoice,
            inv_pembelian.acc_m_akun_id,
            inv_pembelian.pib,
            inv_pembelian.jenis_pembelian,
            inv_pembelian.is_import,
            inv_pembelian.is_ppn,
            inv_pembelian.ppn_edit,
            inv_pembelian.hutang,
            inv_pembelian.total_edit
          ")
          ->from("inv_pembelian")
          ->join('LEFT JOIN', "acc_m_kontak", "acc_m_kontak.id = inv_pembelian.acc_m_kontak_id")
          ->join('LEFT JOIN', "acc_m_lokasi", "acc_m_lokasi.id = inv_pembelian.acc_m_lokasi_id")
          ->where("inv_pembelian.id", "=", $params['reff_id'])
          ->find();

          if( !empty($getPembelian) ){
            /*
              Skema Jurnal
                M Akun
                PPN Masukan (Jika ada)
                    Hutang Usaha
            */
            $ppn = !empty($getPembelian->ppn_edit) ? $getPembelian->ppn_edit : 0;
            $listJurnal[$indexJurnal] = [
                'debit'       => $getPembelian->hutang - $ppn,
                'kredit'      => 0,
                'type'        => 'debit',
                'tanggal'     => $getPembelian->tanggal,
                'm_lokasi_id' => $getPembelian->acc_m_lokasi_id,
                'm_akun_id'   => $getPembelian->acc_m_akun_id,
                'keterangan'  => 'Saldo awal hutang (' . $getPembelian->no_invoice .') - ' . $getPembelian->acc_m_kontak_nama,
                'kode'        => $getPembelian->no_invoice,
                'reff_type'   => 'inv_pembelian',
                'reff_id'     => $getPembelian->inv_pembelian_id,
            ];
            $indexJurnal++;

            $listJurnal[$indexJurnal] = [
                'debit'       => $ppn,
                'kredit'      => 0,
                'type'        => 'debit',
                'tanggal'     => $getPembelian->tanggal,
                'm_lokasi_id' => $getPembelian->acc_m_lokasi_id,
                'm_akun_id'   => $pemetaanAkun['PPN Masukan'],
                'keterangan'  => 'Saldo awal hutang (' . $getPembelian->no_invoice .') - ' . $getPembelian->acc_m_kontak_nama,
                'kode'        => $getPembelian->no_invoice,
                'reff_type'   => 'inv_pembelian',
                'reff_id'     => $getPembelian->inv_pembelian_id,
            ];
            $indexJurnal++;

            $listJurnal[$indexJurnal] = [
                'debit'       => 0,
                'kredit'      => $getPembelian->hutang,
                'type'        => 'kredit',
                'tanggal'     => $getPembelian->tanggal,
                'm_lokasi_id' => $getPembelian->acc_m_lokasi_id,
                'm_akun_id'   => $pemetaanAkun['Hutang Usaha'],
                'keterangan'  => 'Saldo awal hutang (' . $getPembelian->no_invoice .') - ' . $getPembelian->acc_m_kontak_nama,
                'kode'        => $getPembelian->no_invoice,
                'reff_type'   => 'inv_pembelian',
                'reff_id'     => $getPembelian->inv_pembelian_id,
            ];

            $totalDebit   = $totalKredit = $getPembelian->hutang;
            $kodeLokasi   = $getPembelian->acc_m_lokasi_kode;
            $m_kontak_id  = $getPembelian->acc_m_kontak_id;

          }

          // Jika Saldo Hutang - END
        }

    }
    // Jurnal Pembelian - End

    // Jurnal Retur Pembelian
    if($params['reff_type'] == 'inv_retur_pembelian' && !empty($params['reff_id']) ){
        $getDetRetur = $db->select("
      inv_retur_pembelian.id as inv_retur_pembelian_id,
      inv_pembelian.acc_m_kontak_id,
      acc_m_kontak.nama as acc_m_kontak_nama,
      inv_retur_pembelian.acc_m_lokasi_id,
      acc_m_lokasi.kode as acc_m_lokasi_kode,
      FROM_UNIXTIME(inv_retur_pembelian.tanggal, '%Y-%m-%d') as tanggal,
      inv_retur_pembelian.kode,
      inv_retur_pembelian_det.*,
      inv_m_kategori.is_dijual,
      inv_m_kategori.akun_pembelian_id,
      inv_m_kategori.akun_persediaan_brg_id,
      inv_m_kategori.akun_penjualan_id,
      inv_m_kategori.akun_hpp_id
    ")
            ->from("inv_retur_pembelian_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_retur_pembelian_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_retur_pembelian", "inv_retur_pembelian.id = inv_retur_pembelian_det.inv_retur_pembelian_id")
            ->join('LEFT JOIN', "inv_pembelian", "inv_pembelian.id = inv_retur_pembelian.inv_pembelian_id")
            ->join('LEFT JOIN', "acc_m_kontak", "acc_m_kontak.id = inv_pembelian.acc_m_kontak_id")
            ->join('LEFT JOIN', "acc_m_lokasi", "acc_m_lokasi.id = inv_retur_pembelian.acc_m_lokasi_id")
            ->join('LEFT JOIN', "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->where("inv_retur_pembelian_det.inv_retur_pembelian_id", "=", $params['reff_id'])
            ->orderBy("inv_retur_pembelian_det.id")
            ->findAll();

        /*
          Skema jurnal Retur Pembelian
          - Hutang Usaha
              - Persediaan
              - PPn Masukan

        */
        $detJurnal = $listJurnal = [];
        $totalRetur = $totalPPN = 0;

        if(!empty($getDetRetur)){
            foreach ($getDetRetur as $key => $value) {
                $nominalRetur = ($value->jumlah_retur * $value->harga_retur);
                if($nominalRetur > 0){
                    $totalRetur += ($nominalRetur + ($nominalRetur*10/100) );
                    $totalPPN   += ($nominalRetur*10/100);
                    $m_akun_id = ($value->is_dijual == 'ya') ? $value->akun_persediaan_brg_id : $value->akun_hpp_id;

                    if( !empty($detJurnal[$m_akun_id]) ){
                        @$detJurnal[$m_akun_id]['kredit']     += $nominalRetur;
                    } else {
                        $detJurnal[$m_akun_id]['debit']       = 0;
                        $detJurnal[$m_akun_id]['kredit']      = $nominalRetur;
                        $detJurnal[$m_akun_id]['type']        = 'kredit';
                        $detJurnal[$m_akun_id]['tanggal']     = $value->tanggal;
                        $detJurnal[$m_akun_id]['m_lokasi_id'] = $value->acc_m_lokasi_id;
                        $detJurnal[$m_akun_id]['m_akun_id']   = $m_akun_id;
                        $detJurnal[$m_akun_id]['keterangan']  = 'Retur Pembelian (' . $value->kode .') - ' . $value->acc_m_kontak_nama;
                        $detJurnal[$m_akun_id]['kode']        = $value->kode;
                        $detJurnal[$m_akun_id]['reff_type']   = 'inv_retur_pembelian';
                        $detJurnal[$m_akun_id]['reff_id']     = $value->inv_retur_pembelian_id;
                    }
                }
            }

            // Insert Jurnal Hutang Usaha
            $indexJurnal = 0;
            $listJurnal[$indexJurnal] = [
                'debit'       => $totalRetur,
                'kredit'      => 0,
                'type'        => 'debit',
                'tanggal'     => $getDetRetur[0]->tanggal,
                'm_lokasi_id' => $getDetRetur[0]->acc_m_lokasi_id,
                'm_akun_id'   => $pemetaanAkun['Hutang Usaha'],
                'keterangan'  => 'Retur Pembelian (' . $getDetRetur[0]->kode .') - ' . $getDetRetur[0]->acc_m_kontak_nama,
                'kode'        => $getDetRetur[0]->kode,
                'reff_type'   => 'inv_retur_pembelian',
                'reff_id'     => $getDetRetur[0]->inv_retur_pembelian_id,
            ];

            // Memasukkan list jurnal final
            foreach ($detJurnal as $key => $value) {
                $indexJurnal++;
                $listJurnal[$indexJurnal] = $value;
            }

            // Insert PPN Masukan
            if(!empty($totalPPN)){
                $indexJurnal++;
                $listJurnal[$indexJurnal] = [
                    'debit'       => 0,
                    'kredit'      => $totalPPN,
                    'type'        => 'kredit',
                    'tanggal'     => $getDetRetur[0]->tanggal,
                    'm_lokasi_id' => $getDetRetur[0]->acc_m_lokasi_id,
                    'm_akun_id'   => $pemetaanAkun['PPN Masukan'],
                    'keterangan'  => 'Retur Pembelian (' . $getDetRetur[0]->kode .') - ' . $getDetRetur[0]->acc_m_kontak_nama,
                    'kode'        => $getDetRetur[0]->kode,
                    'reff_type'   => 'inv_retur_pembelian',
                    'reff_id'     => $getDetRetur[0]->inv_retur_pembelian_id,
                ];
            }

            $totalDebit   = $totalKredit = $totalRetur;
            $kodeLokasi   = $getDetRetur[0]->acc_m_lokasi_kode;
            $m_kontak_id  = $getDetRetur[0]->acc_m_kontak_id;
        }
    }
    // Jurnal Retur Pembelian - END

    // Jurnal Bayar Hutang
    if($params['reff_type'] == 'acc_bayar_hutang' && !empty($params['reff_id']) ){
        $getBayarHutang = $db->select("
          acc_bayar_hutang.*,
          acc_bayar_hutang_det.m_akun_id,
          acc_bayar_hutang_det.bayar,
          acc_bayar_hutang_det.is_pelunasan,
          acc_bayar_hutang_det.sisa_pelunasan,
          acc_bayar_hutang_det.catatan,
          acc_m_lokasi.kode as acc_m_lokasi_kode
        ")
        ->from("acc_bayar_hutang")
        ->join("LEFT JOIN", "acc_m_lokasi", "acc_m_lokasi.id = acc_bayar_hutang.m_lokasi_id")
        ->join("LEFT JOIN", "acc_bayar_hutang_det", "acc_bayar_hutang.id = acc_bayar_hutang_det.acc_bayar_hutang_id")
        ->where("acc_bayar_hutang.id", "=", $params['reff_id'])
        ->findAll();

        /*
          Skema jurnal Bayar Hutang
          - Hutang Usaha
              - Kas / bank
              - Pendapatan lain-lain / Beban Lain
        */

        $detJurnalKredit = $detJurnalDebit = $listJurnal = [];
        $totalPembayaran = 0;
        if(!empty($getBayarHutang)){
            // List Detail
            $no=0;
            foreach ($getBayarHutang as $key => $value) {
              $totalPembayaran += $value->bayar;
              $m_akun_id        = $value->m_akun_id;

              $detJurnalKredit[$no]['debit']       = 0;
              $detJurnalKredit[$no]['kredit']      = $value->bayar;
              $detJurnalKredit[$no]['type']        = 'kredit';
              $detJurnalKredit[$no]['tanggal']     = $value->tanggal;
              $detJurnalKredit[$no]['m_lokasi_id'] = $value->m_lokasi_id;
              $detJurnalKredit[$no]['m_akun_id']   = $m_akun_id;
              $detJurnalKredit[$no]['keterangan']  = 'Bayar Hutang (' . $value->kode .') - ' . $value->catatan;
              $detJurnalKredit[$no]['kode']        = $value->kode;
              $detJurnalKredit[$no]['reff_type']   = 'acc_bayar_hutang';
              $detJurnalKredit[$no]['reff_id']     = $value->id;
              $no++;
              // Akun Pembayaran - END

              // Sisa Pembayaran
              $nominal_sisa_total = 0;
              if( $value->is_pelunasan ==  1 ){
                $totalPembayaran    -= $value->sisa_pelunasan;
                $nominal_sisa       = abs($value->sisa_pelunasan);
                $nominal_sisa_total += $nominal_sisa;

                if( $value->sisa_pelunasan > 0 ){
                  $m_akun_id = $pemetaanAkun['Piutang Lain'];

                  $detJurnalDebit[$no]['debit']       = $nominal_sisa;
                  $detJurnalDebit[$no]['kredit']      = 0;
                  $detJurnalDebit[$no]['type']        = 'debit';
                  $detJurnalDebit[$no]['tanggal']     = $value->tanggal;
                  $detJurnalDebit[$no]['m_lokasi_id'] = $value->m_lokasi_id;
                  $detJurnalDebit[$no]['m_akun_id']   = $m_akun_id;
                  $detJurnalDebit[$no]['keterangan']  = 'Bayar Hutang (' . $value->kode .') - ' . $value->catatan;
                  $detJurnalDebit[$no]['kode']        = $value->kode;
                  $detJurnalDebit[$no]['reff_type']   = 'acc_bayar_hutang';
                  $detJurnalDebit[$no]['reff_id']     = $value->id;
                  $no++;

                } else if( $value->sisa_pelunasan < 0 ) {
                  $m_akun_id    = $pemetaanAkun['Hutang Lain'];
                  $detJurnalKredit[$no]['debit']       = 0;
                  $detJurnalKredit[$no]['kredit']      = $nominal_sisa;
                  $detJurnalKredit[$no]['type']        = 'kredit';
                  $detJurnalKredit[$no]['tanggal']     = $value->tanggal;
                  $detJurnalKredit[$no]['m_lokasi_id'] = $value->m_lokasi_id;
                  $detJurnalKredit[$no]['m_akun_id']   = $m_akun_id;
                  $detJurnalKredit[$no]['keterangan']  = 'Bayar Hutang (' . $value->kode .') - ' . $value->catatan;
                  $detJurnalKredit[$no]['kode']        = $value->kode;
                  $detJurnalKredit[$no]['reff_type']   = 'acc_bayar_hutang';
                  $detJurnalKredit[$no]['reff_id']     = $value->id;
                  $no++;
                }

              }
              // Sisa Pembayaran - END
            }
            // List Detail - END

            // Insert Jurnal Hutang Usaha
            $indexJurnal = 0;
            $listJurnal[$indexJurnal] = [
                'debit'       => $totalPembayaran,
                'kredit'      => 0,
                'type'        => 'debit',
                'tanggal'     => $getBayarHutang[0]->tanggal,
                'm_lokasi_id' => $getBayarHutang[0]->m_lokasi_id,
                'm_akun_id'   => $pemetaanAkun['Hutang Usaha'],
                'keterangan'  => 'Bayar Hutang (' . $getBayarHutang[0]->kode .')',
                'kode'        => $getBayarHutang[0]->kode,
                'reff_type'   => 'acc_bayar_hutang',
                'reff_id'     => $getBayarHutang[0]->id,
            ];
            $indexJurnal++;

            // Insert detail
            foreach ($detJurnalDebit as $key => $value) {
                $listJurnal[$indexJurnal] = $value;
                $indexJurnal++;
            }

            foreach ($detJurnalKredit as $key => $value) {
                $listJurnal[$indexJurnal] = $value;
                $indexJurnal++;
            }
            // Insert detail - END

            $totalDebit   = $totalKredit = $totalPembayaran;
            $kodeLokasi   = $getBayarHutang[0]->acc_m_lokasi_kode;
            $m_kontak_id  = $getBayarHutang[0]->m_kontak_id;
        }
    }
    // Jurnal Bayar Hutang - END

    // Jurnal Penjualan
    if($params['reff_type'] == 'inv_penjualan' && !empty($params['reff_id']) ){

        $getDetPenjualan = $db->select("
      inv_penjualan.id as inv_penjualan_id,
      inv_penjualan.acc_m_lokasi_id,
      inv_penjualan.acc_m_kontak_id,
      acc_m_lokasi.kode as acc_m_lokasi_kode,
      FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') as tanggal,
      inv_penjualan.is_ppn,
      inv_penjualan.total,
      inv_penjualan.no_surat_jalan,
      acc_m_kontak.nama as customer,
      inv_m_faktur_pajak.nomor as nomor_fp,
      inv_penjualan_det.*,
      COALESCE(inv_penjualan_det.diskon, 0) AS diskon,
      inv_m_barang.inv_m_kategori_id,
      inv_m_kategori.is_dijual,
      inv_m_kategori.akun_pembelian_id,
      inv_m_kategori.akun_persediaan_brg_id,
      inv_m_kategori.akun_penjualan_id,
      inv_m_kategori.akun_hpp_id
    ")
            ->from("inv_penjualan_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->join('LEFT JOIN', "inv_penjualan", "inv_penjualan.id = inv_penjualan_det.inv_penjualan_id")
            ->join('LEFT JOIN', "acc_m_lokasi", "acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id")
            ->join('LEFT JOIN', "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
            ->join('LEFT JOIN', "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
            ->where("inv_penjualan_det.inv_penjualan_id", "=", $params['reff_id'])
            ->orderBy("inv_penjualan_det.id")
            ->findAll();

        /*
          Skema Jurnal Penjualan
          - Piutang Dagang
          - Potongan penjualan
              - Penjualan
              - PPN Keluaran (Jika ada)
        */

        $detJurnal = $listJurnal = [];
        $totalPenjualan = $totalPPN = 0;
        if(!empty($getDetPenjualan)){
            foreach ($getDetPenjualan as $key => $value) {
                $totalPenjualan += ($value->jumlah * $value->harga);
                $m_akun_id = ($value->is_dijual == 'ya') ? $value->akun_penjualan_id : $value->akun_hpp_id;

                if( !empty($detJurnal[$m_akun_id]) ){
                    @$detJurnal[$m_akun_id]['kredit']      += $value->jumlah * $value->harga;
                } else {
                    $detJurnal[$m_akun_id]['debit']       = 0;
                    $detJurnal[$m_akun_id]['kredit']      = $value->jumlah * $value->harga;
                    $detJurnal[$m_akun_id]['type']        = 'kredit';
                    $detJurnal[$m_akun_id]['tanggal']     = $value->tanggal;
                    $detJurnal[$m_akun_id]['m_lokasi_id'] = $value->acc_m_lokasi_id;
                    $detJurnal[$m_akun_id]['m_akun_id']   = $m_akun_id;
                    $detJurnal[$m_akun_id]['keterangan']  = 'Penjualan (' . $value->no_surat_jalan .') - ' . $getDetPenjualan[0]->customer;
                    $detJurnal[$m_akun_id]['kode']        = $value->no_surat_jalan;
                    $detJurnal[$m_akun_id]['reff_type']   = 'inv_penjualan';
                    $detJurnal[$m_akun_id]['reff_id']     = $value->inv_penjualan_id;
                }
            }


        // Piutang Usaha
        if($getDetPenjualan[0]->is_ppn == 1){
            $totalPPN = round($totalPenjualan*10/100);
            $totalPenjualan += $totalPPN;
        }

        $indexJurnal = 0;
        $listJurnal[$indexJurnal] = [
            'debit'       => $totalPenjualan,
            'kredit'      => 0,
            'type'        => 'debit',
            'tanggal'     => $getDetPenjualan[0]->tanggal,
            'm_lokasi_id' => $getDetPenjualan[0]->acc_m_lokasi_id,
            'm_akun_id'   => $pemetaanAkun['Piutang Usaha'],
            'keterangan'  => 'Penjualan (' . $getDetPenjualan[0]->no_surat_jalan .') - ' . $getDetPenjualan[0]->customer,
            'kode'        => $getDetPenjualan[0]->no_surat_jalan,
            'reff_type'   => 'inv_penjualan',
            'reff_id'     => $getDetPenjualan[0]->inv_penjualan_id,
        ];
        // Piutang Usaha - END

        // Penjualan
        foreach ($detJurnal as $key => $value) {
            $indexJurnal++;
            $listJurnal[$indexJurnal] = $value;
        }

        // Jika ada PPN
        if($getDetPenjualan[0]->is_ppn == 1 && $totalPPN > 0){
            $indexJurnal++;
            $listJurnal[$indexJurnal] = [
                'debit'       => 0,
                'kredit'      => $totalPPN,
                'type'        => 'kredit',
                'tanggal'     => $getDetPenjualan[0]->tanggal,
                'm_lokasi_id' => $getDetPenjualan[0]->acc_m_lokasi_id,
                'm_akun_id'   => $pemetaanAkun['PPN Keluaran'],
                'keterangan'  => 'Penjualan (' . $getDetPenjualan[0]->no_surat_jalan .') - ' . $getDetPenjualan[0]->customer,
                'kode'        => $getDetPenjualan[0]->no_surat_jalan,
                'reff_type'   => 'inv_penjualan',
                'reff_id'     => $getDetPenjualan[0]->inv_penjualan_id,
            ];
        }
        // Penjualan - END
        $totalDebit   = $totalKredit = $totalPenjualan;
        $kodeLokasi   = $getDetPenjualan[0]->acc_m_lokasi_kode;
        $m_kontak_id  = $getDetPenjualan[0]->acc_m_kontak_id;
    } else {
      // Jika dari saldo awal piutang
      $getPenjualan = $db->select("
        inv_penjualan.id as inv_penjualan_id,
        inv_penjualan.acc_m_lokasi_id,
        inv_penjualan.acc_m_kontak_id,
        acc_m_lokasi.kode as acc_m_lokasi_kode,
        FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') as tanggal,
        inv_penjualan.is_ppn,
        inv_penjualan.total,
        inv_penjualan.piutang,
        inv_penjualan.no_surat_jalan,
        inv_penjualan.no_invoice,
        inv_penjualan.m_akun_id,
        acc_m_kontak.nama as customer
      ")
      ->from("inv_penjualan")
      ->join('LEFT JOIN', "acc_m_lokasi", "acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id")
      ->join('LEFT JOIN', "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
      ->where("inv_penjualan.id", "=", $params['reff_id'])
      ->find();
      // Jika dari saldo awal piutang - end

      /*
        Skema jurnal
        Piutang Usaha
            Akun Penjualan
      */
// pd([$params, $getPenjualan]);
      if(!empty($getPenjualan)){
        $indexJurnal = 0;
        $totalPenjualan = $getPenjualan->piutang;

        $listJurnal[$indexJurnal] = [
            'debit'       => $totalPenjualan,
            'kredit'      => 0,
            'type'        => 'debit',
            'tanggal'     => $getPenjualan->tanggal,
            'm_lokasi_id' => $getPenjualan->acc_m_lokasi_id,
            'm_akun_id'   => $pemetaanAkun['Piutang Usaha'],
            'keterangan'  => 'Saldo awal piutang (' . $getPenjualan->no_invoice .') - ' . $getPenjualan->customer,
            'kode'        => $getPenjualan->no_invoice,
            'reff_type'   => 'inv_penjualan',
            'reff_id'     => $getPenjualan->inv_penjualan_id,
        ];
        $indexJurnal++;

        $listJurnal[$indexJurnal] = [
            'debit'       => 0,
            'kredit'      => $totalPenjualan,
            'type'        => 'kredit',
            'tanggal'     => $getPenjualan->tanggal,
            'm_lokasi_id' => $getPenjualan->acc_m_lokasi_id,
            'm_akun_id'   => $getPenjualan->m_akun_id,
            'keterangan'  => 'Saldo awal piutang (' . $getPenjualan->no_invoice .') - ' . $getPenjualan->customer,
            'kode'        => $getPenjualan->no_invoice,
            'reff_type'   => 'inv_penjualan',
            'reff_id'     => $getPenjualan->inv_penjualan_id,
        ];

        $totalDebit   = $totalKredit = $totalPenjualan;
        $kodeLokasi   = $getPenjualan->acc_m_lokasi_kode;
        $m_kontak_id  = $getPenjualan->acc_m_kontak_id;

      }
    }


    }
    // pd([
    //   $listJurnal,
    //   $getDetPenjualan,
    //   $kodeLokasi,
    //   $m_kontak_id,
    // ]);
    // Jurnal Penjualan - END

    // Jurnal Retur Penjualan
    if($params['reff_type'] == 'inv_retur_penjualan' && !empty($params['reff_id']) ){
        $getDetRetur = $db->select("
      inv_retur_penjualan.id as inv_retur_penjualan_id,
      acc_m_kontak.acc_m_lokasi_id,
      inv_retur_penjualan.acc_m_kontak_id,
      acc_m_lokasi.kode as acc_m_lokasi_kode,
      FROM_UNIXTIME(inv_retur_penjualan.tanggal, '%Y-%m-%d') as tanggal,
      inv_retur_penjualan.no_nota,
      inv_retur_penjualan.no_faktur_pajak,
      inv_retur_penjualan_det.*,
      inv_m_kategori.is_dijual,
      inv_m_kategori.akun_pembelian_id,
      inv_m_kategori.akun_persediaan_brg_id,
      inv_m_kategori.akun_penjualan_id,
      inv_m_kategori.akun_hpp_id
    ")
            ->from("inv_retur_penjualan_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_retur_penjualan_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_retur_penjualan", "inv_retur_penjualan.id = inv_retur_penjualan_det.inv_retur_penjualan_id")
            ->join('LEFT JOIN', "acc_m_kontak", "inv_retur_penjualan.acc_m_kontak_id = acc_m_kontak.id")
            ->join('LEFT JOIN', "acc_m_lokasi", "acc_m_lokasi.id = inv_retur_penjualan.acc_m_lokasi_id")
            ->join('LEFT JOIN', "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->where("inv_retur_penjualan_det.inv_retur_penjualan_id", "=", $params['reff_id'])
            ->orderBy("inv_retur_penjualan_det.id")
            ->findAll();

        /*
          Skema jurnal Retur Penjualan
          - Penjualan
          - PPn Keluaran (Jika ada PPn)
              - Piutang Dagang

        */
        $detJurnal = $listJurnal = [];
        $totalRetur = $totalPPN = 0;

        if(!empty($getDetRetur)){
            foreach ($getDetRetur as $key => $value) {
                $nominalRetur = ($value->jumlah_retur * $value->harga_retur);
                if($nominalRetur > 0){
                    $totalRetur   += $nominalRetur;
                    $m_akun_id    = ($value->is_dijual == 'ya') ? $value->akun_penjualan_id : $value->akun_hpp_id;

                    if( !empty($detJurnal[$m_akun_id]) ){
                        @$detJurnal[$m_akun_id]['debit']     += $nominalRetur;
                    } else {
                        $detJurnal[$m_akun_id]['debit']       = $nominalRetur;
                        $detJurnal[$m_akun_id]['kredit']      = 0;
                        $detJurnal[$m_akun_id]['type']        = 'debit';
                        $detJurnal[$m_akun_id]['tanggal']     = $value->tanggal;
                        $detJurnal[$m_akun_id]['m_lokasi_id'] = $value->acc_m_lokasi_id;
                        $detJurnal[$m_akun_id]['m_akun_id']   = $m_akun_id;
                        $detJurnal[$m_akun_id]['keterangan']  = 'Retur Penjualan (' . $value->no_nota .')';
                        $detJurnal[$m_akun_id]['kode']        = $value->no_nota;
                        $detJurnal[$m_akun_id]['reff_type']   = 'inv_retur_penjualan';
                        $detJurnal[$m_akun_id]['reff_id']     = $value->inv_retur_penjualan_id;
                    }
                }
            }

            // Memasukkan list jurnal Penjualan
            $indexJurnal = 0;
            foreach ($detJurnal as $key => $value) {
                $listJurnal[$indexJurnal] = $value;
                $indexJurnal++;
            }

            // PPN Jurnal Keluaran
            $totalPPN = round($totalRetur*10/100);
            $listJurnal[$indexJurnal] = [
                'debit'       => $totalPPN,
                'kredit'      => 0,
                'type'        => 'debit',
                'tanggal'     => $getDetRetur[0]->tanggal,
                'm_lokasi_id' => $getDetRetur[0]->acc_m_lokasi_id,
                'm_akun_id'   => $pemetaanAkun['PPN Keluaran'],
                'keterangan'  => 'Retur Penjualan (' . $getDetRetur[0]->no_nota .')',
                'kode'        => $getDetRetur[0]->no_nota,
                'reff_type'   => 'inv_retur_penjualan',
                'reff_id'     => $getDetRetur[0]->inv_retur_penjualan_id,
            ];

            // Insert Jurnal Piutang
            $indexJurnal++;
            $listJurnal[$indexJurnal] = [
                'debit'       => 0,
                'kredit'      => $totalRetur + $totalPPN,
                'type'        => 'kredit',
                'tanggal'     => $getDetRetur[0]->tanggal,
                'm_lokasi_id' => $getDetRetur[0]->acc_m_lokasi_id,
                'm_akun_id'   => $pemetaanAkun['Piutang Usaha'],
                'keterangan'  => 'Retur Penjualan (' . $getDetRetur[0]->no_nota .')',
                'kode'        => $getDetRetur[0]->no_nota,
                'reff_type'   => 'inv_retur_penjualan',
                'reff_id'     => $getDetRetur[0]->inv_retur_penjualan_id,
            ];

            $totalDebit = $totalKredit = ($totalRetur+$totalPPN);
            $kodeLokasi = $getDetRetur[0]->acc_m_lokasi_kode;
            $m_kontak_id = $getDetRetur[0]->acc_m_kontak_id;
        }

        else{


        }
    }
    // Jurnal Retur Penjualan - END

    // Jurnal Bayar Piutang
    if($params['reff_type'] == 'acc_bayar_piutang' && !empty($params['reff_id']) ){
        $getBayarPiutang = $db->select("
          acc_bayar_piutang.*,
          acc_bayar_piutang_det.m_akun_id,
          acc_bayar_piutang_det.bayar,
          acc_bayar_piutang_det.is_pelunasan,
          acc_bayar_piutang_det.sisa_pelunasan,
          acc_bayar_piutang_det.catatan,
          acc_m_lokasi.kode as acc_m_lokasi_kode
        ")
        ->from("acc_bayar_piutang")
        ->join("LEFT JOIN", "acc_m_lokasi", "acc_m_lokasi.id = acc_bayar_piutang.m_lokasi_id")
        ->join("LEFT JOIN", "acc_bayar_piutang_det", "acc_bayar_piutang.id = acc_bayar_piutang_det.acc_bayar_piutang_id")
        ->where("acc_bayar_piutang.id", "=", $params['reff_id'])
        ->findAll();

        /*
          Skema jurnal Bayar Hutang
          - Kas / bank
              - Piutang Usaha
        */

        $detJurnal = $listJurnal = [];
        $totalRetur = $totalPPN = 0;

        if(!empty($getBayarPiutang)){
            // List Detail
            $totalPembayaran = $no = 0;
            $detJurnalKredit = $detJurnalDebit = [];
            foreach ($getBayarPiutang as $key => $value) {
              $totalPembayaran += $value->bayar;
              $m_akun_id        = $value->m_akun_id;

              // Akun Pembayaran
              $detJurnalDebit[$no]['debit']       = $value->bayar;
              $detJurnalDebit[$no]['kredit']      = 0;
              $detJurnalDebit[$no]['type']        = 'debit';
              $detJurnalDebit[$no]['tanggal']     = $value->tanggal;
              $detJurnalDebit[$no]['m_lokasi_id'] = $value->m_lokasi_id;
              $detJurnalDebit[$no]['m_akun_id']   = $m_akun_id;
              $detJurnalDebit[$no]['keterangan']  = 'Bayar Piutang (' . $value->kode .') - ' . $value->catatan;
              $detJurnalDebit[$no]['kode']        = $value->kode;
              $detJurnalDebit[$no]['reff_type']   = 'acc_bayar_piutang';
              $detJurnalDebit[$no]['reff_id']     = $value->id;
              $no++;
              // Akun Pembayaran - END

              // Sisa Pembayaran
              $nominal_sisa_total = 0;
              if( $value->is_pelunasan ==  1 ){
                $totalPembayaran    -= $value->sisa_pelunasan;
                $nominal_sisa       = abs($value->sisa_pelunasan);
                $nominal_sisa_total += $nominal_sisa;

                if( $value->sisa_pelunasan > 0 ){
                  $m_akun_id = $pemetaanAkun['Hutang Lain'];
                  $detJurnalKredit[$no]['debit']       = 0;
                  $detJurnalKredit[$no]['kredit']      = $nominal_sisa;
                  $detJurnalKredit[$no]['type']        = 'kredit';
                  $detJurnalKredit[$no]['tanggal']     = $value->tanggal;
                  $detJurnalKredit[$no]['m_lokasi_id'] = $value->m_lokasi_id;
                  $detJurnalKredit[$no]['m_akun_id']   = $m_akun_id;
                  $detJurnalKredit[$no]['keterangan']  = 'Bayar Piutang (' . $value->kode .') - ' . $value->catatan;
                  $detJurnalKredit[$no]['kode']        = $value->kode;
                  $detJurnalKredit[$no]['reff_type']   = 'acc_bayar_piutang';
                  $detJurnalKredit[$no]['reff_id']     = $value->id;
                  $no++;

                } else if( $value->sisa_pelunasan < 0 ) {
                  $m_akun_id    = $pemetaanAkun['Piutang Lain'];
                  $detJurnalDebit[$no]['debit']       = $nominal_sisa;
                  $detJurnalDebit[$no]['kredit']      = 0;
                  $detJurnalDebit[$no]['type']        = 'debit';
                  $detJurnalDebit[$no]['tanggal']     = $value->tanggal;
                  $detJurnalDebit[$no]['m_lokasi_id'] = $value->m_lokasi_id;
                  $detJurnalDebit[$no]['m_akun_id']   = $m_akun_id;
                  $detJurnalDebit[$no]['keterangan']  = 'Bayar Piutang (' . $value->kode .') - ' . $value->catatan;
                  $detJurnalDebit[$no]['kode']        = $value->kode;
                  $detJurnalDebit[$no]['reff_type']   = 'acc_bayar_piutang';
                  $detJurnalDebit[$no]['reff_id']     = $value->id;
                  $no++;
                }

              }
              // Sisa Pembayaran - END
            }
            // List Detail - END

            // Insert Jurnal KAS BANK
            $indexJurnal = 0;

            // Insert detail
            if( !empty($detJurnalDebit) ){
              foreach ($detJurnalDebit as $key => $value) {
                $listJurnal[$indexJurnal] = $value;
                $indexJurnal++;
              }
            }

            $listJurnal[$indexJurnal] = [
              'debit'       => 0,
              'kredit'      => $totalPembayaran,
              'type'        => 'Kredit',
              'tanggal'     => $getBayarPiutang[0]->tanggal,
              'm_lokasi_id' => $getBayarPiutang[0]->m_lokasi_id,
              'm_akun_id'   => $pemetaanAkun['Piutang Usaha'],
              'keterangan'  => 'Bayar Piutang (' . $getBayarPiutang[0]->kode .')',
              'kode'        => $getBayarPiutang[0]->kode,
              'reff_type'   => 'acc_bayar_piutang',
              'reff_id'     => $getBayarPiutang[0]->id,
            ];
            $indexJurnal++;

            if( !empty($detJurnalKredit) ){
              foreach ($detJurnalKredit as $key => $value) {
                $listJurnal[$indexJurnal] = $value;
                $indexJurnal++;
              }
            }
            // Insert detail - END

            $totalDebit   = $totalKredit = $totalPembayaran;
            $kodeLokasi   = $getBayarPiutang[0]->acc_m_lokasi_kode;
            $m_kontak_id  = $getBayarPiutang[0]->m_kontak_id;
        }
    }
    // Jurnal Bayar Piutang - END

    // Insert List Jurnal
    if( !empty($listJurnal) ){
        // Delete Record Lama
        $jurnal_umum = $db->find("SELECT id FROM acc_jurnal WHERE reff_type='".$params['reff_type']."' AND reff_id='".$params['reff_id']."'");
        if(!empty($jurnal_umum)){
            $db->delete('acc_jurnal', ['id' => $jurnal_umum->id] );
            $db->delete('acc_jurnal_det', ['acc_jurnal_id' => $jurnal_umum->id] );
            $db->delete('acc_trans_detail', [ 'reff_type' => 'acc_jurnal', 'reff_id'=>$jurnal_umum->id ]);
        }

        $db->delete('acc_trans_detail', [ 'reff_type' =>$params['reff_type'], 'reff_id'=>$params['reff_id'] ] );
        // Delete Record Lama - End

        $paramsJU = [
            'tanggal'       => $listJurnal[0]['tanggal'],
            'm_lokasi_id'   => [
                'id'    => $listJurnal[0]['m_lokasi_id'],
                'kode'  => $kodeLokasi
            ],
            'm_kontak_id'   => $m_kontak_id,
            'total_debit'   => $totalDebit,
            'total_kredit'  => $totalKredit,
            'keterangan'    => $listJurnal[0]['keterangan'],
            'detail'        => $listJurnal,
            'reff_type'     => $params['reff_type'],
            'reff_id'       => $params['reff_id'],
        ];

        simpanJurnalUmum($paramsJU);
    }
    // Insert List Jurnal - END
}

function simpanJurnalUmum($params=[]){
    $db = config('DB');
    $db = new Cahkampung\Landadb($db['db']);

    $format = $db->select("format_jurnal")->from("acc_m_setting")->find();
    $get_bulan  = date("m", strtotime($params['tanggal']));
    $get_tahun  = date("Y", strtotime($params['tanggal']));
    $kode       = generateNoTransaksi("jurnal", $params['m_lokasi_id']['kode'], NULL, $get_bulan, $get_tahun);

    $jurnal['no_urut']              = (empty($kode)) ? 1 : ((int) substr($kode, -5));
    $jurnal['m_lokasi_id']          = $params['m_lokasi_id']['id'];
    $jurnal['m_lokasi_jurnal_id']   = $jurnal['m_lokasi_id'];
    $jurnal['tanggal']              = date("Y-m-d h:i:s", strtotime($params['tanggal']));
    $jurnal['total_debit']          = $params['total_debit'];
    $jurnal['total_kredit']         = $params['total_kredit'];
    $jurnal['reff_type']            = $params['reff_type'];
    $jurnal['reff_id']              = $params['reff_id'];

    $jurnal['no_transaksi'] = $kode;
    $model = $db->insert("acc_jurnal", $jurnal);

    if( !empty($params['detail']) ){
        $juQuery      = 'INSERT INTO acc_jurnal_det (m_akun_id, m_lokasi_id, kredit, debit, acc_jurnal_id, keterangan) VALUES ';
        $tadetJUQuery = 'INSERT INTO acc_trans_detail (m_akun_id, kode, tanggal, debit, kredit, keterangan, reff_type, reff_id, m_lokasi_id, m_lokasi_jurnal_id, m_kontak_id, created_at, created_by, modified_at, modified_by) VALUES ';
        // $tadetQuery   = 'INSERT INTO acc_trans_detail (m_akun_id, kode, tanggal, debit, kredit, keterangan, reff_type, reff_id, m_lokasi_id, m_lokasi_jurnal_id, created_at, created_by, modified_at, modified_by) VALUES ';
        foreach ($params['detail'] as $key => $value) {
            // Params for Detail Jurnal Umum
            $juParams = [
                'm_akun_id'       => $value['m_akun_id'],
                'm_lokasi_id'     => $value['m_lokasi_id'],
                'kredit'          => $value['kredit'],
                'debit'           => $value['debit'],
                'acc_jurnal_id'   => $model->id,
                'keterangan'      => $value['keterangan']
            ];
            $juQuery .= "('". implode("','", $juParams) ."'),";

            $tadetJUParams = [
                'm_akun_id'       => $value['m_akun_id'],
                'kode'            => $model->no_transaksi,
                'tanggal'         => $value['tanggal'],
                'debit'           => $value['debit'],
                'kredit'          => $value['kredit'],
                'keterangan'      => $value['keterangan'],
                'reff_type'       => "acc_jurnal",
                'reff_id'         => $model->id,
                'm_lokasi_id'     => $value['m_lokasi_id'],
                'm_lokasi_jurnal_id'     => $value['m_lokasi_id'],
                'm_kontak_id'     => $params['m_kontak_id'],
                'created_at'      => $model->created_at,
                'created_by'      => $model->created_by,
                'modified_at'     => $model->modified_at,
                'modified_by'     => $model->modified_by,
            ];
            $tadetJUQuery .= "('". implode("','", $tadetJUParams) ."'),";
            // Params for Detail Jurnal Umum - END

            // Params for trans_detail
            // $tadetParams = [
            //   'm_akun_id'       => $value['m_akun_id'],
            //   'kode'            => $value['kode'],
            //   'tanggal'         => $value['tanggal'],
            //   'debit'           => $value['debit'],
            //   'kredit'          => $value['kredit'],
            //   'keterangan'      => $value['keterangan'],
            //   'reff_type'       => $value['reff_type'],
            //   'reff_id'         => $value['reff_id'],
            //   'm_lokasi_id'     => $value['m_lokasi_id'],
            //   'm_lokasi_jurnal_id'     => $value['m_lokasi_id'],
            //   'created_at'      => $model->created_at,
            //   'created_by'      => $model->created_by,
            //   'modified_at'     => $model->modified_at,
            //   'modified_by'     => $model->modified_by,
            // ];
            // $tadetQuery .= "('". implode("','", $tadetParams) ."'),";
            // Params for trans_detail - END
        }

        //Remove the last character using substr
        $juQuery = substr($juQuery, 0, -1) . ';';
        $juQuery = $db->run($juQuery);

        $tadetJUQuery = substr($tadetJUQuery, 0, -1) . ';';
        $tadetJUQuery = $db->run($tadetJUQuery);

        // $tadetQuery = substr($tadetQuery, 0, -1) . ';';
        // $tadetQuery = $db->run($tadetQuery);

    }
}

function hapusJurnalUmum($params=[]){
    $db = config('DB');
    $db = new Cahkampung\Landadb($db['db']);

    if( empty($params['reff_type']) || empty($params['reff_id']) ){
        return;
    }

    $jurnal_umum = $db->find("SELECT id FROM acc_jurnal
    WHERE reff_type='". $params['reff_type'] ."'
    AND reff_id='". $params['reff_id'] ."'
  ");

    if(!empty($jurnal_umum)){
        $db->delete('acc_jurnal', ['id' => $jurnal_umum->id] );
        $db->delete('acc_jurnal_det', ['acc_jurnal_id' => $jurnal_umum->id] );
        $db->delete('acc_trans_detail', [
            'reff_type' => 'acc_jurnal',
            'reff_id'   => $jurnal_umum->id
        ]);
    }
}

function getJurnalProsesAkhir($params=[]){
  $db = config('DB');
  $db = new Cahkampung\Landadb($db['db']);
  date_default_timezone_set('Asia/Jakarta');

  if(empty($params['bulan'])){
    return [];
  }

  $params['bulan_akhir']    = date("Y-m-01", strtotime($params['bulan']. " -1 Month") );
  $params['bulan_awal']     = date("Y-m-t", strtotime($params['bulan']) );
  $bulan_terpilih           = date("Y-m", strtotime($params['bulan']) );
  $listHPP = $listKategori  = [];

  /*
    Kamu harus dapatkan Stoknya pada bulan itu dan Harga nya
    Harga rata-rata itu adalah HPP nya...
    Trus lakkukan pengurangan pada stok dan ambil HPP nya.......
  */

  // Get Stok Masuk & Keluar dalam rentang 2 bulan
  $db->select("
    FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
    tanggal,
    inv_m_barang_id,
    inv_m_kategori.id as inv_m_kategori_id,
    jumlah_masuk,
    harga_masuk,
    jumlah_keluar,
    jenis_kas,
    trans_tipe,
    trans_id,
    inv_kartu_stok.kode
    ")
  ->from("inv_kartu_stok")
  ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
  ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
  ->where("tanggal", "<=", strtotime($params['bulan_akhir']))
  ->andWhere("tanggal", ">=", strtotime($params['bulan_awal']))
  ->andWhere("inv_m_barang.inv_m_kategori_id", "=", 3); // Pilih hanya kategori barang Dagangan

  $db->orderBy("tanggal ASC");
  $stokRentang = $db->findAll();
  // Get Stok Masuk & Keluar - END

  // Select stoknya
  $db->select("
    FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
    tanggal,
    inv_m_barang_id,
    inv_m_kategori.id as inv_m_kategori_id,
    jumlah_masuk,
    harga_masuk,
    jenis_kas,
    jumlah_keluar,
    trans_tipe,
    trans_id,
    inv_kartu_stok.kode
    ")
  ->from("inv_kartu_stok")
  ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_kartu_stok.inv_m_barang_id")
  ->join("JOIN", "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
  ->where("tanggal", "<", strtotime($params['bulan_awal']))
  ->andWhere("inv_m_barang.inv_m_kategori_id", "=", 3); // Pilih hanya barang dagangan saja

  $db->orderBy("tanggal ASC");
  $getStokLalu = $db->findAll();

  // Select stoknya - END
  $allStok = array_merge($stokRentang, $getStokLalu);

  $arrayStok = [];
  $saldo_beli_all = $saldo_jual_all = $saldo_retur_beli_all = 0;
  foreach ($allStok as $key => $value) {
    $arrayStok[$value->inv_m_barang_id]['inv_m_kategori_id']                      = $value->inv_m_kategori_id;
    $arrayStok[$value->inv_m_barang_id]['bulan'][$value->bulan]['format_bulan']   = date("M y", strtotime($value->bulan . '-01'));
    $arrayStok[$value->inv_m_barang_id]['bulan_keluar']                           = $value->bulan;
    @$arrayStok[$value->inv_m_barang_id]['bulan'][$value->bulan]['masuk']         += $value->jumlah_masuk;

    @$arrayStok[$value->inv_m_barang_id]['keluar']  += $value->jumlah_keluar;
    @$arrayStok[$value->inv_m_barang_id]['masuk']   += $value->jumlah_masuk;

    if($value->jenis_kas == 'masuk' && $value->harga_masuk > 0){
      $arrayStok[$value->inv_m_barang_id]['bulan'][$value->bulan]['harga_masuk'][] = $value->harga_masuk;
    }
  }

  // Get Stok Sebelumnya - END

  // Pengurangan Stok Perbulan
  foreach ($arrayStok as $key => $value) {
    $jumlah_keluar = $value['keluar'];
    ksort($value['bulan']);
    foreach ($value['bulan'] as $key2 => $value2) {

      if($value2['masuk'] > 0){
        if($value2['masuk'] > $jumlah_keluar){
          $arrayStok[$key]['bulan'][$key2]['keluar']  = $jumlah_keluar;
          $arrayStok[$key]['bulan'][$key2]['sisa']    = $value2['masuk'] - $jumlah_keluar;

          if( $arrayStok[$key]['bulan'][$key2]['keluar'] > 0 ){
            $listHPP[$value['inv_m_kategori_id']][$value['bulan_keluar']][$key][] = [
                'jumlah'          => $arrayStok[$key]['bulan'][$key2]['keluar'],
                'inv_m_barang_id' => $key,
                'bulan_stok'    => $key2,
              ];
          }

          $jumlah_keluar -= $arrayStok[$key]['bulan'][$key2]['keluar'];

        } else if($value2['masuk'] == $jumlah_keluar){
          $arrayStok[$key]['bulan'][$key2]['keluar']  = $value2['masuk'];
          $arrayStok[$key]['bulan'][$key2]['sisa']    = 0;

          if( $arrayStok[$key]['bulan'][$key2]['keluar'] > 0 ){
            $listHPP[$value['inv_m_kategori_id']][$value['bulan_keluar']][$key][] = [
                'jumlah'          => $arrayStok[$key]['bulan'][$key2]['keluar'],
                'inv_m_barang_id' => $key,
                'bulan_stok'    => $key2,
              ];
          }

          $jumlah_keluar -= $arrayStok[$key]['bulan'][$key2]['keluar'];

        } else if($value2['masuk'] < $jumlah_keluar){
          $arrayStok[$key]['bulan'][$key2]['keluar']  = $value2['masuk'];
          $arrayStok[$key]['bulan'][$key2]['sisa']    = 0;

          if( $arrayStok[$key]['bulan'][$key2]['keluar'] > 0 ){
            $listHPP[$value['inv_m_kategori_id']][$value['bulan_keluar']][$key][] = [
                'jumlah'          => $arrayStok[$key]['bulan'][$key2]['keluar'],
                'inv_m_barang_id' => $key,
                'bulan_stok'    => $key2,
              ];
          }

          $jumlah_keluar -= $arrayStok[$key]['bulan'][$key2]['keluar'];
        }

      }

      if(empty($arrayStok[$key]['bulan'][$key2]['sisa']))
        $arrayStok[$key]['bulan'][$key2]['sisa']    = 0;

      if(isset($value2['harga_masuk'])){
        $arrayStok[$key]['bulan'][$key2]['harga_masuk_avg'] = array_sum($value2['harga_masuk']) / count($value2['harga_masuk']);
        $arrayStok[$key]['bulan'][$key2]['harga_masuk_avg'] = number_format($arrayStok[$key]['bulan'][$key2]['harga_masuk_avg'], 2, '.', '');
      } else {
        $arrayStok[$key]['bulan'][$key2]['harga_masuk_avg'] = 0;
      }

      $arrayStok[$key]['bulan'][$key2]['saldo_rp'] = $arrayStok[$key]['bulan'][$key2]['harga_masuk_avg'] * $arrayStok[$key]['bulan'][$key2]['sisa'];
      $arrayStok[$key]['bulan'][$key2]['saldo_rp'] = number_format($arrayStok[$key]['bulan'][$key2]['saldo_rp'], 2, ".", '');
      @$arrayStok[$key]['saldo_rp']                += $arrayStok[$key]['bulan'][$key2]['saldo_rp'];

    }
    krsort($arrayStok[$key]['bulan']);
    $arrayStok[$key]['saldo_akhir'] = $value['masuk'] - $value['keluar'];
  }

  // Pengurangan Stok Perbulan - END

  // list kategori + akun
  $db->select("
  inv_m_kategori.id,
  inv_m_kategori.akun_pembelian_id,
  inv_m_kategori.akun_penjualan_id,
  inv_m_kategori.akun_hpp_id,
  inv_m_kategori.akun_persediaan_brg_id
  ")
  ->from("inv_m_kategori")
  ->where("inv_m_kategori.is_dijual", "=", 'ya')
  ->andWhere("id", "=", 3); // Pilih hanya kategori barang dagangan saja
  $getAkunKategori = $db->findAll();

  $listAkunKategori = [];
  foreach ($getAkunKategori as $key => $value) {
    $listAkunKategori[$value->id] = [
      'akun_pembelian_id'       => $value->akun_pembelian_id,
      'akun_penjualan_id'       => $value->akun_penjualan_id,
      'akun_hpp_id'             => $value->akun_hpp_id,
      'akun_persediaan_brg_id'  => $value->akun_persediaan_brg_id,
    ];
  }
  // list kategori + akun - END

  // Inisiasi HPP
  $detJurnal = $totalPerkategori = [];
  foreach ($listHPP as $key => $value) { // kategori
    foreach ($value as $key2 => $value2) { // bulan
      foreach ($value2 as $key3 => $value3) { // barang
        foreach ($value3 as $key4 => $value4) { // list barang
          // Inisiasi HPP
          // kategori, bulan, barang, index
          $listHPP[$key][$key2][$key3][$key4]['hpp'] = isset($arrayStok[$key3]['bulan'][$value4['bulan_stok']]['harga_masuk_avg']) ? $arrayStok[$key3]['bulan'][$value4['bulan_stok']]['harga_masuk_avg'] :  0;

          // total per kategori
          @$totalPerkategori[$key] += $listHPP[$key][$key2][$key3][$key4]['hpp'] * $value4['jumlah'];
        }
      }
    }
  }
  //
  // pd([$totalPerkategori,$arrayStok]);

  $totalAllKategori = 0;
  foreach ($listAkunKategori as $key => $value) {
    $totalAllKategori += isset($totalPerkategori[$key]) ? $totalPerkategori[$key] : 0;
    // Debit
    if(isset($totalPerkategori[$key]) && isset($totalPerkategori[$key]) > 0){
      $m_akun_id = $value['akun_hpp_id'];
      if($value['akun_hpp_id'] == '' || $value['akun_persediaan_brg_id']== ''){
        pd([$key, $value]);
      }
      if( !empty($detJurnal[$value['akun_hpp_id']]) ){
        @$detJurnal[$value['akun_hpp_id']]['debit']    += isset($totalPerkategori[$key]) ? $totalPerkategori[$key] : 0;
      } else {
        $detJurnal[$value['akun_hpp_id']]['debit']       = isset($totalPerkategori[$key]) ? $totalPerkategori[$key] : 0;
        $detJurnal[$value['akun_hpp_id']]['kredit']      = 0;
        $detJurnal[$value['akun_hpp_id']]['type']        = 'debit';
        $detJurnal[$value['akun_hpp_id']]['tanggal']     = $params['bulan_awal'];
        $detJurnal[$value['akun_hpp_id']]['m_lokasi_id'] = 1;
        $detJurnal[$value['akun_hpp_id']]['m_akun_id']   = $value['akun_hpp_id'];
        $detJurnal[$value['akun_hpp_id']]['keterangan']  = 'Tutup Periode ' . date("F Y", strtotime($params['bulan_awal']));
        $detJurnal[$value['akun_hpp_id']]['kode']        = 'Tutup Periode';
        $detJurnal[$value['akun_hpp_id']]['reff_type']   = 'inv_proses_akhir';
        $detJurnal[$value['akun_hpp_id']]['reff_id']     = 1;
      }

      // Kredit
      $m_akun_id = $value['akun_persediaan_brg_id'];
      if( !empty($detJurnal[$value['akun_persediaan_brg_id']]) ){
        @$detJurnal[$value['akun_persediaan_brg_id']]['kredit']     += isset($totalPerkategori[$key]) ? $totalPerkategori[$key] : 0;
      } else {
        $detJurnal[$value['akun_persediaan_brg_id']]['debit']       = 0;
        $detJurnal[$value['akun_persediaan_brg_id']]['kredit']      = isset($totalPerkategori[$key]) ? $totalPerkategori[$key] : 0;
        $detJurnal[$value['akun_persediaan_brg_id']]['type']        = 'kredit';
        $detJurnal[$value['akun_persediaan_brg_id']]['tanggal']     = $params['bulan_awal'];
        $detJurnal[$value['akun_persediaan_brg_id']]['m_lokasi_id'] = 1;
        $detJurnal[$value['akun_persediaan_brg_id']]['m_akun_id']   = $value['akun_persediaan_brg_id'];
        $detJurnal[$value['akun_persediaan_brg_id']]['keterangan']  = 'Tutup Periode ' . date("F Y", strtotime($params['bulan_awal']));
        $detJurnal[$value['akun_persediaan_brg_id']]['kode']        = 'Tutup Periode';
        $detJurnal[$value['akun_persediaan_brg_id']]['reff_type']   = 'inv_proses_akhir';
        $detJurnal[$value['akun_persediaan_brg_id']]['reff_id']     = 1;
      }

    }
  }

  $index=0;
  $resJurnal = [];
  foreach ($detJurnal as $key => $value) {
    $m_akun_nama = $db->find("SELECT kode, nama FROM acc_m_akun WHERE id=" . $value['m_akun_id']);
    $detJurnal[$key]['m_akun_nama'] = !empty($m_akun_nama->nama) ? $m_akun_nama->kode .' - '. $m_akun_nama->nama : '';
    $resJurnal[$index] = $detJurnal[$key];
    $index++;
  }

  return $resJurnal;
}

function saveJurnalProsesAkhir($params=[]){
  $db = config('DB');
  $db = new Cahkampung\Landadb($db['db']);
  date_default_timezone_set('Asia/Jakarta');


}

function getAkunTipe($params=[]){
  $db = config('DB');
  $db = new Cahkampung\Landadb($db['db']);

  $db->select("
    acc_m_akun.*
  ")
  ->from("acc_m_akun")
  ->where("acc_m_akun.is_deleted", "=", 0)
  ->andWhere("acc_m_akun.is_tipe", "=", 1)
  ->orderBy('acc_m_akun.kode');

  $models = $db->findAll();

  $listAkun = [];
  if( !empty($models) ){
    foreach ($models as $key => $value) {
      $listAkun[$value->id] = (array)$value;
    }
  }

  return $listAkun;
}

function getIndonesianTimezone(){
  date_default_timezone_set('Asia/Jakarta');
  $now      = new DateTime();
  $mins     = $now->getOffset() / 60;

  $sgn      = ($mins < 0 ? -1 : 1);
  $mins     = abs($mins);
  $hrs      = floor($mins / 60);
  $mins     -= $hrs * 60;
  $offset   = sprintf('%+d:%02d', $hrs*$sgn, $mins);
  return $offset;
}
