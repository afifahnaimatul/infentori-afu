<?php

$app->post('/t_penjualan/generatePenjualan', function ($request, $response) {
    $params = $request->getParams();

//    print_r($params);die;

    $invoice = generatePenjualan('invoice', 0, 0);
    $surat_jalan = generatePenjualan('surat_jalan', 0, $params['lokasi']['id']);

    return successResponse($response, [
        'invoice' => $invoice,
        'surat_jalan' => $surat_jalan,
    ]);
});

$app->get('/t_penjualan/getLastData', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

//    $data = $db->find("SELECT inv_penjualan.*, acc_m_lokasi.nama as namaLokasi, acc_m_lokasi.kode as kodeLokasi FROM inv_penjualan JOIN acc_m_lokasi ON acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id ORDER BY id DESC");

    $data = isset($_SESSION['user']['temp']) ? $_SESSION['user']['temp'] : null;

    return successResponse($response, $data);
});

$app->get('/t_penjualan/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    date_default_timezone_set("Asia/Jakarta");

    if (isset($params['bulan']) && !empty($params['bulan'])) {
        $bulan_awal = date("Y-m-01", strtotime($params['bulan']));
        $bulan_akhir = date("Y-m-t", strtotime($params['bulan']));
    }

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("
      inv_penjualan.*,
      m_user.nama as pembuat,
      inv_m_faktur_pajak.nomor,
      acc_m_akun.kode as kodeAkun,
      acc_m_akun.nama as namaAkun
    ")
    ->from("inv_penjualan")
    ->join("LEFT JOIN", "m_user", "m_user.id = inv_penjualan.created_by")
    ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
    ->join("LEFT JOIN", "acc_m_lokasi", "acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id")
    ->join("JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
    ->join("LEFT JOIN", "acc_m_akun", "acc_m_akun.id = inv_penjualan.m_akun_id");

    if (isset($params['filter'])) {
        $filter = (array) json_decode($params['filter']);
        foreach ($filter as $key => $val) {
            if ($key == 'is_deleted') {
                $db->where("is_deleted", '=', $val);
            } else {
                $db->where($key, 'like', $val);
            }
        }
    }

    if (isset($params['sort'])) {
        $sort = $params['sort'];
        if (isset($params['order'])) {
            if ($params['order'] == "false") {
                $sort .= " ASC";
            } else {
                $sort .= " DESC";
            }
        }
        $db->orderBy($sort);
    }

    /** Set limit */
    if (isset($params['limit']) && !empty($params['limit'])) {
        $db->limit($params['limit']);
    }
    /** Set offset */
    if (isset($params['offset']) && !empty($params['offset'])) {
        $db->offset($params['offset']);
    }

    // Hanya view yg belum ditutup
    if (!isset($params['bulan'])) {
        $db->customWhere("inv_penjualan.inv_proses_akhir_id IS NULL", "AND");
    } else {
        $db->customWhere("FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') >= '" . $bulan_awal . "'", "AND");
        $db->customWhere("FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') <= '" . $bulan_akhir . "'", "AND");
    }

    $db->where("inv_penjualan.tipe", "=", 'penjualan');

    $models = $db->orderBy("acc_m_lokasi.nama ASC, inv_penjualan.tanggal ASC, no_surat_jalan ASC")->findAll();
    $totalItem = $db->count();

    // Get Pembayaran Piutang
    $getPembayaranPiutang = getPembayaranPiutang($models);

    $allLokasi = getAllData($db, 'acc_m_lokasi', 'id');
    foreach ($models as $key => $value) {
        $models[$key]->no                   = $params['offset'] + $key + 1;
        $models[$key]->grand_total          = $value->total;
        $models[$key]->tanggal2             = date("Y-m-d", $value->tanggal);
        $models[$key]->acc_m_lokasi_id      = isset($allLokasi[$value->acc_m_lokasi_id]) ? $allLokasi[$value->acc_m_lokasi_id] : [];
        $models[$key]->m_akun_id_piutang    = isset($value->m_akun_id) ? ['id' => $value->m_akun_id, 'kode' => $value->kodeAkun, 'nama' => $value->namaAkun] : [];

        $po = [];
        if (!empty($value->inv_po_penjualan_id)) {
            $po = $db->find("SELECT * FROM inv_po_penjualan WHERE id = {$value->inv_po_penjualan_id}");
            $models[$key]->inv_po_penjualan_id = !empty($po) ? $po : 0;
        } else {
            $models[$key]->inv_po_penjualan_id = !empty($po) ? $po : 0;
        }

        $models[$key]->inv_m_faktur_pajak_id = !empty($value->inv_m_faktur_pajak_id) ? ["id" => $value->inv_m_faktur_pajak_id, "nomor" => $value->nomor] : [];
        $models[$key]->terbayar              = !empty($getPembayaranPiutang[$value->id]) ? $getPembayaranPiutang[$value->id] : 0;

        $supp                           = $db->find("SELECT * FROM acc_m_kontak WHERE id = {$value->acc_m_kontak_id}");
        $supp->acc_m_akun_id            = !empty($supp->acc_m_akun_id) ? $db->find("SELECT * FROM acc_m_akun WHERE id = {$supp->acc_m_akun_id}") : [];
        $models[$key]->acc_m_kontak_id  = !empty($supp) ? $supp : [];
        $models[$key]->stok             = 0;
//        $models[$key]->is_ppn = intval($value->is_ppn);
//        $models[$key]->ppn = intval($value->ppn);
        $models[$key]->total            = intval($value->total);
        $models[$key]->checklist        = false;
    }

    if (!isset($params['bulan'])) {
        $totalDpp = $db->find("SELECT SUM(total) as total_dpp
          FROM inv_penjualan WHERE inv_proses_akhir_id IS NULL
          AND status != 'dibatalkan'");
    } else {
        $totalDpp = $db->find(
          "SELECT SUM(total) as total_dpp FROM inv_penjualan WHERE FROM_UNIXTIME(tanggal, '%Y-%m-%d') >= '" . $bulan_awal . "'" .
          "AND FROM_UNIXTIME(tanggal, '%Y-%m-%d') <= '" . $bulan_akhir . "'" .
          "AND status != 'dibatalkan'");
    }

    return successResponse($response, [
        'list' => $models,
        'totalDpp' => !empty($totalDpp->total_dpp) ? $totalDpp->total_dpp : 0,
        'totalItems' => $totalItem,
    ]);
});

// Fungsi get Pembayaran Piutang
function getPembayaranPiutang($params=[]) {
  $db = config('DB');
  $db = new Cahkampung\Landadb($db['db']);
  
  if( empty($params) ){
    return [];
  }

  $listID =[];
  foreach ($params as $key => $value) {
    $listID[] = $value->id;
  }

  $db->select("
    acc_bayar_piutang_det.inv_penjualan_id,
    sum(acc_bayar_piutang_det.bayar - acc_bayar_piutang_det.sisa_pelunasan) as bayar
  ")
  ->from("acc_bayar_piutang_det")
  ->leftJoin("inv_penjualan", "inv_penjualan.id = acc_bayar_piutang_det.inv_penjualan_id")
  ->where("acc_bayar_piutang_det.status", "=", "terposting")
  ->customWhere("acc_bayar_piutang_det.inv_penjualan_id IN(". implode(",", $listID) .")", "AND")
  ->groupBy("acc_bayar_piutang_det.inv_penjualan_id");
  $getPembayaran = $db->findAll();

  $listResult = [];
  if( !empty($getPembayaran) ){
    foreach ($getPembayaran as $key => $value) {
      $listResult[$value->inv_penjualan_id] = $value->bayar;
    }
  }

  return $listResult;
}
// Fungsi get Pembayaran Piutang - END

$app->get('/t_penjualan/getPenjualanPPN', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select("inv_penjualan.*, inv_m_faktur_pajak.nomor as no_faktur, acc_m_lokasi.nama as lokasi, acc_m_kontak.nama as kontak")
            ->from("inv_penjualan")
            ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
            ->join("JOIN", "acc_m_lokasi", "acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id")
            ->join("JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
            ->where("inv_penjualan.is_ppn", "=", 1)
            ->where("is_draft", "=", 0)
            ->customWhere("LENGTH(no_invoice) > 0", "AND")
            ->where("status", "!=", "dibatalkan");

    if (isset($params['tanggal']) && !empty($params['tanggal'])) {
        $tanggal_awal = strtotime(date("Y-m-d", strtotime($params['tanggal'])));
        $tanggal_akhir = strtotime(date("Y-m-t", strtotime($params['tanggal'])));
        $db->where("inv_penjualan.tanggal", ">=", $tanggal_awal)
                ->where("inv_penjualan.tanggal", "<=", $tanggal_akhir);
    }

    $models = $db->orderBy("inv_penjualan.no_invoice")->findAll();

    // List customer
    $db->select("
      acc_m_kontak.id,
      acc_m_kontak.nama
    ")
            ->from("inv_penjualan")
            ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
            ->join("JOIN", "acc_m_lokasi", "acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id")
            ->join("JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
            ->where("inv_penjualan.is_ppn", "=", 1)
            ->where("is_draft", "=", 0)
            ->customWhere("LENGTH(no_invoice) > 0", "AND")
            ->where("status", "!=", "dibatalkan");

    if (isset($params['tanggal']) && !empty($params['tanggal'])) {
        $tanggal_awal = strtotime(date("Y-m-d", strtotime($params['tanggal'])));
        $tanggal_akhir = strtotime(date("Y-m-t", strtotime($params['tanggal'])));
        $db->where("inv_penjualan.tanggal", ">=", $tanggal_awal)
                ->where("inv_penjualan.tanggal", "<=", $tanggal_akhir);
    }

    $db->orderBy("acc_m_kontak.nama")
            ->groupBy("acc_m_kontak.id");

    $listCustomer = $db->findAll();

    return successResponse($response, ['listInvoice' => $models, 'listCustomer' => $listCustomer]);
});

$app->get('/t_penjualan/getPenjualan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $models = $db->select("inv_penjualan.*, inv_m_faktur_pajak.nomor as no_faktur")
            ->from("inv_penjualan")
            ->join("JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
//            ->where("is_ppn", "=", 0)
            ->where("is_draft", "=", 0)
            ->where("status", "!=", "dibatalkan")
            ->findAll();

    return successResponse($response, $models);
});

$app->get('/t_penjualan/customer', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select("*")
            ->from("acc_m_kontak")
            ->where("nama", "LIKE", $params['nama'])
            ->where("type", "=", "customer");

    if (!empty($params['lokasi'])) {
        $db->andWhere("acc_m_kontak.acc_m_lokasi_id", "=", $params['lokasi']);
    }

    $db->limit(20);

    $models = $db->findAll();

    return successResponse($response, $models);
});

function generateKode($db) {
    $cekKode = $db->select('*')
            ->from("inv_penjualan")
            ->where("kode", "!=", "")
            ->orderBy('kode DESC')
            ->find();

    if ($cekKode) {
        $kode_terakhir = $cekKode->kode;
    } else {
        $kode_terakhir = 0;
    }

    $kodePenjualan = (substr($kode_terakhir, -5) + 1);
    $kode = substr('00000' . $kodePenjualan, strlen($kodePenjualan));
    $kode = 'J' . date("y") . "/" . $kode;

    return $kode;
}

function validasiPenjualan($data, $custom = array()) {
    $validasi = array(
        'acc_m_lokasi_id' => 'required',
        'acc_m_kontak_id' => 'required',
        'tanggal' => 'required',
//        'jatuh_tempo' => 'required',
            // 'no_invoice'           => 'required',
            // 'm_akun_id'             => 'required',
    );

    GUMP::set_field_name("acc_m_kontak_id", "Customer");
    GUMP::set_field_name("acc_m_lokasi_id", "Lokasi");
//    GUMP::set_field_name("jatuh_tempo", "Tanggal Jatuh Tempo");
    GUMP::set_field_name("m_akun_id", "Akun Keluar");

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->post('/t_penjualan/save', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

//    echo json_encode($data);die();

    $keterangan_trans = "";

    if (empty($data['detail'])) {
        $data['form']['tipe'] = 'Piutang';
    } else {
        $data['form']['tipe'] = 'Penjualan';
    }

    $tanggal = date('Y-m-d', strtotime($data['form']['tanggal']));

    //simpan ke session
    $lokasi = $data['form']['acc_m_lokasi_id'];

    $data['form']['acc_m_lokasi_id']    = !empty($data['form']['acc_m_lokasi_id']['id']) ? $data['form']['acc_m_lokasi_id']['id'] : NULL;
    $data['form']['tanggal']            = strtotime($tanggal);
    $data['form']['m_akun_biaya_id']    = isset($data['form']['m_akun_biaya_id']) ? $data['form']['m_akun_biaya_id']['id'] : null;
    $data['form']['m_akun_id']          = isset($data['form']['m_akun_id']) ? $data['form']['m_akun_id']['id'] : null;
    $data['form']['ongkos_kirim']       = isset($data['form']['ongkos_kirim']) ? $data['form']['ongkos_kirim'] : 0;
    $data['form']['alamat_pengiriman']  = isset($data['form']['alamat_pengiriman']) ? $data['form']['alamat_pengiriman'] : null;
    $data['form']['kode']               = generatePenjualan('kode', $tanggal);
    $data['form']['is_ppn']             = $data['form']['is_ppn'];
    $data['form']['piutang'] =  $data['form']['piutang'] + round($data['form']['grand_total'] / 10);


    $data['form']['no_surat_jalan'] = generatePenjualan('surat_jalan', $tanggal, $data['form']['acc_m_lokasi_id']);
    $data['form']['no_faktur']      = isset($data['form']['inv_m_faktur_pajak_id']['nomor']) ? $data['form']['inv_m_faktur_pajak_id']['nomor'] : null;




    if (isset($data['form']['sisipan']) && $data['form']['sisipan'] == 1) {
        $data['form']['inv_m_faktur_pajak_id'] = isset($data['form']['inv_m_faktur_pajak_id']['id']) ? $data['form']['inv_m_faktur_pajak_id']['id'] : generatePenjualan('faktur_pajak', $tanggal);
        $data['form']['no_invoice'] = isset($data['form']['no_invoice']) ? $data['form']['no_invoice'] : generatePenjualan('invoice', $tanggal);
    } else {
        $data['form']['inv_m_faktur_pajak_id'] = isset($data['form']['inv_m_faktur_pajak_id']['id']) ? $data['form']['inv_m_faktur_pajak_id']['id'] : null;
        $data['form']['no_invoice'] = isset($data['form']['no_invoice']) ? $data['form']['no_invoice'] : null;
    }



    if ($data['form']['ongkos_kirim'] > 0) {
        $validasi = validasiPenjualan($data['form'], ['m_akun_biaya_id' => 'required']);
    } else if ($data['form']['acc_m_kontak_id']['npwp'] != '') {
        $validasi = validasiPenjualan($data['form']);
    } else {
        $validasi = validasiPenjualan($data['form']);
    }

    if ($validasi !== true) {
        return unprocessResponse($response, $validasi);
    }

    $data['form']['acc_m_kontak_id'] = (isset($data['form']['acc_m_kontak_id']['id'])) ? $data['form']['acc_m_kontak_id']['id'] : null;

    if ($data['form']['tipe'] == "Piutang") {
        $data['form']['m_akun_id'] = $data['form']['m_akun_id_piutang']['id'];
    }

    if ($data['form']['type'] == 'draft') {
        $data['form']['status']     = 'draft';
        $data['form']['is_draft']   = 1;
    }else {
        $data['form']['status']     = 'belum lunas';
        $data['form']['is_draft']   = 0;
    }

    $data['form']['total']  = !empty($data['form']['grand_total']) ? $data['form']['grand_total'] : 0;
    $data['form']['ppn']    = !empty($data['form']['ppn']) ? $data['form']['ppn'] : 0;

    if (!empty($data['form']['inv_po_penjualan_id'])) {
        $data['form']['inv_po_penjualan_id'] = $data['form']['inv_po_penjualan_id']['id'];
        unset($data['form']['id']);
    }

    try {
        if (!isset($data['form']['sisipan']) || (isset($data['form']['sisipan']) && $data['form']['sisipan'] == 0 ))
            if(!isset($data['form']['is_saldo_awal_piutang']))
            unset($data['form']['no_invoice']);
        $db->startTransaction();

        if (isset($data['form']["id"])) {
            unset($data['form']['no_surat_jalan']);
            unset($data['form']['kode']);
            unset($data['form']['no_kuitansi']);

            $model = $db->update("inv_penjualan", $data['form'], array('id' => $data['form']['id']));
        } else {
            $model = $db->insert("inv_penjualan", $data['form']);
            $_SESSION['user']['temp']['depo'] = $lokasi;
            $_SESSION['user']['temp']['tanggal'] = $tanggal;
        }

        $lokasi = config('LOKASI_STOK') != 0 ? config('LOKASI_STOK') : $model->acc_m_lokasi_id;

        if (!empty($data['detail'])) {
            $deDetail   = $db->delete('inv_penjualan_det', ['inv_penjualan_id' => $model->id]);
            $delStok    = $db->delete('inv_kartu_stok', ['trans_id' => $model->id, "trans_tipe"=> 'inv_penjualan_id']);

            foreach ($data['detail'] as $val) {

                if (!empty($val['inv_m_barang_id']['harga_pokok'])) {
                    $_harga_pokok = $val['inv_m_barang_id']['harga_pokok'];
                }

                $val['inv_penjualan_id']  = $model->id;
                $val['harga_pokok']       = !empty($_harga_pokok) ? $_harga_pokok : 'average';
                //tambahan
                $barang                   = $val['inv_m_barang_id'];
                $barang_lama              = isset($val['inv_m_barang_id_lama']) ? $val['inv_m_barang_id_lama'] : [];

                $val['jumlah_awal']       = isset($val['jumlah']) ? $val['jumlah'] : null;
                $val['jumlah']            = isset($val['koreksi']) ? $val['koreksi'] : $val['jumlah'];
                $val['harga']             = isset($val['koreksi_harga']) ? $val['koreksi_harga'] : $val['harga'];

                $val['inv_m_barang_id']   = !empty($val['inv_m_barang_id']['id']) ? $val['inv_m_barang_id']['id'] : NULL;

                $detail = $db->insert("inv_penjualan_det", $val);

                // Simpan ke kartu stok
                if($model->status != 'draft'){
                  $dataa = array(
                      "kode"              => $model->kode,
                      "inv_m_barang_id"   => $barang['id'],
                      "catatan"           => 'Penjualan',
                      "jumlah_keluar"     => $val['jumlah'],
                      "harga_keluar"      => $val['harga'] - $val['diskon'],
                      "trans_tipe"        => 'inv_penjualan_id',
                      "trans_id"          => $model->id,
                      "hpp"               => $val['harga'] - $val['diskon'],
                      "jenis_kas"         => 'keluar',
                      "acc_m_lokasi_id"   => $lokasi,
                      "tanggal"           => $model->tanggal,
                  );
                  $insertKartuStok = $db->insert("inv_kartu_stok", $dataa);
                }
                // Simpan ke kartu stok - END
            }
        }

        if($model->status != 'draft'){
          $paramsJurnal = [
            'reff_type'   => 'inv_penjualan',
            'reff_id'     => $model->id,
          ];

          simpanJurnal($paramsJurnal);
        }

        $db->endTransaction();
        return successResponse($response, $model);
    } catch (Exception $exc) {
        echo $exc;
    }
});

$app->get('/t_penjualan/getPengajuan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $pengajuan = $db->select("
      inv_po_penjualan.*,
      inv_po_penjualan.id as inv_po_penjualan_id
    ")
            ->from("inv_po_penjualan")
            ->where("inv_po_penjualan.id", "=", $params['id'])
            ->find();

    if (isset($pengajuan->acc_m_kontak_id)) {
        $acc_m_kontak_id = $db->find("SELECT * FROM acc_m_kontak WHERE id = $pengajuan->acc_m_kontak_id");
    }
    $pengajuan->acc_m_kontak_id = !empty($acc_m_kontak_id) ? $acc_m_kontak_id : [];

    if (isset($pengajuan->acc_m_lokasi_id)) {
        $acc_m_lokasi_id = $db->find("SELECT * FROM acc_m_lokasi WHERE id = $pengajuan->acc_m_lokasi_id");
    }
    $pengajuan->acc_m_lokasi_id = !empty($acc_m_lokasi_id) ? $acc_m_lokasi_id : [];

    $pengajuan->inv_po_penjualan_id = $params;

    $detail = $db->select("
      inv_po_penjualan_det.*,
      inv_m_barang.id as inv_m_barang_id,
      inv_m_barang.nama,
      inv_m_barang.kode,
      inv_m_satuan.nama as nama_satuan
      ")
            ->from("inv_po_penjualan_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_po_penjualan_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->where("inv_po_penjualan_det.inv_po_penjualan_id", "=", $params['id'])
            ->findAll();

    $result = [];
    foreach ($detail as $key => $value) {
        $result[$key] = (array) $value;
        $stok = getStok($db, $value->inv_m_barang_id, $pengajuan->acc_m_lokasi_id->id);

        $result[$key]['inv_m_barang_id'] = [
            'id' => $value->inv_m_barang_id,
            'kode' => $value->kode,
            'nama' => $value->nama,
            'nama_satuan' => $value->nama_satuan,
            'stok' => $stok,
        ];

        $result[$key]['subtotal'] = $value->jumlah * $value->harga;
        $result[$key]['stok'] = $stok;
        $result[$key]['diskon'] = 0;
        $result[$key]['diskon_persen'] = 0;
    }

    return successResponse($response, ['form' => $pengajuan, 'detail' => $result]);
});
$app->get('/t_penjualan/getDetail', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $detail = $db->select("
      inv_penjualan_det.*,
      inv_m_barang.id as inv_m_barang_id,
      inv_m_barang.nama,
      inv_m_barang.kode,
      inv_m_barang.type_barcode,
      inv_m_barang.harga_pokok,
      inv_m_barang.harga_beli,
      inv_m_satuan.nama as nama_satuan,
      inv_m_kategori.akun_pembelian_id,
      inv_m_kategori.akun_persediaan_brg_id,
      inv_m_kategori.akun_penjualan_id,
      inv_m_kategori.akun_hpp_id,
      akun_pembelian.kode as kodePembelian,
      akun_pembelian.nama as namaPembelian,
      akun_persediaan_brg.kode as kodePersediaan,
      akun_persediaan_brg.nama as namaPersediaan,
      akun_penjualan.kode as kodePenjualan,
      akun_penjualan.nama as namaPenjualan,
      akun_hpp.kode as kodeHpp,
      akun_hpp.nama as namaHpp
      ")
            ->from("inv_penjualan_det")
            ->join('LEFT JOIN', "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
            ->join('LEFT JOIN', "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->join('LEFT JOIN', "inv_m_kategori", "inv_m_kategori.id = inv_m_barang.inv_m_kategori_id")
            ->join("left join", "acc_m_akun akun_pembelian", "akun_pembelian.id= inv_m_kategori.akun_pembelian_id")
            ->join("left join", "acc_m_akun akun_penjualan", "akun_penjualan.id = inv_m_kategori.akun_penjualan_id")
            ->join("left join", "acc_m_akun akun_hpp", "akun_hpp.id= inv_m_kategori.akun_hpp_id")
            ->join("left join", "acc_m_akun akun_persediaan_brg", "akun_persediaan_brg.id= inv_m_kategori.akun_persediaan_brg_id")
            ->where("inv_penjualan_det.inv_penjualan_id", "=", $params['id'])
            ->orderBy('inv_penjualan_det.id')
            ->findAll();

    $result = [];
    foreach ($detail as $key => $value) {
        $value->koreksi = $value->jumlah;
        $value->koreksi_harga = $value->harga;

        $value->akun_pembelian_id = !empty($value->akun_pembelian_id) ? ["id" => $value->akun_pembelian_id, "nama" => $value->namaPembelian, "kode" => $value->kodePembelian] : [];
        $value->akun_penjualan_id = !empty($value->akun_penjualan_id) ? ["id" => $value->akun_penjualan_id, "nama" => $value->namaPenjualan, "kode" => $value->kodePenjualan] : [];
        $value->akun_hpp_id = !empty($value->akun_hpp_id) ? ["id" => $value->akun_hpp_id, "nama" => $value->namaHpp, "kode" => $value->kodeHpp] : [];
        $value->akun_persediaan_brg_id = !empty($value->akun_persediaan_brg_id) ? ["id" => $value->akun_persediaan_brg_id, "nama" => $value->namaPersediaan, "kode" => $value->kodePersediaan] : [];

        $result[$key] = (array) $value;
        $stok = getStok($db, $value->inv_m_barang_id, config('LOKASI_STOK'));

        $result[$key]['inv_m_barang_id'] = [
            'id' => $value->inv_m_barang_id,
            'kode' => $value->kode,
            'nama' => $value->nama,
            'nama_satuan' => $value->nama_satuan,
            'stok' => $stok,
            'type_barcode' => $value->type_barcode,
            'harga_pokok' => $value->harga_pokok,
            'harga_beli' => $value->harga_beli,
            'akun_pembelian_id' => !empty($value->akun_pembelian_id) ? ["id" => $value->akun_pembelian_id, "nama" => $value->namaPembelian, "kode" => $value->kodePembelian] : [],
            'akun_penjualan_id' => !empty($value->akun_penjualan_id) ? ["id" => $value->akun_penjualan_id, "nama" => $value->namaPenjualan, "kode" => $value->kodePenjualan] : [],
            'akun_hpp_id' => !empty($value->akun_hpp_id) ? ["id" => $value->akun_hpp_id, "nama" => $value->namaHpp, "kode" => $value->kodeHpp] : [],
            'akun_persediaan_brg_id' => !empty($value->akun_persediaan_brg_id) ? ["id" => $value->akun_persediaan_brg_id, "nama" => $value->namaPersediaan, "kode" => $value->kodePersediaan] : [],
        ];

        $result[$key]['inv_m_barang_id_lama'] = [
            'id' => $value->inv_m_barang_id,
            'kode' => $value->kode,
            'nama' => $value->nama,
            'nama_satuan' => $value->nama_satuan,
            'stok' => $stok,
            'type_barcode' => $value->type_barcode,
            'harga_pokok' => $value->harga_pokok,
            'harga_beli' => $value->harga_beli,
            'akun_pembelian_id' => !empty($value->akun_pembelian_id) ? ["id" => $value->akun_pembelian_id, "nama" => $value->namaPembelian, "kode" => $value->kodePembelian] : [],
            'akun_penjualan_id' => !empty($value->akun_penjualan_id) ? ["id" => $value->akun_penjualan_id, "nama" => $value->namaPenjualan, "kode" => $value->kodePenjualan] : [],
            'akun_hpp_id' => !empty($value->akun_hpp_id) ? ["id" => $value->akun_hpp_id, "nama" => $value->namaHpp, "kode" => $value->kodeHpp] : [],
            'akun_persediaan_brg_id' => !empty($value->akun_persediaan_brg_id) ? ["id" => $value->akun_persediaan_brg_id, "nama" => $value->namaPersediaan, "kode" => $value->kodePersediaan] : [],
        ];

        if ($value->diskon == 0) {
            $result[$key]['diskon_persen'] = 0;
        } else {
            $result[$key]['diskon_persen'] = round(($value->diskon / $value->harga) * 100, 1);
        }

        $subharga = ($value->jumlah * $value->harga) - ($value->jumlah * $value->diskon);
        $result[$key]['subtotal'] = $subharga;
        $result[$key]['stok'] = $stok;
    }

    return successResponse($response, ['detail' => $result]);
});

$app->get('/t_penjualan/getJurnal', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $trans_detail = $db->select("
      acc_trans_detail.*,
      acc_m_kontak.nama as namaKontak,
      acc_m_kontak.kode as kodeKontak,
      acc_m_lokasi.nama as namaLokasi,
      acc_m_lokasi.kode as kodeLokasi,
      acc_m_akun.nama as namaAkun,
      acc_m_akun.kode as kodeAkun
      ")
            ->from("acc_trans_detail")
            ->join('LEFT JOIN', "acc_m_kontak", "acc_m_kontak.id = acc_trans_detail.m_kontak_id")
            ->join('LEFT JOIN', "acc_m_lokasi", "acc_m_lokasi.id = acc_trans_detail.m_lokasi_id")
            ->join('LEFT JOIN', "acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
            ->where("acc_trans_detail.reff_id", "=", $params['id'])
            ->where("acc_trans_detail.reff_type", "=", 'inv_penjualan')
            ->findAll();

    $result = [];
    foreach ($trans_detail as $key => $value) {

        $value->akun = ['id' => $value->m_akun_id, 'nama' => $value->namaAkun, 'kode' => $value->kodeAkun];

        $result[$key] = (array) $value;
    }

    return successResponse($response, ['list' => $result]);
});

function generateNoSuratJalan($db) {
    $cekNoSuratJalan = $db->select('*')
            ->from("inv_penjualan")
            ->where("no_surat_jalan", "!=", "")
            ->orderBy('no_surat_jalan DESC')
            ->find();

    if ($cekNoSuratJalan) {
        $no_terakhir = $cekNoSuratJalan->no_surat_jalan;
    } else {
        $no_terakhir = 0;
    }

    $noSuratJalan = (substr($no_terakhir, -6) + 1);
    $no = substr('000000' . $noSuratJalan, strlen($noSuratJalan));
    $no = "K" . $no;

    return $no;
}

$app->post('/t_penjualan/approved', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    if (isset($params['approved']) && !empty($params['approved'])) {
        $models = $db->update("inv_penjualan", ['status' => $params['approved']], array("id" => $params['id']));
    };

    if (isset($params['approved']) && $params['approved'] == 'approved1') {
        date_default_timezone_set("Asia/Jakarta");
        $date = date('Y/m/d');
        $data['form']['no_surat_jalan'] = generateNoSuratJalan($db);
        $data['form']['approved1_by'] = $_SESSION['user']['id'];
        $data['form']['approved1_at'] = strtotime($date);
        $db->update("inv_penjualan", ['no_surat_jalan' => $data['form']['no_surat_jalan'],
            'approved1_by' => $data['form']['approved1_by'],
            'approved1_at' => $data['form']['approved1_at']], array("id" => $params['id']));
    }

    if (isset($params['approved']) && $params['approved'] == 'approved2') {
        date_default_timezone_set("Asia/Jakarta");
        $date = date('Y/m/d');
        $data['form']['approved2_by'] = $_SESSION['user']['id'];
        $data['form']['approved2_at'] = strtotime($date);
        $db->update("inv_penjualan", ['approved2_by' => $data['form']['approved2_by'],
            'approved2_at' => $data['form']['approved2_at']], array("id" => $params['id']));
    }

    if (isset($params['approved']) && $params['approved'] == 'approved1') {
        $model = $db->select("*")
                ->from("inv_penjualan")
                ->where("id", "=", $params['id'])
                ->find();

        $ambil = $db->select("inv_penjualan_det.*, inv_m_barang.type_barcode, inv_m_barang.harga_pokok")
                ->from("inv_penjualan_det")
                ->join("JOIN", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
                ->where("inv_penjualan_id", "=", $params['id'])
                ->findAll();

        foreach ($ambil as $val) {
            if ($val->type_barcode == 'serial') {
                $dataa = array(
                    "kode" => $model->kode,
                    "inv_m_barang_id" => $val->inv_m_barang_id,
                    "catatan" => 'Penjualan',
                    "jumlah_keluar" => $val->jumlah,
                    "harga_keluar" => $val->harga - $val->diskon,
                    "trans_tipe" => 'inv_penjualan_id',
                    "trans_id" => $model->id,
                    "hpp" => $val->harga,
                    "jenis_kas" => 'keluar',
                    "acc_m_lokasi_id" => $model->acc_m_lokasi_id,
                    "tanggal" => $model->tanggal,
                );
                $insertKartuStok = $db->insert("inv_kartu_stok", $dataa);
            } else if ($val->type_barcode == 'non serial') {
                $_hpp = pos_hpp($val->inv_m_barang_id, $model->acc_m_lokasi_id, null, $val->harga_pokok, $val->jumlah, false);
                foreach ($_hpp as $key) {
                    $jumlah = ($val->harga_pokok == 'average') ? $val->jumlah : $key['jumlah'];
                    $dataa = array(
                        "kode" => $model->kode,
                        "inv_m_barang_id" => $val->inv_m_barang_id,
                        "catatan" => 'Penjualan',
                        "jumlah_keluar" => $jumlah,
                        "harga_keluar" => $val->harga - $val->diskon,
                        "trans_tipe" => 'inv_penjualan_id',
                        "trans_id" => $model->id,
                        "hpp" => $key['hpp'],
                        "jenis_kas" => 'keluar',
                        "acc_m_lokasi_id" => $model->acc_m_lokasi_id,
                        "tanggal" => $model->tanggal,
                    );
                    $insertKartuStok = $db->insert("inv_kartu_stok", $dataa);
                }

                /* PENGURANGAN STOCK */
                pos_pengurangan_stok($val->jumlah, $val->inv_m_barang_id, $model->acc_m_lokasi_id, $model->tanggal);
            }
        }
    }

    if ($models) {
        return successResponse($response, $models);
    } else {
        return unprocessResponse($response, ['Gagal mengapprove data']);
    }
});

$app->post('/t_penjualan/approveAll', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

//    print_r($params);die;

    try {
        $arr = [];
        foreach ($params['form'] as $key => $val) {
            $arr[$val['id']] = $val;
        }

        $update = $db->run("UPDATE inv_penjualan SET status = 'lunas' WHERE id IN (" . implode(", ", $params['id']) . ")");

        $detail = $db->select("inv_penjualan_det.*, inv_m_barang.type_barcode, inv_m_barang.harga_pokok")
                        ->from("inv_penjualan_det")
                        ->join("join", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
                        ->customWhere("inv_penjualan_det.inv_penjualan_id in (" . implode(", ", $params['id']) . ")")->findAll();

//        print_r($detail);die;

        foreach ($detail as $key => $val) {
            if ($val->type_barcode == 'serial') {
                echo "serial";
                die;
                $dataa = array(
                    "kode" => $model->kode,
                    "inv_m_barang_id" => $barang['id'],
                    "catatan" => 'Penjualan',
                    "jumlah_keluar" => $val['jumlah'],
                    "harga_keluar" => $val->harga - $val->diskon,
                    "trans_tipe" => 'inv_penjualan_id',
                    "trans_id" => $model->id,
                    "hpp" => $val->harga,
                    "jenis_kas" => 'keluar',
                    "acc_m_lokasi_id" => $model->acc_m_lokasi_id,
                    "tanggal" => $model->tanggal,
                );
                $insertKartuStok = $db->insert("inv_kartu_stok", $dataa);
            } else if ($val->type_barcode == 'non serial') {
//                echo "non serial";die;
                $_hpp = pos_hpp($val->inv_m_barang_id, config('LOKASI_STOK'), null, $val->harga_pokok, $val->jumlah, false);

//                print_r($_hpp);die;

                foreach ($_hpp as $key) {
                    $jumlah = ($val->harga_pokok == 'average') ? $val->jumlah : $key['jumlah'];
                    $dataa = array(
                        "kode" => $arr[$val->inv_penjualan_id]['kode'],
                        "inv_m_barang_id" => $val->inv_m_barang_id,
                        "catatan" => 'Penjualan',
                        "jumlah_keluar" => $jumlah,
                        "harga_keluar" => $val->harga - $val->diskon,
                        "trans_tipe" => 'inv_penjualan_id',
                        "trans_id" => $val->inv_penjualan_id,
                        "hpp" => $key['hpp'],
                        "jenis_kas" => 'keluar',
                        "acc_m_lokasi_id" => config('LOKASI_STOK'),
                        "tanggal" => $arr[$val->inv_penjualan_id]['tanggal'],
                    );
                    $insertKartuStok = $db->insert("inv_kartu_stok", $dataa);
                }

                /* PENGURANGAN STOCK */
                pos_pengurangan_stok($val->jumlah, $val->inv_m_barang_id, config('LOKASI_STOK'), $arr[$val->inv_penjualan_id]['tanggal']);
            }
        }

        return successResponse($response, []);
    } catch (Exception $exc) {
        echo $exc;
    }
});

$app->get('/t_penjualan/suratJalan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $db->select("inv_penjualan.id,
    inv_penjualan.no_surat_jalan,
    inv_penjualan.kode,
    inv_penjualan.tanggal,
    acc_m_kontak.nama as nama_customer,
    acc_m_kontak.alamat,
    acc_m_lokasi.nama as nama_lokasi")
            ->from("inv_penjualan")
            ->join("LEFT JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
            ->join("LEFT JOIN", "acc_m_lokasi", "acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id")
            ->where("inv_penjualan.id", "=", $params['id']);

    $models = $db->find();

    $barang = $db->select("SUM(jumlah) as total,
            SUM(inv_penjualan_det.jumlah * inv_penjualan_det.harga) as harga_total,
            inv_m_barang.nama,
            inv_m_satuan.nama as nama_satuan")
            ->from("inv_penjualan_det")
            ->join("left join", "inv_penjualan", "inv_penjualan.id = inv_penjualan_det.inv_penjualan_id")
            ->join("left join", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
            ->join("left join", "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->groupBy("inv_m_barang_id")
            ->where("inv_penjualan_det.inv_penjualan_id", "=", $params['id'])
            ->findAll();

    $data = [
        'id' => $models->id,
        'no_surat_jalan' => $models->no_surat_jalan,
        'tanggal' => $models->tanggal,
        'nama_customer' => $models->nama_customer,
        'alamat' => $models->alamat,
        'kode' => $models->kode,
        'nama_lokasi' => $models->nama_lokasi,
        'barang' => $barang
    ];

//    echo json_encode($data); die();

    if (isset($params['print']) && $params['print'] == 1) {
        $view = twigView();
        $content = $view->fetch("surat/surat_jalan.html", [
            'data' => $data,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    }
});

$app->get('/t_penjualan/fakturPenjualan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    if( !empty($params['tanggal']) ){
      $starDate = date("Y-m-01", strtotime($params['tanggal']));
      $endDate  = date("Y-m-t", strtotime($params['tanggal']));
    }

    $params['arr1'] = [];
    if (isset($params['mulai']) && !empty($params['mulai'])) {
        $db->select("id")
        ->from("inv_penjualan")
        ->where("no_invoice", ">=", $params['mulai'])
        ->andWhere("no_invoice", "<=", $params['selesai']);

        if(!empty(!empty($params['tanggal']))){
          $db->andWhere("inv_penjualan.tanggal", ">=", strtotime($starDate));
          $db->andWhere("inv_penjualan.tanggal", "<=", strtotime($endDate));
        }
        $penjualan1 = $db->findAll();

        $arr1 = [];
        foreach ($penjualan1 as $key => $value) {
            array_push($arr1, $value->id);
        }
        $params['arr1'] = $arr1;

    }

    $params['listPenjualan'] = isset($params['listPenjualan']) && !empty($params['listPenjualan']) ? $params['listPenjualan'] : [];

    $arrfinal = array_unique(array_merge($params['arr1'], $params['listPenjualan']));

    $db->select("inv_penjualan.id,
    inv_penjualan.no_surat_jalan,
    inv_penjualan.no_invoice,
    inv_penjualan.kode,
    inv_penjualan.tanggal,
    acc_m_kontak.nama as nama_customer,
    acc_m_kontak.alamat,
    acc_m_lokasi.nama as nama_lokasi,
    acc_m_kontak.is_ppn,
    inv_m_faktur_pajak.nomor as no_faktur")
            ->from("inv_penjualan")
            ->join("LEFT JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
            ->join("LEFT JOIN", "acc_m_lokasi", "acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id")
            ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
            ->customWhere("inv_penjualan.id IN(" . implode(", ", $arrfinal) . ")", "AND")
            ->orderBy("inv_penjualan.no_invoice");

    $models = $db->findAll();

//    pd($models);

    $barang = $db->select("inv_penjualan_id, SUM(jumlah) as total,
            SUM(inv_penjualan_det.jumlah * inv_penjualan_det.harga) as harga_total,
            inv_m_barang.nama,
            inv_m_satuan.nama as nama_satuan,
            inv_penjualan_det.harga as harga_jual,
            inv_penjualan_det.diskon")
            ->from("inv_penjualan_det")
            ->join("left join", "inv_penjualan", "inv_penjualan.id = inv_penjualan_det.inv_penjualan_id")
            ->join("left join", "inv_m_barang", "inv_m_barang.id = inv_penjualan_det.inv_m_barang_id")
            ->join("left join", "inv_m_satuan", "inv_m_satuan.id = inv_m_barang.inv_m_satuan_id")
            ->groupBy("inv_m_barang_id, inv_penjualan_det.harga, inv_penjualan_id")
            ->customWhere("inv_penjualan_det.inv_penjualan_id IN(" . implode(", ", $arrfinal) . ")", "AND")
            ->orderBy("inv_penjualan_det.id")
            ->findAll();

//    pd($barang);

    $data = [];
    foreach ($models as $key => $value) {
        $total = 0;
        $diskon = 0;
        $arr_barang = [];
        foreach ($barang as $keys => $values) {
            if ($values->inv_penjualan_id == $value->id) {
                $total += $values->harga_total;
                $diskon += $values->diskon;
                $arr_barang[] = $values;
            }
        }

        $total_setelah_diskon = $total - $diskon;
        $ppn = 0;
        if ($value->is_ppn == 1) {
            $ppn = (10 / 100 * $total);
        }
        $grand_total = $total_setelah_diskon + $ppn;

        $terbilang = strtoupper(terbilang($grand_total) . " rupiah");

        $data[$key] = [
            'id' => $value->id,
            'no_faktur' => $value->no_faktur,
            'no_surat_jalan' => $value->no_surat_jalan,
            'no_invoice' => $value->no_invoice,
            'tanggal' => $value->tanggal,
            'nama_customer' => $value->nama_customer,
            'alamat' => $value->alamat,
            'kode' => $value->kode,
            'nama_lokasi' => $value->nama_lokasi,
            'total' => $total,
            'total_setelah_diskon' => $total_setelah_diskon,
            'ppn' => $ppn,
            'grand_total' => $grand_total,
            'terbilang' => $terbilang,
            'diskon' => $diskon,
            'barang' => $arr_barang
        ];
    }


//    pd($data);

    $fix = [];
    $page = 1;
    $index = 1;
    foreach ($data as $key => $value) {
        $fix[$page][$index] = (array) $value;

        if ($index == 2) {
            $page++;
            $index = 1;
        } else {
            $index++;
        }
    }

//    pd($fix);
//    echo json_encode($data);
//    die();

    if (isset($params['print']) && $params['print'] == 1) {
        $view = twigView();
        $content = $view->fetch("surat/faktur_penjualan.html", [
            'data' => $fix,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    }
});

$app->get('/t_penjualan/kwitansiPenjualan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $params['listPenjualan'] = isset($params['listPenjualan']) && !empty($params['listPenjualan']) ? $params['listPenjualan'] : [];

    if( !empty($params['tanggal']) ){
      $startDate = date("Y-m-01", strtotime($params['tanggal']));
      $endDate  = date("Y-m-t", strtotime($params['tanggal']));
    }

    $arr1 = [];
    if (isset($params['mulai']) && !empty($params['mulai'])) {
      // Jika pilih filter Mulai - Sampai
        $db->select("id")
        ->from("inv_penjualan")
        ->where("no_invoice", ">=", $params['mulai'])
        ->where("no_invoice", "<=", $params['selesai']);

      if( !empty($params['tanggal']) ){
        $db->andWhere("inv_penjualan.tanggal", ">=", strtotime($startDate));
        $db->andWhere("inv_penjualan.tanggal", "<=", strtotime($endDate));
      }

        if (!empty($params['kecuali'])) {
            $listID = [];
            foreach ($params['kecuali'] as $key => $value) {
                $listID[] = $value;
            }

            if (!empty($listID)) {
                $listID = implode(",", $listID);
                $db->customWhere("inv_penjualan.acc_m_kontak_id NOT IN(" . $listID . ")", "AND");
            }
        }

        $penjualan1 = $db->findAll();

        $arr1 = [];
        foreach ($penjualan1 as $key => $value) {
            array_push($arr1, $value->id);
        }
        // Jika pilih filter Mulai - Sampai
    } else {
      // Jika gk pilih filter
      $db->select("id")
      ->from("inv_penjualan")
      ->where("inv_penjualan.is_ppn", "=", 1)
      ->andWhere("is_draft", "=", 0)
      ->customWhere("LENGTH(no_invoice) > 0", "AND")
      ->andWhere("status", "!=", "dibatalkan");

      if( !empty($params['tanggal']) ){
        $db->andWhere("inv_penjualan.tanggal", ">=", strtotime($startDate));
        $db->andWhere("inv_penjualan.tanggal", "<=", strtotime($endDate));
      }

      if (!empty($params['kecuali'])) {
          $listID = [];
          foreach ($params['kecuali'] as $key => $value) {
              $listID[] = $value;
          }

          if (!empty($listID)) {
              $listID = implode(",", $listID);
              $db->customWhere("inv_penjualan.acc_m_kontak_id NOT IN(" . $listID . ")", "AND");
          }
      }

      $penjualan1 = $db->findAll();

      $arr1 = [];
      foreach ($penjualan1 as $key => $value) {
          array_push($arr1, $value->id);
      }
      // Jika gk pilih filter - END
    }

    $params['arr1']           = $arr1;
    $params['listPenjualan']  = isset($params['listPenjualan']) && !empty($params['listPenjualan']) ? $params['listPenjualan'] : [];
    $arrfinal                 = array_unique(array_merge($params['arr1'], $params['listPenjualan']));

    $db->select("
      inv_penjualan.id,
      inv_penjualan.no_surat_jalan,
      inv_penjualan.no_kuitansi,
      inv_penjualan.total,
      inv_penjualan.cash,
      inv_penjualan.piutang,
      inv_penjualan.kembalian,
      inv_penjualan.tanggal,
      inv_m_faktur_pajak.nomor as no_faktur,
      acc_m_kontak.nama as nama_customer,
      acc_m_lokasi.nama as nama_lokasi,
      acc_m_kontak.is_ppn")
    ->from("inv_penjualan")
    ->join("LEFT JOIN", "inv_m_faktur_pajak", "inv_m_faktur_pajak.id = inv_penjualan.inv_m_faktur_pajak_id")
    ->join("LEFT JOIN", "acc_m_kontak", "acc_m_kontak.id = inv_penjualan.acc_m_kontak_id")
    ->join("LEFT JOIN", "acc_m_lokasi", "acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id");

    if( !empty($params['listPenjualan']) ){
      $db->customWhere("inv_penjualan.id IN (" . implode(", ", $params['listPenjualan']) . ")");

    } else if( !empty($arrfinal) ){
      $db->customWhere("inv_penjualan.id IN (" . implode(", ", $arrfinal) . ")");
    }

    if( !empty($params['tanggal']) ){
      $db->andWhere("inv_penjualan.tanggal", ">=", strtotime($startDate));
      $db->andWhere("inv_penjualan.tanggal", "<=", strtotime($endDate));
    }

    if (!empty($params['kecuali'])) {
        $listID = [];
        foreach ($params['kecuali'] as $value) {
            $listID[] = $value;
        }

        if (!empty($listID)) {
            $listID = implode(",", $listID);
            $db->customWhere("inv_penjualan.acc_m_kontak_id NOT IN(" . $listID . ")", "AND");
        }
    }

    $db->orderBy("inv_penjualan.no_invoice");
    $models = $db->findAll();

    foreach ($models as $key => $value) {
        $value->ppn = 0;
        if ($value->is_ppn == 1) {
            $value->ppn = (10 / 100 * $value->total);
        } else {
            $value->no_kuitansi = '';
        }
        $value->grandtotal = $value->total + $value->ppn;
        $value->terbilang = ucwords(terbilang($value->grandtotal) . " rupiah");
        $value->tanggal = date("d/m/Y", $value->tanggal);
    }

    $data = $models;

    $fix = [];
    $page = 1;
    $index = 1;
    foreach ($data as $key => $value) {
        $fix[$page][$index] = (array) $value;

        if ($index == 3) {
            $page++;
            $index = 1;
        } else {
            $index++;
        }
    }

    if (isset($params['print']) && $params['print'] == 1) {
        $view = twigView();
        $content = $view->fetch("surat/kwitansi_penjualan.html", [
            'data' => $fix,
            'css' => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    }
});

$app->post('/t_penjualan/delete', function ($request, $response) {

    $params = $request->getParams();
    $db = $this->db;

    try {
        $db->delete('inv_penjualan', ['id' => $params['id']]);
        $db->delete('inv_penjualan_det', ['inv_penjualan_id' => $params['id']]);

        // Hapus Jurnal Umum dan Trans Detail
        $paramsJurnal = [
          'reff_type'   => 'inv_penjualan',
          'reff_id'     => $params['id'],
        ];
        hapusJurnalUmum($paramsJurnal);
        // Hapus Jurnal Umum dan Trans Detail - END

        return successResponse($response, []);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->post('/t_penjualan/cancel', function ($request, $response) {

    $params = $request->getParams();
    $db = $this->db;

    try {
        $db->update('inv_penjualan', ['status' => 'dibatalkan'], ['id' => $params['id']]);
        $db->update('inv_penjualan_det', ['jumlah' => 0], ['inv_penjualan_id' => $params['id']]);
        $db->delete('inv_kartu_stok', ['trans_id' => $params['id'], 'trans_tipe' => 'inv_penjualan_id']);

        // Hapus Jurnal Umum dan Trans Detail
        $paramsJurnal = [
          'reff_type'   => 'inv_penjualan',
          'reff_id'     => $params['id'],
        ];
        hapusJurnalUmum($paramsJurnal);
        // Hapus Jurnal Umum dan Trans Detail- END

        return successResponse($response, []);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->post('/t_penjualan/unpost', function ($request, $response) {

    $params = $request->getParams();
    $db = $this->db;

    try {
        $delete = $db->delete('inv_kartu_stok', ['trans_id' => $params['id'], 'trans_tipe' => 'inv_penjualan_id']);
        $update = $db->update('inv_penjualan', ['status' => 'pending'], ['id' => $params['id']]);

        // Hapus Jurnal Umum dan Trans Detail
        $paramsJurnal = [
          'reff_type'   => 'inv_penjualan',
          'reff_id'     => $params['id'],
        ];
        hapusJurnalUmum($paramsJurnal);
        // Hapus Jurnal Umum dan Trans Detail- END

        return successResponse($response, []);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->post('/t_penjualan/update_after_retur', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    try {
        $db->run("UPDATE inv_penjualan SET total = total - " . $params['form']['total'] . ", cash = cash - " . $params['form']['total'] . ", status = 'approved2' WHERE id = " . $params['form']['inv_penjualan_id']['id']);

        foreach ($params['detail'] as $key => $val) {
            $db->update('inv_penjualan_det', ['jumlah' => $val['jumlah'] - $val['jumlah_retur']], ['inv_penjualan_id' => $val['inv_penjualan_id'], 'inv_m_barang_id' => $val['inv_m_barang_id']['id']]);
            $db->update('inv_kartu_stok', ['jumlah_keluar' => $val['jumlah'] - $val['jumlah_retur']], ['trans_tipe' => 'inv_penjualan_id', 'trans_id' => $val['inv_penjualan_id'], 'inv_m_barang_id' => $val['inv_m_barang_id']['id']]);
        }

        return successResponse($response, []);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->post('/t_penjualan/sinkronInvoice', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $mulai = $params['tanggal'];
    $selesai = date("Y-m-t", strtotime($params['tanggal']));

    try {
      $setNull = $db->run("UPDATE inv_penjualan
        SET no_invoice = NULL
        WHERE MONTH(FROM_UNIXTIME(inv_penjualan.tanggal)) = '". date('m', strtotime($mulai)) ."'
        AND YEAR(FROM_UNIXTIME(inv_penjualan.tanggal)) = '". date('Y', strtotime($mulai)) ."'
      ");

        $models = $db->select("inv_penjualan.*, acc_m_lokasi.nama as namaLokasi, FROM_UNIXTIME(inv_penjualan.tanggal, '%m/%Y') as bulantahun")
                ->from("inv_penjualan")
                ->join("JOIN", "acc_m_lokasi", "acc_m_lokasi.id = inv_penjualan.acc_m_lokasi_id")
                ->where("is_ppn", "=", 1)
                ->customWhere("FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') >= '$mulai'", "AND")
                ->customWhere("FROM_UNIXTIME(inv_penjualan.tanggal, '%Y-%m-%d') <= '$selesai'", "AND")
                ->orderBy("tanggal, FIELD(acc_m_lokasi.nama, 'BOJONEGORO', 'CAMPLONG', 'CIAMIS', 'CIKAMPEK', 'KEDIRI', 'MAGETAN', 'PROBOLINGGO', 'SEMARANG', 'MALANG', 'JEMBER'), inv_penjualan.no_surat_jalan")
                ->findAll();


        $index = 1;
        foreach ($models as $key => $value) {
//            $cek = $db->find("SELECT no_invoice FROM inv_penjualan WHERE inv_proses_akhir_id IS NULL AND no_invoice <> '' AND no_invoice LIKE '%$value->bulantahun' ORDER BY id DESC");
//            $urut = (empty($cek)) || $index == 0 ? 1 : ((int) substr($cek->no_invoice, 0, 5)) + 1;
            $no_urut = substr('00000' . $index, -5);
            $no_transaksi = $no_urut . "/" . date('m', $value->tanggal) . "/" . date('Y', $value->tanggal);

            $urut = (empty($no_transaksi)) ? 1 : ((int) substr($no_transaksi, 0, 5));
            $no_kuitansi = substr('00000' . $urut, -5);

            $db->update("inv_penjualan", ["no_invoice" => $no_transaksi, "no_kuitansi" => $no_kuitansi], ["id" => $value->id]);

            $index++;
        }
        return successResponse($response, []);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->post('/t_penjualan/hapusFaktur', function ($request, $response) {
    $params = $request->getParams();
    $db     = $this->db;

    try {
        $delete = $db->delete('inv_kartu_stok', ['trans_id' => $params['id'], 'trans_tipe' => 'inv_penjualan_id']);
        $update = $db->delete('inv_penjualan', ['id' => $params['id']]);
        $update = $db->delete('inv_penjualan_det', ['inv_penjualan_id' => $params['id']]);

        // Hapus Jurnal Umum dan Trans Detail
        $paramsJurnal = [
          'reff_type'   => 'inv_penjualan',
          'reff_id'     => $params['id'],
        ];
        hapusJurnalUmum($paramsJurnal);
        // Hapus Jurnal Umum dan Trans Detail- END

        return successResponse($response, ['Berhasil menghapus data penjualan.']);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->get('/t_penjualan/testingJurnal', function ($request, $response) {
    $params = $request->getParams();
    $db     = $this->db;

    try {
      $paramsJurnal = [
        'reff_type'   => $params['reff_type'],
        'reff_id'     => $params['reff_id'],
      ];
      simpanJurnal($paramsJurnal);


      return successResponse($response, ['Berhasil insert jurnal']);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});

$app->get('/t_penjualan/testingHapusJurnal', function ($request, $response) {
    $params = $request->getParams();
    $db     = $this->db;

    try {
      $paramsJurnal = [
        'reff_type'   => $params['reff_type'],
        'reff_id'     => $params['reff_id'],
      ];
      hapusJurnalUmum($paramsJurnal);

      return successResponse($response, ['Berhasil hapus jurnal']);
    } catch (Exception $exc) {
        return unprocessResponse($response, $exc);
    }
});
