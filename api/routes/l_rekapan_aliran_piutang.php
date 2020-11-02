<?php


function getSaldoAwalPiutang($params = []){
  $db         = config('DB');
  $db         = new Cahkampung\Landadb($db['db']);
  $saldoAwal  = 0;

  if( empty($params['tanggal']) ){
    return $saldoAwal;
  }

  // GET PENAMBAHAN PIUTANG
  // PENAMBAHAN PENJUALAN
  $db->select("SUM(total) + SUM(ROUND(total*10/100, 0)) as total")
  ->from("inv_penjualan")
  ->where("YEAR(FROM_UNIXTIME(tanggal))", "<", $params['tanggal'] )
  ->andWhere("status", "!=", 'draft' )
  ->andWhere("status", "!=", 'dibatalkan' );
  $getPenjualan = $db->find();

  if( !empty($getPenjualan) ){
    $saldoAwal += $getPenjualan->total;
  }
  // PENAMBAHAN PENJUALAN -END

  // PENAMBAHAN PEMBULATAN

  // PENAMBAHAN PEMBULATAN - END

  // PENAMBAHAN RETUR PEMBELIAN
  $db->select("SUM(total) + SUM(ppn) as total")
  ->from("inv_retur_pembelian")
  ->where("YEAR(FROM_UNIXTIME(tanggal))", "<", $params['tanggal'] )
  ->andWhere("inv_retur_pembelian.status", "=", 'berhasil');
  $getRetur = $db->find();

  if( !empty($getRetur) ){
    $saldoAwal += $getRetur->total;
  }
  // PENAMBAHAN RETUR PEMBELIAN - END
  // GET PENAMBAHAN PIUTANG - END

  // Pengurangan Piutang
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
  ->where("acc_m_akun_peta.type", "=", "Piutang Usaha")
  ->andWhere("YEAR(acc_trans_detail.tanggal)", "<", $params['tanggal'] );
  $trans_bayar_piutang = $db->andWhere("acc_trans_detail.kredit", ">", 0)->findAll();

  $trans_bayar_id = [];
  if( !empty($trans_bayar_piutang) ){
    foreach ($trans_bayar_piutang as $key => $value) {
      $trans_bayar_id[] = $value->reff_id;
    }

    $trans_bayar_id = implode(',', $trans_bayar_id);

    $getPengurangan = $db->select("SUM(acc_trans_detail.debit) as debit")
    ->from("acc_trans_detail")
    ->customWhere("acc_trans_detail.reff_id IN(". $trans_bayar_id .")", "AND")
    ->andWhere("acc_trans_detail.reff_type", "=", 'acc_jurnal')
    ->andWhere("acc_trans_detail.debit", ">", 0)
    ->andWhere("YEAR(acc_trans_detail.tanggal)", "<", $params['tanggal'])
    ->find();

    if( !empty($getPengurangan) ){
      $saldoAwal -= $getPengurangan->debit;
    }
  }

  return $saldoAwal;
}

