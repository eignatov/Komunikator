<?php

/*
*  | RUS | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

*    «Komunikator» – Web-интерфейс для настройки и управления программной IP-АТС «YATE»
*    Copyright (C) 2012-2013, ООО «Телефонные системы»

*    ЭТОТ ФАЙЛ является частью проекта «Komunikator»

*    Сайт проекта «Komunikator»: http://4yate.ru/
*    Служба технической поддержки проекта «Komunikator»: E-mail: support@4yate.ru

*    В проекте «Komunikator» используются:
*      исходные коды проекта «YATE», http://yate.null.ro/pmwiki/
*      исходные коды проекта «FREESENTRAL», http://www.freesentral.com/
*      библиотеки проекта «Sencha Ext JS», http://www.sencha.com/products/extjs

*    Web-приложение «Komunikator» является свободным и открытым программным обеспечением. Тем самым
*  давая пользователю право на распространение и (или) модификацию данного Web-приложения (а также
*  и иные права) согласно условиям GNU General Public License, опубликованной
*  Free Software Foundation, версии 3.

*    В случае отсутствия файла «License» (идущего вместе с исходными кодами программного обеспечения)
*  описывающего условия GNU General Public License версии 3, можно посетить официальный сайт
*  http://www.gnu.org/licenses/ , где опубликованы условия GNU General Public License
*  различных версий (в том числе и версии 3).

*  | ENG | - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 

*    "Komunikator" is a web interface for IP-PBX "YATE" configuration and management
*    Copyright (C) 2012-2013, "Telephonnyie sistemy" Ltd.

*    THIS FILE is an integral part of the project "Komunikator"

*    "Komunikator" project site: http://4yate.ru/
*    "Komunikator" technical support e-mail: support@4yate.ru

*    The project "Komunikator" are used:
*      the source code of "YATE" project, http://yate.null.ro/pmwiki/
*      the source code of "FREESENTRAL" project, http://www.freesentral.com/
*      "Sencha Ext JS" project libraries, http://www.sencha.com/products/extjs

*    "Komunikator" web application is a free/libre and open-source software. Therefore it grants user rights
*  for distribution and (or) modification (including other rights) of this programming solution according
*  to GNU General Public License terms and conditions published by Free Software Foundation in version 3.

*    In case the file "License" that describes GNU General Public License terms and conditions,
*  version 3, is missing (initially goes with software source code), you can visit the official site
*  http://www.gnu.org/licenses/ and find terms specified in appropriate GNU General Public License
*  version (version 3 as well).

*  - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - 
*/

?><?

if (!$_SESSION['user'] && !$_SESSION['extension']) {
    echo (out(array("success" => false, "message" => "User is undefined")));
    exit;
}

$request_id = getparam("request_id");

$tmp = sys_get_temp_dir() . "/" . $request_id;
$data = json_decode(file_get_contents($tmp));

function array2csv(array &$array) {
    if (count($array) == 0) {
        return null;
    }
    ob_start();
    $df = fopen("php://output", 'w');
    //fputcsv($df, array_keys(reset($array)));
    foreach ($array as $row) {
        fputcsv($df, $row, ";");
    }
    fclose($df);
    return ob_get_clean();
}

function translate($data, $lang = 'ru') {
    $file = "js/app/locale/" . $lang . ".js";
    if (!file_exists($file))
        return $data;
    //echo ('!!!!');
    $text = file_get_contents($file);
// удаляем строки начинающиеся с #
    $text = preg_replace('/#.*/', '', $text);
// удаляем строки начинающиеся с //
    $text = preg_replace('#//.*#', '', $text);
// удаляем многострочные комментарии /* */
    $text = preg_replace('#/\*(?:[^*]*(?:\*(?!/))*)*\*/#', '', $text);

    $text = str_replace("\r\n", '', $text);
    $text = str_replace("\n", '', $text);

    $text = preg_replace('/(.*app\.msg\s*=\s*)({.*})(\s*;.*)/', '$2', $text);
    $text = preg_replace('/([{,])([\s\"\']*)([\w\(\)\[\]\,\_]+)([\s\"\']*):\s*\"([^"]*)\"/', '$1"$3":"$5"', $text);
    $text = preg_replace('/([{,])([\s\"\']*)([\w\(\)\[\]\,\_]+)([\s\"\']*):\s*\'([^\']*)\'/', '$1"$3":"$5"', $text);

    $words = json_decode($text, true);
    if ($data && $words)
        foreach ($data as &$row)
            foreach ($row as $key => $el)
                foreach ($words as $word => $value) {
                    if ($word == $el)
                        $row[$key] = $value;
                }
    return $data;
}

function download_send_headers($filename) {
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download  
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
}

download_send_headers("data_export_" . date("Y-m-d_H_i_s") . ".csv");
$data = translate($data, $_SESSION['lang'] ? $_SESSION['lang'] : 'ru');
echo iconv("utf-8", "windows-1251", array2csv($data));
unlink($tmp);
die();
?>