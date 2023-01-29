<?php

function load($data, $private_data = null) {

    $output = array();

    if (!Registry::load('current_user')->logged_in || !isset($data["filter"])) {
        $data["filter"] = 0;
    }

    if (!isset($data["offset"]) || empty($data["offset"])) {
        $data["offset"] = 0;
    }

    if (!isset($data["sortby"]) || empty($data["sortby"])) {
        $data["sortby"] = 0;
    }

    if (!isset($data["search"]) || empty($data["search"])) {
        $data["search"] = 0;
    } else {
        $data["search"] = str_replace('_', '\_', $data["search"]);
    }

    if (isset($data["load"])) {
        $data["load"] = preg_replace("/[^a-zA-Z0-9_]+/", "", $data["load"]);
    }

    if (isset($data["load"]) && !empty($data["load"])) {
        $loadfnfile = 'fns/load/'.$data["load"].'.php';
        if (file_exists($loadfnfile)) {
            include($loadfnfile);
        }
    }

    if (isset($data["return"]) && $data["return"]) {
        return $output;
    } else if (!isset($data["skip_output"])) {
        $output = json_encode($output);
        echo $output;
    }

}

?>