$app->get('/l_rekapan_aliran_piutang/laporan', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    // echo json_encode($params); die();

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

    // GET PENAMBAHAN PIUTANG
    $listPenambahan   = [];
    $totalPenambahan  = [];

    // PENAMBAHAN PENJUALAN
    $db->select("
      FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
      SUM(total) + SUM(ROUND(total*10/100, 0)) as total
    ")
    ->from("inv_penjualan")
    ->where("YEAR(FROM_UNIXTIME(tanggal))", "=", date("Y", strtotime($tanggal_end)) )
    ->andWhere("status", "!=", 'draft' )
    ->andWhere("status", "!=", 'dibatalkan' );

    if( !empty($params['m_lokasi_id']) ){
      $db->andWhere("acc_m_lokasi_id", "=", $params['m_lokasi_id'] );
    }

    $db->groupBy("YEAR(FROM_UNIXTIME(tanggal)), MONTH(FROM_UNIXTIME(tanggal))");
    $getPenjualan = $db->findAll();

    $listPenjualan = [];
    if( !empty($getPenjualan) ){
      foreach ($getPenjualan as $key => $value) {
        $listPenjualan[$value->bulan] = $value->total;
      }
    }

    $listPenambahan[0]['keterangan']  = 'PENJUALAN ' . date("Y", strtotime($tanggal_end));

    $totalFP = 0;
    foreach ($data['listBulan'] as $key => $value) {
      $listPenambahan[0]['detail'][$key]['jumlah']  = isset($listPenjualan[$key]) ? $listPenjualan[$key] : 0;
      @$totalPenambahan[$key]   += $listPenambahan[0]['detail'][$key]['jumlah'];
      $totalFP                  += $listPenambahan[0]['detail'][$key]['jumlah'];
    }
    $listPenambahan[0]['detail']['9999-99']['jumlah'] = $totalFP;
    @$totalPenambahan['9999-99']                      += $totalFP;
    // PENAMBAHAN PENJUALAN -END

    // PENAMBAHAN PEMBULATAN
    $totalBulatan = 0;
    $listBulatan = [];
    $listPenambahan[1]['keterangan']  = 'PEMBULATAN ' . date("Y", strtotime($tanggal_end));
    foreach ($data['listBulan'] as $key => $value) {
      $listPenambahan[1]['detail'][$key]['jumlah']  = isset($listBulatan[$key]) ? $listBulatan[$key] : 0;
      @$totalPenambahan[$key]       += $listPenambahan[1]['detail'][$key]['jumlah'];
      $totalBulatan                 += $listPenambahan[1]['detail'][$key]['jumlah'];
    }
    $listPenambahan[1]['detail']['9999-99']['jumlah'] = $totalBulatan;
    @$totalPenambahan['9999-99']                      += $totalBulatan;
    // PENAMBAHAN PEMBULATAN - END

    // PENAMBAHAN RETUR PEMBELIAN
    $db->select("
      FROM_UNIXTIME(tanggal, '%Y-%m') as bulan,
      SUM(total) + SUM(ppn) as total
    ")
    ->from("inv_retur_pembelian")
    ->where("YEAR(FROM_UNIXTIME(tanggal))", "=", date("Y", strtotime($tanggal_end)) )
    ->andWhere("inv_retur_pembelian.status", "=", 'berhasil')
    ->groupBy("YEAR(FROM_UNIXTIME(tanggal)), MONTH(FROM_UNIXTIME(tanggal))");
    $getRetur = $db->findAll();

    $listRetur = [];
    if( !empty($getRetur) ){
      foreach ($getRetur as $key => $value) {
        $listRetur[$value->bulan] = $value->total;
      }
    }

    $listPenambahan[2]['keterangan']  = 'Retur';
    $totalRetur=0;
    foreach ($data['listBulan'] as $key => $value) {
      $listPenambahan[2]['detail'][$key]['jumlah']  = isset($listRetur[$key]) ? $listRetur[$key] : 0;
      @$totalPenambahan[$key]   += $listPenambahan[2]['detail'][$key]['jumlah'];
      $totalRetur               += $listPenambahan[2]['detail'][$key]['jumlah'];
    }
    $listPenambahan[2]['detail']['9999-99']['jumlah']   = $totalRetur;
    @$totalPenambahan['9999-99']                       += $totalRetur;
    // PENAMBAHAN RETUR PEMBELIAN - END
    // GET PENAMBAHAN PIUTANG - END

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
    ->where("acc_m_akun_peta.type", "=", "Piutang Usaha")
    ->andWhere("YEAR(acc_trans_detail.tanggal)", "=", date("Y", strtotime($tanggal_end)) );

    if( !empty($params['m_lokasi_id']) ){
      $db->andWhere("m_lokasi_id", "=", $params['m_lokasi_id'] );
    }

    $trans_bayar_piutang = $db->andWhere("acc_trans_detail.kredit", ">", 0)->findAll();

    $trans_bayar_id = [];
    if( !empty($trans_bayar_piutang) ){
      foreach ($trans_bayar_piutang as $key => $value) {
        $trans_bayar_id[] = $value->reff_id;
      }

      $trans_bayar_id = implode(',', $trans_bayar_id);

      $getPengurangan = $db->select("
        acc_trans_detail.id,
        acc_trans_detail.reff_type,
        acc_trans_detail.reff_id,
        acc_trans_detail.m_akun_id,
        acc_m_akun.nama,
        SUM(acc_trans_detail.debit) as debit,
        tanggal
      ")
      ->from("acc_trans_detail")
      ->join("LEFT JOIN", "acc_m_akun", "acc_m_akun.id = acc_trans_detail.m_akun_id")
      ->join("LEFT JOIN", "acc_m_akun_peta", "acc_m_akun.id = acc_m_akun_peta.m_akun_id")
      ->customWhere("acc_trans_detail.reff_id IN(". $trans_bayar_id .")", "AND")
      ->andWhere("acc_trans_detail.reff_type", "=", 'acc_jurnal')
      ->andWhere("acc_trans_detail.debit", ">", 0)
      ->andWhere("YEAR(acc_trans_detail.tanggal)", "=", date("Y", strtotime($tanggal_end)))
      ->groupBy("Month(tanggal), YEAR(tanggal), acc_trans_detail.m_akun_id")
      ->findAll();

      $listPengurangan = [];
      if(!empty($getPengurangan)){
        foreach ($getPengurangan as $key => $value) {
          $listPengurangan[$value->m_akun_id]['m_akun_id'] = $value->m_akun_id;
          $listPengurangan[$value->m_akun_id]['keterangan'] = $value->nama;
          $listPengurangan[$value->m_akun_id]['bulan'][ date("Y-m", strtotime($value->tanggal)) ] = $value->debit;
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
      $saldo_akhir[$key] = $value - (!empty($totalPengurangan[$key]) ? $totalPengurangan[$key] : 0);
    }

    // Get saldo awal piutang-customer
    $saldo_awal = getSaldoAwalPiutang($params);
    $saldo_akhir['9999-99'] -= $saldo_awal;

    if (isset($params['export']) && $params['export'] == 1) {
        $view = twigView();
        $content = $view->fetch('laporan/rekapan_aliran_piutang.html', [
            "data"            => $data,
            "detail_tambah"   => $listPenambahan,
            "total_tambah"    => $totalPenambahan,
            "detail_kurang"   => $listPenguranganFinal,
            "total_kurang"    => $totalPengurangan,
            "saldo_akhir"     => $saldo_akhir,
            "saldo_awal"      => $saldo_awal,
            "css"             => modulUrl() . '/assets/css/style.css',
        ]);
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment;Filename=laporan-rekapan-aliran-piutang.xls");
        echo $content;
    } elseif (isset($params['print']) && $params['print'] == 1) {
        $view = twigView();
        $content = $view->fetch('laporan/rekapan_aliran_piutang.html', [
            "data"            => $data,
            "data"            => $data,
            "detail_tambah"   => $listPenambahan,
            "total_tambah"    => $totalPenambahan,
            "detail_kurang"   => $listPenguranganFinal,
            "total_kurang"    => $totalPengurangan,
            "saldo_akhir"     => $saldo_akhir,
            "saldo_awal"      => $saldo_awal,
            "css"             => modulUrl() . '/assets/css/style.css',
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
