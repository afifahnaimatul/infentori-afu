<?php

function getConfig()
{
    $subDomain = $_SERVER['HTTP_HOST'];
    if ($subDomain == "systems.larensi.com") {
        $path = 'config/landa.php';
    } else if ($subDomain == "proptech.larensi.com"){
        $path = 'config/rain.php';
    } else if ($subDomain == "baca.larensi.com"){
        $path = 'config/wb.php';
    } else{
        $path = 'config/config.php';
    }

    return $path;
}
