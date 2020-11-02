<?php

$app->get('/m_kategori/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("
      inv_m_kategori.*,
      inv_m_jenis.jenis
      ")
      ->from("inv_m_kategori")
      ->join('LEFT JOIN', "inv_m_jenis", "inv_m_jenis.id = inv_m_kategori.inv_m_jenis_id");

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

    $allJenis = getAllData($db, 'inv_m_jenis', 'id');
    foreach($models as $key => $value) {
      $models[$key]->inv_m_jenis_id = isset($allJenis[$value->inv_m_jenis_id]) ? $allJenis[$value->inv_m_jenis_id] : NULL;
    }

    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
    ]);

});

$app->post('/m_kategori/trash', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $model = $db->update("inv_m_kategori", ['is_deleted' => $data['is_deleted']], array('id' => $data['id']));
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

function validasi($data, $custom = array()) {
    $validasi = array(
        'nama'            => 'required',
        'inv_m_jenis_id'  => 'required',
        'is_dijual' => 'required'
    );

    GUMP::set_field_name("inv_m_jenis_id", "Jenis");
    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->post('/m_kategori/save', function ($request, $response) {
    $params = $request->getParams();
    $db     = $this->db;

    $validasi = validasi($params);
    if ( $validasi !== true ) {
      return unprocessResponse($response, $validasi);
    }

    try{
      $params['inv_m_jenis_id'] = !empty($params['inv_m_jenis_id']['id']) ? $params['inv_m_jenis_id']['id'] : 0;

      if (isset($params["id"])) {
        $model = $db->update("inv_m_kategori", $params, array('id' => $params['id']));
      } else {
        $model = $db->insert("inv_m_kategori", $params);
      }

      return successResponse($response, $model);

    } catch(Exception $e){
      return unprocessResponse($response, ['Terjadi kesalahan pada server!']);
    }
});
