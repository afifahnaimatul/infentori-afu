<?php

$app->get('/m_faktur_pelabuhan/index', function ($request, $response) {
    $params = $request->getParams();
    $db     = $this->db;

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit  = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("
      inv_m_faktur_pajak.*,
      acc_m_kontak.kode as kontak_kode,
      acc_m_kontak.id as kontak_id,
      acc_m_kontak.nama as kontak_nama
      ")
    ->from("inv_m_faktur_pajak")
    ->leftJoin("acc_m_kontak", "acc_m_kontak.id = inv_m_faktur_pajak.acc_m_kontak_id")
    ->where("inv_m_faktur_pajak.jenis_faktur", "=", "pelabuhan");

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
                $sort.=" ASC";
            } else {
                $sort.=" DESC";
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
    $models = $db->findAll();
    $totalItem = $db->count();

    if(!empty($models)){
      foreach ($models as $key => $value) {
        $models[$key]->acc_m_kontak_id = [
          'id'    => $value->kontak_id,
          'kode'  => $value->kontak_kode,
          'nama'  => $value->kontak_nama
        ];

      }
    }

    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
    ]);

});

$app->post('/m_faktur_pelabuhan/trash', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $model = $db->update("inv_m_faktur_pajak", ['is_deleted' => $data['is_deleted']], array('id' => $data['id']));
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

function validasi($data, $custom = array()) {
    $validasi = array(
        'nomor'           => 'required',
        'acc_m_kontak_id' => 'required',
        'jenis_ppn'       => 'required'
    );

    GUMP::set_field_name("acc_m_kontak_id", "Penyedia Jasa");
    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->post('/m_faktur_pelabuhan/save', function ($request, $response) {
    $params = $request->getParams();
    $db     = $this->db;

    $validasi = validasi($params['form']);
    if ( $validasi !== true ) {
      return unprocessResponse($response, $validasi);
    }

    try{
      $params['form']['acc_m_kontak_id']  = $params['form']['acc_m_kontak_id']['id'];
      $params['form']['tanggal']          = date("Y-m-d", strtotime($params['form']['tanggal']));

      if (isset($params["form"]["id"])) {
        $model      = $db->update("inv_m_faktur_pajak", $params["form"], array('id' => $params["form"]['id']));
        $delDetail  = $db->delete('inv_m_faktur_pajak_det', ['inv_m_faktur_pajak_id' => $model->id]);
      } else {
        $params['form']['jenis_faktur'] = 'pelabuhan';
        $model = $db->insert("inv_m_faktur_pajak", $params["form"]);
      }

      if(!empty($params['detail'])){
        foreach ($params['detail'] as $key => $value) {
          $dataDet                          = (array)$value;
          $dataDet['inv_m_faktur_pajak_id'] = $model->id;

          $insertDet = $db->insert("inv_m_faktur_pajak_det", $dataDet);
        }
      }

      return successResponse($response, $model);

    } catch(Exception $e){
      return unprocessResponse($response, ['Terjadi kesalahan pada server!']);
    }
});

$app->get('/m_faktur_pelabuhan/getDetail', function ($request, $response) {
    $params   = $request->getParams();
    $db     = $this->db;

    try{
      $detail = $db->select("*")
      ->from("inv_m_faktur_pajak_det")
      ->where("inv_m_faktur_pajak_id", "=", $params['id'])
      ->findAll();

      return successResponse($response, $detail);

    } catch(Exception $e) {
      return unprocessResponse($response, ['Gagal mendapatkan detail Faktur.']);
    }

});
