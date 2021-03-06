<?php

$app->get('/acc/l_laba_rugi/laporan', function ($request, $response) {

    $data['img'] = imgLaporan();

    $params = $request->getParams();
    $sql = $this->db;
    /*
     * tanggal awal
     */
    $tanggal_awal = new DateTime($params['startDate']);
    $tanggal_awal->setTimezone(new DateTimeZone('Asia/Jakarta'));
    /*
     * tanggal akhir
     */
    $tanggal_akhir = new DateTime($params['endDate']);
    $tanggal_akhir->setTimezone(new DateTimeZone('Asia/Jakarta'));
    $tanggal_start = $tanggal_awal->format("Y-m-d");
    $tanggal_end = $tanggal_akhir->format("Y-m-d");
    /*
     * return untuk header
     */
    $data['tanggal'] = date("d M Y", strtotime($tanggal_start)) . ' s/d ' . date("d M Y", strtotime($tanggal_end));
    $data['disiapkan'] = date("d-m-Y, H:i");
    $data['lokasi'] = "Semua";
    if (isset($params['m_lokasi_id']) && !empty($params['m_lokasi_id'])) {
        $data['lokasi'] = $params['lokasi_nama'];
    }
    /*
     * panggil function saldo laba rugi, karena digunakan juga di laporan neraca
     */
    $labarugi = getLabaRugi($tanggal_start, $tanggal_end, $params['m_lokasi_id']);
    $arr = $labarugi['data'];
    $pendapatan = isset($labarugi['total']['PENDAPATAN']) ? $labarugi['total']['PENDAPATAN'] : 0;
    $beban = isset($labarugi['total']['BEBAN']) ? $labarugi['total']['BEBAN'] : 0;
    $pendapatanLuarUsaha = isset($labarugi['total']['PENDAPATAN DILUAR USAHA']) ? $labarugi['total']['PENDAPATAN DILUAR USAHA'] : 0;
    $bebanLuarUsaha = isset($labarugi['total']['BEBAN DILUAR USAHA']) ? $labarugi['total']['BEBAN DILUAR USAHA'] : 0;
    $data['total'] = $pendapatan + $pendapatanLuarUsaha - $beban - $bebanLuarUsaha;
    $data['lr_usaha'] = $pendapatan - $beban;
    $data['is_detail'] = $params['is_detail'];
    
    foreach ($arr as $key => $value) {
        foreach ($value['detail'] as $keys => $values) {
            if($values['nominal'] == 0){
                unset($arr[$key]['detail'][$keys]);
            }
        }
    }
    
    if (isset($params['export']) && $params['export'] == 1) {
        $view = twigViewPath();
        $content = $view->fetch('laporan/labaRugi.html', [
            "data" => $data,
            "detail" => $arr,
            "totalsemua" => $data['total'],
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment;Filename=laporan-laba-rugi.xls");
        echo $content;
    } elseif (isset($params['print']) && $params['print'] == 1) {
        $view = twigViewPath();
        $content = $view->fetch('laporan/labaRugi.html', [
            "data" => $data,
            "detail" => $arr,
            "totalsemua" => $data['total'],
            "css" => modulUrl() . '/assets/css/style.css',
        ]);
        echo $content;
        echo '<script type="text/javascript">window.print();setTimeout(function () { window.close(); }, 500);</script>';
    } else {
        return successResponse($response, ["data" => $data, "detail" => $arr]);
    }
});
