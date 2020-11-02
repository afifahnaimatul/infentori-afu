<?php

//error_reporting(0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require 'vendor/autoload.php';
require 'systems/domain.php';
require 'systems/systems.php';
require 'systems/functions.php';

$db = new Cahkampung\Landadb(config('DB')['db']);

//$db->run("TRUNCATE acc_m_kontak");
//pre($a);
//die;

try {
    $inputFileType = PHPExcel_IOFactory::identify("import_data/impoer_sabtu.xlsx");
    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
    $objPHPExcel = $objReader->load("import_data/impoer_sabtu.xlsx");
} catch (Exception $e) {
    die('Error loading file "' . pathinfo("import_data/impoer_sabtu.xlsx", PATHINFO_BASENAME) . '": ' . $e->getMessage());
}

$sheet = $objPHPExcel->getSheet(3);
$highestRow = $sheet->getHighestRow();
$highestColumn = $sheet->getHighestColumn();
$models = [];
$urut = 1;
$kembar = "";
for ($row = 4; $row <= $highestRow; $row++) {
//    echo $objPHPExcel->getSheet(3)->getCell('K' . $row)->getFormattedValue() . "<br>";
//    if ($objPHPExcel->getSheet(3)->getCell('A' . $row)->getFormattedValue() != "") {
//        for ($a = 0; $a < $objPHPExcel->getSheet(0)->getCell('F' . $row)->getValue(); $a++) {
    $no = substr('00000' . $urut, -5);
//            echo $no;
    $kode_var = "CUST2019" . $no;
//            echo $kode_var;
    $query = $objPHPExcel->getSheet(3)->getCell('K' . $row)->getFormattedValue();
    $query = str_replace("kode_var", $kode_var, $query);
    if ($objPHPExcel->getSheet(3)->getCell('E' . $row)->getFormattedValue() . ";" . $objPHPExcel->getSheet(3)->getCell('H' . $row)->getFormattedValue() != $kembar) {
        echo $query . "<br/>";
        $kembar = $objPHPExcel->getSheet(3)->getCell('E' . $row)->getFormattedValue() . ";" . $objPHPExcel->getSheet(3)->getCell('H' . $row)->getFormattedValue();
        $urut++;
    }

//            $db->run($query);
//        }
//    }
}
?>
