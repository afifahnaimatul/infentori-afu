<?php

$app->get('/m_satuan/index', function ($request, $response) {
    $params = $request->getParams();
    $db = $this->db;

    $offset = isset($params['offset']) ? $params['offset'] : 0;
    $limit = isset($params['limit']) ? $params['limit'] : 10;

    $db->select("*")
            ->from("inv_m_satuan");

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

    return successResponse($response, [
        'list' => $models,
        'totalItems' => $totalItem,
    ]);

});

$app->post('/m_satuan/trash', function ($request, $response) {
    $data = $request->getParams();
    $db = $this->db;

    $model = $db->update("inv_m_satuan", ['is_deleted' => $data['is_deleted']], array('id' => $data['id']));
    if ($model) {
        return successResponse($response, $model);
    } else {
        return unprocessResponse($response, ['Gagal menghapus data']);
    }
});

function validasi($data, $custom = array()) {
    $validasi = array(
        'nama' => 'required'
    );
    $cek = validate($data, $validasi, $custom);
    return $cek;
}

$app->post('/m_satuan/save', function ($request, $response) {
    $params = $request->getParams();
    $db     = $this->db;

    if ( validasi($params) !== true ) {
      return unprocessResponse($response, $validasi);
    }

    try{
      if (isset($params["id"])) {
        $model = $db->update("inv_m_satuan", $params, array('id' => $params['id']));
      } else {
        $model = $db->insert("inv_m_satuan", $params);
      }

      return successResponse($response, $model);

    } catch(Exception $e){
      return unprocessResponse($response, ['Terjadi kesalahan pada server!']);
    }
});
