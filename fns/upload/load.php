<?php

function upload($data, $private_data = null) {
    $result = array();
    $force_request = false;

    if (isset($data["upload"])) {
        $data["upload"] = preg_replace("/[^a-zA-Z0-9_]+/", "", $data["upload"]);
    }

    if (isset($private_data["force_request"]) && $private_data["force_request"]) {
        $force_request = true;
    }

    if (isset($data["upload"]) && !empty($data["upload"])) {
        $function_file = 'fns/upload/'.$data["upload"].'.php';

        if (file_exists($function_file)) {
            include($function_file);
        }
    }
    if (isset($data["return"]) && $data["return"]) {
        return $result;
    } else {
        $result = json_encode($result);
        echo $result;
    }
}