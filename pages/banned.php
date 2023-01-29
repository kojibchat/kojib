<?php

include 'fns/firewall/load.php';
include_once 'fns/sql/load.php';
include 'fns/variables/load.php';

if (Registry::load('current_user')->banned) {
    include 'layouts/banned_page/layout.php';
} else {
    header("Location: ".Registry::load('config')->site_url);
    exit;
}
?>