<?php
include 'fns/firewall/load.php';
include_once 'fns/sql/load.php';
include 'fns/variables/load.php';

$load_chat_page = false;

if (isset($slug_exists) && $slug_exists) {
    $load_chat_page = true;

    if (isset(Registry::load('config')->load_page) && !empty(Registry::load('config')->load_page)) {
        $load_chat_page = false;
    }
}

if (Registry::load('settings')->landing_page !== 'enable') {
    $load_chat_page = true;
}

if (Registry::load('current_user')->logged_in || $load_chat_page) {
    include 'fns/fetch/load.php';
    include 'layouts/chat_page/layout.php';
} else {
    include 'layouts/landing_page/layout.php';
}
?>