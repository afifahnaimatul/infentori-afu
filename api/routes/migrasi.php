<?php


function validasi($data, $custom = array()) {
    $validasi = array(
        'reff_type'       => 'required',
        'chunks'          => 'required',
        'bulan_pembelian' => 'required',
    );

    GUMP::set_field_name("reff_type", "Jenis transaksi");
    GUMP::set_field_name("bulan_pembelian", "Bulan");

    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->get('/migrasi/migrasiGetPembelian', function ($request, $response) {
  $params = $request->getParams();
  $db = $this->db;

  $validasi = validasi($params);
  if( $validasi !== true ){
      return unprocessResponse($response, $validasi);
  }

  if( $params['reff_type'] == 'inv_pembelian' ){
    $getDetTransaksi = $db->select("inv_pembelian.id")
    ->from("inv_pembelian")
    ->where("YEAR(FROM_UNIXTIME(inv_pembelian.tanggal))", "=", date("Y", strtotime($params['bulan_pembelian'])))
    ->andWhere("MONTH(FROM_UNIXTIME(inv_pembelian.tanggal))", "=", date("m", strtotime($params['bulan_pembelian'])))
    ->andWhere("inv_pembelian.status", "!=", 'draft')
    ->andWhere("inv_pembelian.status", "!=", 'unpost')
    ->findAll();

  } else if( $params['reff_type'] == 'inv_penjualan' ) {
    $getDetTransaksi = $db->select("inv_penjualan.id")
    ->from("inv_penjualan")
    ->where("YEAR(FROM_UNIXTIME(inv_penjualan.tanggal))", "=", date("Y", strtotime($params['bulan_pembelian'])))
    ->andWhere("MONTH(FROM_UNIXTIME(inv_penjualan.tanggal))", "=", date("m", strtotime($params['bulan_pembelian'])))
    ->andWhere("inv_penjualan.status", "!=", 'draft')
    ->andWhere("inv_penjualan.status", "!=", 'dibatalkan')
    ->findAll();

  }


  if(empty($getDetTransaksi))
    return unprocessResponse($response, ['Data tidak ditemukan']);

  $listTransaksi = [];
  foreach ($getDetTransaksi as $key => $value) {
    $listTransaksi[] = $value->id;
  }

  $listTransaksi = array_chunk($listTransaksi, $params['chunks']);

  return successResponse($response, [
    'list'        => $listTransaksi,
    'dataLength'  => sizeof($listTransaksi),
  ]);
});

$app->post('/migrasi/migrasiJurnalPembelian', function ($request, $response) {
  $params = $request->getParams();
  $db     = $this->db;

  foreach ($params['list_id'] as $key => $value) {
    $paramsJurnal = [
      'reff_type'   => $params['reff_type'],
      'reff_id'     => $value,
    ];
    simpanJurnal($paramsJurnal);
  }

  return successResponse($response, [
    'list'        => true,
  ]);
});

$app->get('/migrasi/test', function ($request, $response) {
  $params = $request->getParams();
  $db     = $this->db;

  pd([
    "hahaha",
    $params
  ]);die;

  return successResponse($response, [
    'list'        => true,
  ]);
});


$app->get('/migrasi/migrasiPenjualanPPN', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;
    // pd();

    $db->select("id,total, piutang")
        ->from('inv_penjualan');

    $penjualan = $db->findAll();


    $juQuery = 'INSERT INTO inv_penjualan (id, piutang) VALUES ';

    $panjangArray = sizeof($penjualan);

    foreach ($penjualan as $key => $value) {
        $piutang = $value->total + round($value->total / 10 );
        $juQuery .= "('". $value->id ."', '". $piutang ."')";
        $juQuery .= ($key==($panjangArray-1)) ? '' : ',';
    }
    $juQuery .= " ON DUPLICATE KEY UPDATE id=VALUES(id), piutang=VALUES(piutang)";

    $juQuery = $db->run($juQuery);
    echo json_encode($juQuery);
});

$app->get('/migrasi/migrasiPembelianPPN', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;
    // pd();

    $db->select("id,hutang,total, ppn_edit")
        ->from('inv_pembelian');

    $pembelian = $db->findAll();



    $juQuery = 'INSERT INTO inv_pembelian (id, hutang) VALUES ';

    $panjangArray = sizeof($pembelian);

    foreach ($pembelian as $key => $value) {
        $hutang = $value->total + $value->ppn_edit;
        $juQuery .= "('". $value->id ."', '". $hutang ."')";
        $juQuery .= ($key==($panjangArray-1)) ? '' : ',';
    }
    $juQuery .= " ON DUPLICATE KEY UPDATE id=VALUES(id), hutang=VALUES(hutang)";

    $juQuery = $db->run($juQuery);
    echo json_encode($juQuery);
});
