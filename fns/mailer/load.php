<?php

function mailer($todo, $data) {

    $result = array();

    if (isset($todo)) {
        $todo = preg_replace("/[^a-zA-Z0-9_]+/", "", $todo);
    }

    if (isset($todo) && !empty($todo)) {
        $load_fn_file = 'fns/mailer/'.$todo.'.php';
        if (file_exists($load_fn_file)) {
            include($load_fn_file);
        }
    }

    if (isset($data["print"]) && $data["print"]) {
        $result = json_encode($result);
        echo $result;
    } else {
        return $result;
    }
}