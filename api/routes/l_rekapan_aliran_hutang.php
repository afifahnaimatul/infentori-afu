<?php

function getSaldoAwalHutang($params = []){
  $db         = config('DB');
  $db         = new Cahkampung\Landadb($db['db']);
  $saldoAwal  = 0;

  if( empty($params['tanggal']) ){
    return $saldoAwal;
  }

  // Penambahan Hutang
  // Penambahan FP
  $db->select("SUM(total) + SUM(ppn_edit) as total")
  ->from("inv_pembelian")
  ->where("YEAR(FROM_UNIXTIME(tanggal))", "<", $params['tanggal'] )
  ->andWhere("inv_pembelian.is_ppn", "=", 1)
  ->andWhere("inv_pembelian.is_import", "=", 0)
  ->customWhere("inv_pembelian.jenis_pembelian IN('barang', 'hutang')", "AND");
  $getPembelianPPN = $db->find();

  if( !empty($getPembelianPPN) ){
    $saldoAwal += $getPembelianPPN->total;
  }

  // Penambahan FP Uang Muka
  $db->select("SUM(total) + SUM(ppn_edit) as total")
  ->from("inv_pembelian")
  ->where("YEAR(FROM_UNIXTIME(tanggal))", "<", $params['tanggal'] )
  ->andWhere("inv_pembelian.is_import", "=", 1);
  $getPembelianImport = $db->find();

  if( !empty($getPembelianImport) ){
    $saldoAwal += $getPembelianImport->total;
  }

  // Penambahan Pembelian NON PPN
  $db->select("SUM(total) + SUM(ppn_edit) as total")
  ->from("inv_pembelian")
  ->where("YEAR(FROM_UNIXTIME(tanggal))", "<", $params['tanggal'] )
  ->andWhere("inv_pembelian.is_ppn", "=", 0)
  ->andWhere("inv_pembelian.is_import", "=", 0)
  ->andWhere("inv_pembelian.jenis_pembelian", "=", 'barang');
  $getPembelianNonPPN = $db->find();

  if( !empty($getPembelianNonPPN) ){
    $saldoAwal += $getPembelianNonPPN->total;
  }

  // Penambahan Biaya Pelabuhan
  $db->select("SUM(total) + SUM(ppn_edit) as total")
  ->from("inv_pembelian")
  ->where("YEAR(FROM_UNIXTIME(tanggal))", "<", $params['tanggal'] )
  ->andWhere("inv_pembelian.is_import", "=", 0)
  ->andWhere("inv_pembelian.jenis_pembelian", "=", 'jasa');
  $getPembelianPelabuhan = $db->find();

  if( !empty($getPembelianPelabuhan) ){
    $saldoAwal += $getPembelianPelabuhan->total;
  }

  // Penambahan Retur Penjualan
  $db->select("SUM(total) + SUM(ppn) as total")
  ->from("inv_retur_penjualan")
  ->where("YEAR(FROM_UNIXTIME(tanggal))", "<", $params['tanggal'] )
  ->andWhere("inv_retur_penjualan.status", "=", 'approved');
  $getRetur = $db->find();

  if( !empty($getRetur) ){
    $saldoAwal += $getRetur->total;
  }
  // Penambahan Hutang - END

  // Pengurangan Hutang
  $db->select("
    acc_trans_detail.id,
    acc_trans_detail.reff_type,
    acc_trans_detail.reff_id,
    acc_trans_detail.m_akun_id,
    acc_trans_detail.debit,
    acc_m_akun.nama
  ")
  ->from("acc_m_akun_peta")
  ->join("LEFT JOIN", "acc_m_akun", "acc_m_akun.id = acc_m_akun_peta.m_akun_id")
  ->join("LEFT JOIN", "acc_trans_detail", "acc_m_akun.id = acc_trans_detail.m_akun_id")
  ->where("acc_m_akun_peta.type", "=", "Hutang Usaha")
  ->andWhere("acc_trans_detail.debit", ">", 0)
  ->andWhere("YEAR(acc_trans_detail.tanggal)", "<", $params['tanggal'] );
  $trans_bayar_hutang = $db->findAll();

  $trans_bayar_id = [];
  if( !empty($trans_bayar_hutang) ){
    foreach ($trans_bayar_hutang as $key => $value) {
      $trans_bayar_id[] = $value->reff_id;
    }

    $trans_bayar_id = implode(',', $trans_bayar_id);

    $getPengurangan = $db->select("SUM(acc_trans_detail.kredit) as kredit")
    ->from("acc_trans_detail")
    ->customWhere("acc_trans_detail.reff_id IN(". $trans_bayar_id .")", "AND")
    ->andWhere("acc_trans_detail.reff_type", "=", 'acc_jurnal')
    ->andWhere("acc_trans_detail.kredit", ">", 0)
    ->andWhere("YEAR(acc_trans_detail.tanggal)", "<", $params['tanggal'])
    ->find();

    if( !empty($getPengurangan) ){
      $saldoAwal -= $getPengurangan->kredit;
    }
  }
  // Pengurangan Hutang - END

  // pd([
  //   $getPembelianNonPPN,
  //   $getPembelianImport,
  //   $getPembelianPPN,
  //   $params
  // ]);

  return $saldoAwal;
}

function getAliranHutang($params = [], $tipeLaporan = 'pertahun'){
  $db = config('DB');
  $db = new Cahkampung\Landadb($db['db']);

  $tanggal_start = $params['tanggal'] . '-01-01';
  $tanggal_end = $params['tanggal'] . '-12-31';
  $data['disiapkan'] = date("d/m/Y, H:i");
  $data['tahun'] = $params['tanggal'];

  if (isset($params['m_lokasi_id'])) {
      $data['lokasi'] = $params['nama_lokasi'];
  }

  if (isset($params['m_lokasi_id'])) {
      $lokasiId = getChildId("acc_m_lokasi", $params['m_lokasi_id']);
      /**
       * jika lokasi punya child
       */
      if (!empty($lokasiId)) {
          $lokasiId = implode(",", $lokasiId);
      } else {
          $lokasiId = $params['m_lokasi_id'];
      }
  }

  if (isset($params['m_lokasi_id'])) {
      $lokasi = $db->select("*")->from("acc_m_lokasi")->customWhere("id IN($lokasiId)")->orderBy("id")->findAll();
  }

  $akun_kas = $db->select("*")->from("acc_m_akun")->where("is_kas", "=", 1)->findAll();

  $akunId = [];
  foreach ($akun_kas as $key => $value) {
      $akunId[] = $value->id;
  }

  for ($i = 1; $i <= 12; $i++) {
    $tgl = date("Y-m", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'));
    $data['listBulan'][$tgl]['jumlah'] = 0;
      $data['tanggal'][] = date("F", strtotime($params['tanggal'] . '-' . ($i < 10 ? '0' . $i : $i) . '-01'));
  }

  // Penambahan HUTANG
  $listPenambahan   = [];
  $totalPenambahan  = [];
  // Penambahan FP
  $db->select("
    FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
    SUM(total) + SUM(ppn_edit) as total
  ")
  ->from("inv_pembelian")
  ->where("YEAR(FROM_UNIXTIME(tanggal))", "=", date("Y", strtotime($tanggal_end)) )
  ->andWhere("inv_pembelian.is_ppn", "=", 1)
  ->andWhere("inv_pembelian.is_import", "=", 0)
  // ->andWhere("inv_pembelian.jenis_pembelian", "=", 'barang')
  ->customWhere("inv_pembelian.jenis_pembelian IN('barang', 'hutang')", "AND")
  ->groupBy("YEAR(FROM_UNIXTIME(tanggal)), MONTH(FROM_UNIXTIME(tanggal))");
  $getPembelianPPN = $db->findAll();

  $listPembelianPPN = [];
  if( !empty($getPembelianPPN) ){
    foreach ($getPembelianPPN as $key => $value) {
      $listPembelianPPN[$value->bulan] = $value->total;
    }
  }

  $listPenambahan[0]['keterangan']  = 'PEMBELIAN FP ' . date("Y", strtotime($tanggal_end));

  $totalFP = 0;
  foreach ($data['listBulan'] as $key => $value) {
    $listPenambahan[0]['detail'][$key]['jumlah']  = isset($listPembelianPPN[$key]) ? $listPembelianPPN[$key] : 0;
    @$totalPenambahan[$key]   += $listPenambahan[0]['detail'][$key]['jumlah'];
    $totalFP                  += $listPenambahan[0]['detail'][$key]['jumlah'];
  }
  $listPenambahan[0]['detail']['9999-99']['jumlah'] = $totalFP;
  @$totalPenambahan['9999-99']                      += $totalFP;
  // Penambahan FP - END

  // Penambahan FP Uang Muka
  $db->select("
    FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
    SUM(total) + SUM(ppn_edit) as total
  ")
  ->from("inv_pembelian")
  ->where("YEAR(FROM_UNIXTIME(tanggal))", "=", date("Y", strtotime($tanggal_end)) )
  ->andWhere("inv_pembelian.is_import", "=", 1)
  ->groupBy("YEAR(FROM_UNIXTIME(tanggal)), MONTH(FROM_UNIXTIME(tanggal))");
  $getPembelianImport = $db->findAll();

  $listPembelianImport = [];
  if( !empty($getPembelianImport) ){
    foreach ($getPembelianImport as $key => $value) {
      $listPembelianImport[$value->bulan] = $value->total;
    }
  }

  $listPenambahan[1]['keterangan']  = 'FP UANG MUKA';

  $totalPI = 0;
  foreach ($data['listBulan'] as $key => $value) {
    $listPenambahan[1]['detail'][$key]['jumlah']  = isset($listPembelianImport[$key]) ? $listPembelianImport[$key] : 0;
    @$totalPenambahan[$key]  += $listPenambahan[1]['detail'][$key]['jumlah'];
    $totalPI                 += $listPenambahan[1]['detail'][$key]['jumlah'];;
  }
  $listPenambahan[1]['detail']['9999-99']['jumlah'] = $totalPI;
  @$totalPenambahan['9999-99']                      += $totalPI;
  // Penambahan FP Uang Muka - END

  // Penambahan Pembelian NON PPN
  $db->select("
    FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
    SUM(total) + SUM(ppn_edit) as total
  ")
  ->from("inv_pembelian")
  ->where("YEAR(FROM_UNIXTIME(tanggal))", "=", date("Y", strtotime($tanggal_end)) )
  ->andWhere("inv_pembelian.is_ppn", "=", 0)
  ->andWhere("inv_pembelian.is_import", "=", 0)
  ->andWhere("inv_pembelian.jenis_pembelian", "=", 'barang')
  ->groupBy("YEAR(FROM_UNIXTIME(tanggal)), MONTH(FROM_UNIXTIME(tanggal))");
  $getPembelianNonPPN = $db->findAll();

  $listPembelianNonPPN = [];
  if( !empty($getPembelianNonPPN) ){
    foreach ($getPembelianNonPPN as $key => $value) {
      $listPembelianNonPPN[$value->bulan] = $value->total;
    }
  }

  $listPenambahan[2]['keterangan']  = 'PEMBELIAN NON PPN';
  $totalNOPPN = 0;
  foreach ($data['listBulan'] as $key => $value) {
    $listPenambahan[2]['detail'][$key]['jumlah']  = isset($listPembelianNonPPN[$key]) ? $listPembelianNonPPN[$key] : 0;
    @$totalPenambahan[$key]   += $listPenambahan[2]['detail'][$key]['jumlah'];
    $totalNOPPN               += $listPenambahan[2]['detail'][$key]['jumlah'];
  }
  $listPenambahan[2]['detail']['9999-99']['jumlah']   = $totalNOPPN;
  @$totalPenambahan['9999-99']                       += $totalNOPPN;
  // Penambahan Pembelian NON PPN - END

  // Penambahan Biaya Pelabuhan
  $db->select("
    FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
    SUM(total) + SUM(ppn_edit) as total
  ")
  ->from("inv_pembelian")
  ->where("YEAR(FROM_UNIXTIME(tanggal))", "=", date("Y", strtotime($tanggal_end)) )
  ->andWhere("inv_pembelian.is_import", "=", 0)
  ->andWhere("inv_pembelian.jenis_pembelian", "=", 'jasa')
  ->groupBy("YEAR(FROM_UNIXTIME(tanggal)), MONTH(FROM_UNIXTIME(tanggal))");
  $getPembelianPelabuhan = $db->findAll();

  $listPembelianPelabuhan = [];
  if( !empty($getPembelianPelabuhan) ){
    foreach ($getPembelianPelabuhan as $key => $value) {
      $listPembelianPelabuhan[$value->bulan] = $value->total;
    }
  }

  $listPenambahan[3]['keterangan']  = 'Pembelian PPN (Biaya Pelabuhan)';

  $totalPel = 0;
  foreach ($data['listBulan'] as $key => $value) {
    $listPenambahan[3]['detail'][$key]['jumlah']  = isset($listPembelianNonPPN[$key]) ? $listPembelianNonPPN[$key] : 0;
    @$totalPenambahan[$key]   += $listPenambahan[3]['detail'][$key]['jumlah'];
    $totalPel                 += $listPenambahan[3]['detail'][$key]['jumlah'];
  }
  $listPenambahan[3]['detail']['9999-99']['jumlah']   = $totalPel;
  @$totalPenambahan['9999-99']                       += $totalPel;
  // Penambahan Biaya Pelabuhan - END

  // Penambahan Retur Penjualan
  $db->select("
    FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
    SUM(total) + SUM(ppn) as total
  ")
  ->from("inv_retur_penjualan")
  ->where("YEAR(FROM_UNIXTIME(tanggal))", "=", date("Y", strtotime($tanggal_end)) )
  ->andWhere("inv_retur_penjualan.status", "=", 'approved')
  ->groupBy("YEAR(FROM_UNIXTIME(tanggal)), MONTH(FROM_UNIXTIME(tanggal))");
  $getRetur = $db->findAll();

  $listRetur = [];
  if( !empty($getRetur) ){
    foreach ($getRetur as $key => $value) {
      $listRetur[$value->bulan] = $value->total;
    }
  }

  $listPenambahan[4]['keterangan']  = 'Retur';
  $totalRetur=0;
  foreach ($data['listBulan'] as $key => $value) {
    $listPenambahan[4]['detail'][$key]['jumlah']  = isset($listRetur[$key]) ? $listRetur[$key] : 0;
    @$totalPenambahan[$key]   += $listPenambahan[4]['detail'][$key]['jumlah'];
    $totalRetur               += $listPenambahan[4]['detail'][$key]['jumlah'];
  }
  $listPenambahan[4]['detail']['9999-99']['jumlah']   = $totalRetur;
  @$totalPenambahan['9999-99']                       += $totalRetur;
  // Penambahan Retur Penjualan - END
  // Penambahan HUTANG - END

  // Pengurangan Hutang
  $listPengurangan = $totalPengurangan = [];
  $db->select("
    acc_trans_detail.id,
    acc_trans_detail.reff_type,
    acc_trans_detail.reff_id,
    acc_trans_detail.m_akun_id,
    acc_trans_detail.debit,
    acc_m_akun.nama
  ")
  ->from("acc_m_akun_peta")
  ->join("LEFT JOIN", "acc_m_akun", "acc_m_akun.id = acc_m_akun_peta.m_akun_id")
  ->join("LEFT JOIN", "acc_trans_detail", "acc_m_akun.id = acc_trans_detail.m_akun_id")
  ->where("acc_m_akun_peta.type", "=", "Hutang Usaha")
  ->andWhere("acc_trans_detail.debit", ">", 0)
  ->andWhere("YEAR(acc_trans_detail.tanggal)", "=", date("Y", strtotime($tanggal_end)) );

  // if( !empty($params['m_lokasi_id']) ){
  //   $db->andWhere("m_lokasi_id", "=", $params['m_lokasi_id'] );
  // }

  $trans_bayar_hutang = $db->findAll();

  $trans_bayar_id = [];
  if( !empty($trans_bayar_hutang) ){
    foreach ($trans_bayar_hutang as $key => $value) {
      $trans_bayar_id[] = $value->reff_id;
    }

    $trans_bayar_id = implode(',', $trans_bayar_id);

    $getPengurangan = $db->select("
      acc_trans_detail.id,
      acc_trans_detail.reff_type,
      acc_trans_detail.reff_id,
      acc_m_akun.id as m_akun_id,
      acc_m_akun.nama,
      SUM(acc_trans_detail.kredit) as kredit,
      tanggal
    ")
    ->from("acc_trans_detail")
    ->join("LEFT JOIN", "acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
    ->join("LEFT JOIN", "acc_m_akun_peta", "acc_m_akun.id = acc_m_akun_peta.m_akun_id")
    ->customWhere("acc_trans_detail.reff_id IN(". $trans_bayar_id .")", "AND")
    ->andWhere("acc_trans_detail.reff_type", "=", 'acc_jurnal')
    ->andWhere("acc_trans_detail.kredit", ">", 0)
    ->andWhere("YEAR(acc_trans_detail.tanggal)", "=", date("Y", strtotime($tanggal_end)))
    ->groupBy("Month(tanggal), YEAR(tanggal), acc_trans_detail.m_akun_id")
    ->findAll();

    $listPengurangan = [];
    if(!empty($getPengurangan)){
      foreach ($getPengurangan as $key => $value) {
        $listPengurangan[$key]['m_akun_id'] = $value->m_akun_id;
        $listPengurangan[$key]['keterangan'] = $value->nama;
        $listPengurangan[$key]['bulan'][ date("Y-m", strtotime($value->tanggal)) ] = $value->kredit;
      }
    }
  }

  $listPenguranganFinal = [];
  $index = 0;
  foreach ($listPengurangan as $key => $value) {
    $listPenguranganFinal[$index]['keterangan'] = $value['keterangan'];

    $totalPerIndex[$index] = 0;
    foreach ($data['listBulan'] as $key2 => $value2) {
      $listPenguranganFinal[$index]['detail'][] = [
          'bulan'   => $key2,
          'jumlah'  => isset($value['bulan'][$key2]) ? $value['bulan'][$key2] : 0,
        ];
      @$totalPengurangan[$key2] += isset($value['bulan'][$key2]) ? $value['bulan'][$key2] : 0;
      $totalPerIndex[$index]    += isset($value['bulan'][$key2]) ? $value['bulan'][$key2] : 0;
    }
    $listPenguranganFinal[$index]['detail'][] = [
        'bulan'   => '9999-99',
        'jumlah'  => $totalPerIndex[$index],
      ];
    $index++;
  }

  $totPengurangan = 0;
  if( !empty($totalPerIndex) ){
    foreach ($totalPerIndex as $key => $value) {
      $totPengurangan += $value;
    }
  }
  $totalPengurangan['9999-99'] = $totPengurangan;
  // Pengurangan Hutang - End

  $saldo_akhir = [];
  foreach ($totalPenambahan as $key => $value) {
    $saldo_akhir[$key] = $value - (!empty($totalPengurangan[$key]) ? $totalPengurangan[$key] :0) ;
  }

$finalResult = [
  "data"          => $data,
  "detail_tambah" => $listPenambahan,
  "total_tambah"  => $totalPenambahan,
  "detail_kurang" => $listPenguranganFinal,
  "total_kurang"  => $totalPengurangan,
  "saldo_akhir"   => $saldo_akhir,
];

return $finalResult;
}

$app->get('/l_rekapan_aliran_hutang/laporan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $saldo_awal   = getSaldoAwalHutang($params);
    $finalResult  = getAliranHutang($params, 'pertahun');

    $finalResult['saldo_akhir']['9999-99'] -= $saldo_awal;

    $data                 = $finalResult['data'];
    $listPenambahan       = $finalResult['detail_tambah'];
    $totalPenambahan      = $finalResult['total_tambah'];
    $listPenguranganFinal = $finalResult['detail_kurang'];
    $totalPengurangan     = $finalResult['total_kurang'];
    $saldo_akhir          = $finalResult['saldo_akhir'];

    if (isset($params['export']) && $params['export'] == 1) {
        $view = twigView();
        $content = $view->fetch('laporan/rekapan_aliran_hutang.html', [
            "data"          => $data,
            "detail_tambah" => $listPenambahan,
            "total_tambah"  => $totalPenambahan,
            "detail_kurang" => $listPenguranganFinal,
            "total_kurang"  => $totalPengurangan,
            "saldo_akhir"   => $saldo_akhir,
            "saldo_awal"    => $saldo_awal,
            "css"           => modulUrl() . '/assets/css/style.css',
        ]);
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment;Filename=laporan-rekapan-aliran-hutang.xls");
        echo $content;
    } elseif (isset($params['print']) && $params['print'] == 1) {
        $view = twigView();
        $content = $view->fetch('laporan/rekapan_aliran_hutang.html', [
            "data" => $data,
            "detail_tambah" => $listPenambahan,
            "total_tambah"  => $totalPenambahan,
            "detail_kurang" => $listPenguranganFinal,
            "total_kurang"  => $totalPengurangan,
            "saldo_akhir"   => $saldo_akhir,
            "saldo_awal"    => $saldo_awal,
            "css"           => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, [
          "data"            => $data,
          "detail_tambah"   => $listPenambahan,
          "total_tambah"    => $totalPenambahan,
          "detail_kurang"   => $listPenguranganFinal,
          "total_kurang"    => $totalPengurangan,
          "saldo_akhir"     => $saldo_akhir,
          "saldo_awal"      => $saldo_awal,
        ]);
    }
});
