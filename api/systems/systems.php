<?php
function config($key){
    include getConfig();
    return isset($config[$key]) ? $config[$key] : '';
}

function site_url()
{
    return rtrim(config('SITE_URL'), '/') . '/';
}

function site_path()
{
    static $_path;

    if (!$_path) {
        $_path = rtrim(parse_url(config('SITE_URL'), PHP_URL_PATH), '/');
    }

    return $_path;
}

function img_url()
{
    return rtrim(config('SITE_URL'), '/') . '/';
}

function img_path()
{
    return rtrim(config('IMG_PATH'), '/') . '/';
}

function dispatch()
{
    $path = $_SERVER['REQUEST_URI'];

    if (config('SITE_URL') !== null) {
        $path = preg_replace('@^' . preg_quote(site_path()) . '@', '', $path);
    }

    $parts = preg_split('/\?/', $path, -1, PREG_SPLIT_NO_EMPTY);

    $uri = trim($parts[0], '/');

    if ($uri == 'index.php' || $uri == '') {
        $uri = 'site';
    }

    return $uri;
}

function getUrlFile()
{
    $uri    = dispatch();
    $getUri = explode("/", $uri);

    $path   = isset($getUri[0]) ? $getUri[0] : '';
    $action = isset($getUri[1]) ? $getUri[1] : '';
    $action2 = isset($getUri[2]) ? $getUri[2] : '';
    
    if ($path == 'api') {
        $file = 'routes/' . (isset($action) ? $action : 'sites') . '.php';
        if (file_exists($file)) {
            return $file;
        }
    } else if($path == 'acc') {
        $file = 'acc/landaacc/routes/' . $action . '.php';
        if (file_exists($file)) {
            return $file;
        }
    } else {
        $file = 'routes/' . $path . '.php';

        if (file_exists($file)) {
            return $file;
        }
    }

    return 'routes/sites.php';
}

function successResponse($response, $message)
{
    return $response->write(json_encode([
        'status_code' => 200,
        'data'        => $message,
    ]))->withStatus(200);
}

function unprocessResponse($response, $message)
{
    return $response->write(json_encode([
        'status_code' => 422,
        'errors'      => $message,
    ]))->withStatus(200);
}

function unauthorizedResponse($response, $message)
{
    return $response->write(json_encode([
        'status_code' => 403,
        'errors'      => $message,
    ]))->withStatus(403);
}

function validate($data, $validasi, $custom = [])
{
    if (!empty($custom)) {
        $validasiData = array_merge($validasi, $custom);
    } else {
        $validasiData = $validasi;
    }

    $lang = 'en';
    $gump = new GUMP($lang);
    
    $validate = $gump->is_valid($data, $validasiData);

    if ($validate === true) {
        return true;
    } else {
        return $validate;
    }
}
function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function normalizeChars()
{
    return array(
        'ï¿½' => 'S', 'ï¿½' => 's', 'ï¿½' => 'Dj', 'ï¿½' => 'Z', 'ï¿½' => 'z', 'ï¿½'  => 'A', 'ï¿½' => 'A', 'ï¿½' => 'A', 'ï¿½' => 'A', 'ï¿½' => 'A',
        'ï¿½' => 'A', 'ï¿½' => 'A', 'ï¿½' => 'C', 'ï¿½'  => 'E', 'ï¿½' => 'E', 'ï¿½'  => 'E', 'ï¿½' => 'E', 'ï¿½' => 'I', 'ï¿½' => 'I', 'ï¿½' => 'I',
        'ï¿½' => 'I', 'ï¿½' => 'N', 'ï¿½' => 'O', 'ï¿½'  => 'O', 'ï¿½' => 'O', 'ï¿½'  => 'O', 'ï¿½' => 'O', 'ï¿½' => 'O', 'ï¿½' => 'U', 'ï¿½' => 'U',
        'ï¿½' => 'U', 'ï¿½' => 'U', 'ï¿½' => 'Y', 'ï¿½'  => 'B', 'ï¿½' => 'Ss', 'ï¿½' => 'a', 'ï¿½' => 'a', 'ï¿½' => 'a', 'ï¿½' => 'a', 'ï¿½' => 'a',
        'ï¿½' => 'a', 'ï¿½' => 'a', 'ï¿½' => 'c', 'ï¿½'  => 'e', 'ï¿½' => 'e', 'ï¿½'  => 'e', 'ï¿½' => 'e', 'ï¿½' => 'i', 'ï¿½' => 'i', 'ï¿½' => 'i',
        'ï¿½' => 'i', 'ï¿½' => 'o', 'ï¿½' => 'n', 'ï¿½'  => 'o', 'ï¿½' => 'o', 'ï¿½'  => 'o', 'ï¿½' => 'o', 'ï¿½' => 'o', 'ï¿½' => 'o', 'ï¿½' => 'u',
        'ï¿½' => 'u', 'ï¿½' => 'u', 'ï¿½' => 'u', 'ï¿½'  => 'y', 'ï¿½' => 'y', 'ï¿½'  => 'b', 'ï¿½' => 'y', 'ï¿½' => 'f',
    );
}

function urlParsing($string)
{
    $arrDash = array("--", "---", "----", "-----");
    $string  = strtolower(trim($string));
    $string  = strtr($string, normalizeChars());
    $string  = preg_replace('/[^a-zA-Z0-9 -.]/', '', $string);
    $string  = str_replace(" ", "-", $string);
    $string  = str_replace("&", "", $string);
    $string  = str_replace(array("'", "\"", "&quot;"), "", $string);
    $string  = str_replace($arrDash, "-", $string);
    return str_replace($arrDash, "-", $string);
}

