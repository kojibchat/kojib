<?php

function meta_tags() {

    $meta_tags = array();
    $meta_tags['title'] = Registry::load('settings')->site_name.' - '.Registry::load('settings')->site_slogan;

    if (!empty(Registry::load('settings')->meta_title)) {
        $meta_tags['title'] = Registry::load('settings')->meta_title.' - '.Registry::load('settings')->site_name;
    }

    $meta_tags['default_title'] = $meta_tags['title'];

    $meta_tags['description'] = Registry::load('settings')->site_description;
    $meta_tags['url'] = Registry::load('config')->site_url;
    $meta_tags['social_share_image'] = Registry::load('settings')->social_share_image;
    $user_id = Registry::load('current_user')->id;


    if (Registry::load('config')->current_page == 'entry' && isset($_GET['redirect'])) {
        include('fns/meta_tags/entry_page.php');
    }

    if (isset(Registry::load('config')->load_user_profile) && !empty(Registry::load('config')->load_user_profile)) {
        include('fns/meta_tags/site_user.php');
    } else if (isset(Registry::load('config')->load_private_conversation) && !empty(Registry::load('config')->load_private_conversation)) {
        include('fns/meta_tags/site_user.php');
    } else if (isset(Registry::load('config')->load_group_conversation) && !empty(Registry::load('config')->load_group_conversation)) {
        include('fns/meta_tags/group.php');
    } else if (isset(Registry::load('config')->load_page) && !empty(Registry::load('config')->load_page)) {
        include('fns/meta_tags/custom_page.php');
    }

    return $meta_tags;

}

?>