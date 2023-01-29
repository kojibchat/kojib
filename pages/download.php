<?php
include 'fns/firewall/load.php';
include 'fns/sql/load.php';
include 'fns/variables/load.php';
include 'fns/download/load.php';

$to_download = explode('/', get_url(['path' => true]));

if (isset($to_download[3]) && !empty($to_download[3])) {

    $download = array();
    $identifier = $to_download[2];
    $download['download'] = $to_download[1];
    $download[$identifier] = $to_download[3];

    if (isset($to_download[5]) && !empty($to_download[5])) {
        $identifier = $to_download[4];
        $download[$identifier] = $to_download[5];
    }

    if (isset($to_download[7])) {
        $identifier = $to_download[6];
        $download[$identifier] = $to_download[7];
    }

    download($download);

} else {
    echo Registry::load('strings')->file_expired;
}
?>