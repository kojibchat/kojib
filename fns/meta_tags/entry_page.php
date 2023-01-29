<?php

$find_slug = urldecode(str_replace('/', '', $_GET['redirect']));
$find_slug = htmlspecialchars($find_slug);

if (!empty($find_slug)) {
    $slug_exists = false;

    $query = 'SELECT ';
    $query .= ' (SELECT <user_id> FROM <site_users> WHERE <username> = :findslug) AS user_id, ';
    $query .= '(SELECT <page_id> FROM <custom_pages> WHERE <slug> = :findslug AND <disabled> = 0) AS page_id, ';
    $query .= '(SELECT <group_id> FROM <groups> WHERE <slug> = :findslug) AS group_id;';
    $get_slug_info = DB::connect()->query($query, ['findslug' => $find_slug])->fetchAll();


    if (isset($get_slug_info[0]) && isset($get_slug_info[0]['user_id'])) {
        $load_conversation = urldecode(Registry::load('config')->url_path);
        $load_conversation = preg_split('/\//', $load_conversation);
        if (isset($load_conversation[1]) && $load_conversation[1] === 'chat') {
            Registry::load('config')->load_private_conversation = $get_slug_info[0]['user_id'];
        } else {
            Registry::load('config')->load_user_profile = $get_slug_info[0]['user_id'];
        }
    } elseif (isset($get_slug_info[0]) && isset($get_slug_info[0]['group_id'])) {
        Registry::load('config')->load_group_conversation = $get_slug_info[0]['group_id'];
    } elseif (isset($get_slug_info[0]) && isset($get_slug_info[0]['page_id'])) {
        Registry::load('config')->load_page = $get_slug_info[0]['page_id'];
    }
}

?>