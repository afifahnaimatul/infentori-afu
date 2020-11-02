<?php

$app->get('/tes/migrasiAkun', function ($request, $response) {
    $params   = $request->getParams();
    $db       = $this->db;
die;
    $data = $db->select("*")
            ->from("acc_m_akun")
            ->where('is_deleted', '=', 0)
            ->findAll();

    $result = [];
    foreach ($data as $key => $value) {
      if( strlen($value->kode) > 3 ){
        $kode_baru = chunk_split($value->kode, 3, ".");
        $db->update("acc_m_akun", ['kode'=>$kode_baru], ['id'=>$value->id]);
      }
    }

    return successResponse($response, [
      'status'       => 'berhasil',
    ]);
});

$app->get('/tes/cocoklogi', function ($request, $response) {
    $params   = $request->getParams();
    $db       = $this->db;

    $getPenjualan = $db->select("
      sum(jumlah) jumlah
    ")
    ->from("inv_m_penjualan_det")
    ->find();
    pd($getPenjualan);
    return successResponse($response, [
      'status'       => 'berhasil',
    ]);
});

$app->get('/tes/timezone', function ($request, $response) {
    $params   = $request->getParams();
    $db       = $this->db;

    // $getPenjualan = $db->select("
    //   sum(jumlah) jumlah
    // ")
    // ->from("inv_m_penjualan_det")
    // ->find();

  $now = new DateTime();
  $mins = $now->getOffset() / 60;

  $sgn = ($mins < 0 ? -1 : 1);
  $mins = abs($mins);
  $hrs = floor($mins / 60);
  $mins -= $hrs * 60;

  $offset = sprintf('%+d:%02d', $hrs*$sgn, $mins);
  $timezoneMYSQL = $db->find("SELECT now() AS now");

  $setTimezone = $db->run("SET time_zone='$offset';");
  $timezoneEdit = $db->find("SELECT now() AS now");

  $data = [
    'mysql time now'    => $timezoneMYSQL,
    'server time now'   => $timezoneEdit,
    'offset'            => $offset,
  ];

  pd($data);
    return successResponse($response, [
      'status'       => 'berhasil',
    ]);
});


$app->get('/tes/resetFPAPRIL', function ($request, $response) {
    $params   = $request->getParams();
    $db       = $this->db;
    //
    // $reset = $db->run("UPDATE inv_penjualan
    //   SET inv_m_faktur_pajak_id = NULL
    //   where month(from_unixtime(tanggal)) = '04'
    //   and year(from_unixtime(tanggal)) = '2020'
    // ");

    return successResponse($response, [
      'status'       => 'berhasil',
    ]);
});
