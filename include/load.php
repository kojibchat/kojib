<?php

include 'fns/registry/load.php';
include 'config.php';

Registry::__init();
Registry::add('config', $config);

date_default_timezone_set($config->timezone);

include 'fns/global/load.php';

$config = (object) array_merge((array) $config, (array) get_url());

Registry::add('config', $config);

if (Registry::load('config')->developer_mode) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}

if (Registry::load('config')->current_page === 'index') {
    redirect();
} elseif (empty(Registry::load('config')->current_page)) {
    Registry::load('config')->current_page = 'index';
}

$skip_sql_check=false;

if (!Registry::load('config')->developer_mode) {
    if (file_exists('pages/installer.php')) {
        $skip_sql_check=true;
        $disallow_pages=['index','entry','404'];
        if (empty(Registry::load('config')->current_page) || in_array(Registry::load('config')->current_page, $disallow_pages)) {
            Registry::load('config')->current_page = 'installer';
        }
    }
}

$page = 'pages/'.Registry::load('config')->current_page.'.php';
$developer_mode_pages = 'developer_mode/pages/'.Registry::load('config')->current_page.'.php';

    if (Registry::load('config')->scheme !== 'https' && Registry::load('config')->scheme !== 'https://') {
        Registry::load('config')->initial_samesite_cookies = Registry::load('config')->samesite_cookies;
        Registry::load('config')->samesite_cookies="default";

        if (Registry::load('config')->force_https) {
            exit(header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301));
        }
    }

if (file_exists($page)) {
    include $page;
} elseif (Registry::load('config')->developer_mode && file_exists($developer_mode_pages)) {
    include $developer_mode_pages;
} elseif (!$skip_sql_check) {
    include_once 'fns/sql/load.php';

    $find_slug = urldecode(Registry::load('config')->current_page);
    $slug_exists=false;
    $group_secret_code='';
    $domain_url_path = urldecode(Registry::load('config')->url_path);
    $domain_url_path = preg_split('/\//', $domain_url_path);

    if ($find_slug==='group') {
        if (isset($domain_url_path[1])) {
            if (isset($domain_url_path[2])) {
                $group_secret_code=$domain_url_path[2];
            }
            $group_id=$domain_url_path[1];
            $group_id=filter_var($group_id, FILTER_SANITIZE_NUMBER_INT);
            if (!empty($group_id)) {
                $query = 'SELECT ';
                $query .= '(SELECT <group_id> FROM <groups> WHERE <group_id> = :find_group_id) AS group_id;';
                $get_slug_info = DB::connect()->query($query, ['find_group_id' => $group_id])->fetchAll();
            }
        }
    } else {
        if (isset($domain_url_path[1])) {
            $group_secret_code=$domain_url_path[1];
        }

        $query = 'SELECT ';
        $query .= ' (SELECT <user_id> FROM <site_users> WHERE <username> = :findslug) AS user_id, ';
        $query .= '(SELECT <page_id> FROM <custom_pages> WHERE <slug> = :findslug AND <disabled> = 0) AS page_id, ';
        $query .= '(SELECT <group_id> FROM <groups> WHERE <slug> = :findslug) AS group_id;';
        $get_slug_info = DB::connect()->query($query, ['findslug' => $find_slug])->fetchAll();
    }


    if (isset($get_slug_info[0]) && isset($get_slug_info[0]['user_id'])) {
        $slug_exists=true;
        $load_conversation = urldecode(Registry::load('config')->url_path);
        $load_conversation = preg_split('/\//', $load_conversation);
        if (isset($load_conversation[1]) && $load_conversation[1]==='chat') {
            Registry::load('config')->load_private_conversation = $get_slug_info[0]['user_id'];
        } else {
            Registry::load('config')->load_user_profile = $get_slug_info[0]['user_id'];
        }
    } elseif (isset($get_slug_info[0]) && isset($get_slug_info[0]['group_id'])) {
        $slug_exists=true;
        $cookie_time = time() + (86400 * 2);
        Registry::load('config')->load_group_conversation = $get_slug_info[0]['group_id'];

        if (!empty($group_secret_code)) {
            add_cookie('current_group_secret_code', $group_secret_code, $cookie_time);
            $_COOKIE['current_group_secret_code'] = $group_secret_code;
        }
    } elseif (isset($get_slug_info[0]) && isset($get_slug_info[0]['page_id'])) {
        $slug_exists=true;
        Registry::load('config')->load_page = $get_slug_info[0]['page_id'];
    }

    if ($slug_exists) {
        include("pages/index.php");
        exit;
    } else {
        header('HTTP/1.1 404 Not Found');
        include("pages/404.php");
        exit;
    }
}
