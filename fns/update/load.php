<?php

function update($data, $private_data = null)
{
    $result = array();
    $force_request = $api_request = false;

    if (isset($data["api_secret_key"]) && !empty($data["api_secret_key"])) {
        if (isset(Registry::load('settings')->api_secret_key) && !empty(Registry::load('settings')->api_secret_key)) {
            if ($data["api_secret_key"] === Registry::load('settings')->api_secret_key) {
                $force_request = true;
                $api_request=true;
            }
        }
    }

    if (isset($data["update"])) {
        $data["update"] = preg_replace("/[^a-zA-Z0-9_]+/", "", $data["update"]);
    }

    if (isset($private_data["force_request"]) && $private_data["force_request"]) {
        $force_request = true;
    }

    if (isset($data["update"]) && !empty($data["update"])) {
        $loadfnfile = 'fns/update/'.$data["update"].'.php';
        if (file_exists($loadfnfile)) {
            include($loadfnfile);
        }
    }

    if ($api_request) {
        unset($result['reload']);
        unset($result['identifier_type']);
        unset($result['reload_aside']);

        if (isset($data["update"]) && $data["update"]!=='settings') {
            unset($result['todo']);
        }
    }

    if (isset($data["return"]) && $data["return"]) {
        return $result;
    } else {
        $result = json_encode($result);
        echo $result;
    }
}
