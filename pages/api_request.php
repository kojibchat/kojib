<?php

session_write_close();
ignore_user_abort(false);

include 'fns/firewall/load.php';
include_once 'fns/sql/load.php';
include 'fns/variables/load.php';

$skip_api_request = false;
$api_request_session = false;

if (Registry::load('current_user')->logged_in) {
    if (isset($_REQUEST['update']) && $_REQUEST['update'] === 'settings') {
        if (isset($_REQUEST["api_secret_key"])) {
            $skip_api_request = true;
        }
    }
}

if (isset($_REQUEST["api_secret_key"]) && !empty($_REQUEST["api_secret_key"]) && !$skip_api_request) {
    if (isset(Registry::load('settings')->api_secret_key) && !empty(Registry::load('settings')->api_secret_key)) {
        if ($_REQUEST["api_secret_key"] === Registry::load('settings')->api_secret_key) {
            $data = get_data('request');
            $api_request_session = true;
        } else {
            $result = array();
            $result['success'] = false;
            $result['error_message'] = 'Invalid API Secret Key';
            $result['error_key'] = 'invalid_api_secret_key';
            $result = json_encode($result);
            echo $result;
            return;
        }
    } else {
        return false;
    }
} else {
    $data = get_data();
}

if (Registry::load('current_user')->logged_in) {
    if (Registry::load('config')->csrf_token && !$api_request_session) {
        if (!empty(Registry::load('current_user')->csrf_token)) {
            if (!isset($data["csrf_token"]) || $data["csrf_token"] !== Registry::load('current_user')->csrf_token) {
                $result = array();
                $result['success'] = false;
                $result['error_message'] = 'Invalid CSRF Token';
                $result['error_key'] = 'invalid_csrf_token';
                $result = json_encode($result);
                echo $result;
                return;
            }
        }
    }
}

if (isset($data["load"])) {
    include 'fns/load/load.php';
    load($data);
} else if (isset($data["form"])) {
    include 'fns/form/load.php';
    form($data);
} else if (isset($data["add"])) {
    include 'fns/add/load.php';
    add($data);
} else if (isset($data["update"])) {
    include 'fns/update/load.php';
    update($data);
} else if (isset($data["download"])) {
    include 'fns/download/load.php';
    download($data);
} else if (isset($data["upload"])) {
    include 'fns/upload/load.php';
    upload($data);
} else if (isset($data["remove"])) {
    include 'fns/remove/load.php';
    remove($data);
} else if (isset($data["popup"])) {
    include 'fns/popup/load.php';
    popup($data);
} else if (isset($data["realtime"])) {
    include 'fns/realtime/load.php';
    realtime($data);
}

?>