<?php
$current_user_info = new stdClass;
$current_user_info->id = 0;
$current_user_info->site_role = 1;
$current_user_info->site_role_attribute = null;
$current_user_info->logged_in = false;
$current_user_info->email_address = false;
$current_user_info->color_scheme = 0;
$current_user_info->country_code = 0;
$current_user_info->settings_exists = false;
$current_user_info->csrf_token = null;
$current_user_info->allowed_file_types = '';
$current_user_banned = false;
$current_time_stamp = get_date();
$geoip_service = true;

$firewall = new Firewall();
$current_user_ip_address = $firewall->getUserIP();
$current_user_agent = $firewall->getUserAgent();

$ip_blacklist = array();
include('assets/cache/ip_blacklist.cache');
$firewall->blockIP($ip_blacklist);

try {
    $firewall->run();
} catch(Exception $e) {

    $current_user_banned = true;
    $_COOKIE["login_session_id"] = $_COOKIE["session_time_stamp"] = $_COOKIE["access_code"] = null;

    if (Registry::load('config')->current_page !== 'banned') {
        header("Location: ".Registry::load('config')->site_url."banned");
        exit;
    }
}

$login_session_id = $session_time_stamp = $access_code = null;

if (isset($_COOKIE["login_session_id"]) && isset($_COOKIE["session_time_stamp"]) && isset($_COOKIE["access_code"])) {
    $login_session_id = $_COOKIE["login_session_id"];
    $session_time_stamp = $_COOKIE["session_time_stamp"];
    $access_code = $_COOKIE["access_code"];
}

$join = [
    "[>]site_users_settings(settings)" => ["login_sessions.user_id" => "user_id"],
    "[>]site_users" => ["login_sessions.user_id" => "user_id"],
    "[>]site_roles" => ["site_users.site_role_id" => "site_role_id"]
];

$join["[>]custom_fields_values"] = [
    "login_sessions.user_id" => "user_id",
    "AND" => ["field_id" => '6']
];

$columns = [
    'login_sessions.user_id(id)', 'site_users.display_name(name)', 'site_users.email_address', 'settings.time_zone',
    'site_users.username', 'site_users.site_role_id(site_role)', 'settings.language_id', 'settings.offline_mode', 'settings.user_setting_id',
    'settings.notification_tone', 'settings.deactivated', 'settings.color_scheme', 'custom_fields_values.field_value(country_code)',
    'custom_fields_values.field_value_id(country_code_field_value_id)', 'site_users.online_status', 'login_sessions.last_access(last_access)',
    'site_users.geo_latitude', 'site_users.geo_longitude', 'site_users.last_seen_on', 'login_sessions.log_device', 'login_sessions.login_from_user_id',
    'site_roles.site_role_attribute', 'login_sessions.csrf_token', 'login_sessions.csrf_token_generated_on', 'login_sessions.login_session_id'
];
$where = [
    "login_sessions.login_session_id" => $login_session_id,
    "login_sessions.access_code" => $access_code,
    "login_sessions.time_stamp" => $session_time_stamp,
    "login_sessions.status" => 1,
    "ORDER" => ["login_sessions.login_session_id" => "DESC"],
    'LIMIT' => 1
];
$get_current_user_info = DB::connect()->select('login_sessions', $join, $columns, $where);

if (isset($get_current_user_info[0])) {
    if (isset($get_current_user_info[0]['id']) && !empty($get_current_user_info[0]['id'])) {
        $current_user_info = json_decode(json_encode($get_current_user_info[0]));
        $current_user_info->logged_in = true;

        $time_diff_in_seconds = strtotime($current_time_stamp) - strtotime($current_user_info->last_access);
        $device_log_time_diff_in_seconds = 0;

        if (isset($current_user_info->log_device) && !empty($current_user_info->log_device)) {
            if ($time_diff_in_seconds > 290) {

                $last_log_condition = array();
                $last_log_condition['site_users_device_logs.login_session_id'] = $login_session_id;
                $last_log_condition['site_users_device_logs.ip_address'] = $current_user_ip_address;
                $last_log_condition['ORDER'] = ["site_users_device_logs.access_log_id" => "DESC"];
                $last_log_condition['LIMIT'] = 1;

                $last_device_log = DB::connect()->select('site_users_device_logs', ['created_on'], $last_log_condition);

                if (isset($last_device_log[0])) {
                    $device_log_time_diff_in_seconds = strtotime($current_time_stamp) - strtotime($last_device_log[0]['created_on']);
                }

                if (!isset($last_device_log[0]) || $device_log_time_diff_in_seconds > 3600) {
                    $device_log['login_session_id'] = $login_session_id;
                    $device_log['ip_address'] = $current_user_ip_address;
                    $device_log['user_agent'] = $current_user_agent;
                    $device_log['user_id'] = $current_user_info->id;
                    $device_log['created_on'] = $current_time_stamp;

                    DB::connect()->insert('site_users_device_logs', $device_log);
                }

                DB::connect()->update('login_sessions', ['last_access' => $current_time_stamp], ['login_session_id' => $login_session_id]);
            }
        }

        if (isset($current_user_info->geo_latitude) && $current_user_info->geo_latitude == '-75.25097300') {
            $current_user_info->geo_latitude = null;
        }

        if (isset($current_user_info->geo_latitude) && $current_user_info->geo_latitude == '0.00000000') {
            $current_user_info->geo_latitude = null;
        }

        if (isset($current_user_info->geo_longitude) && $current_user_info->geo_longitude == '-0.07138900') {
            $current_user_info->geo_longitude = null;
        }

        if ($time_diff_in_seconds > 180) {
            DB::connect()->update('site_users', ['last_seen_on' => $current_time_stamp], ['user_id' => $current_user_info->id]);
        }

        if ($geoip_service) {
            if (empty($current_user_info->time_zone) || empty($current_user_info->country_code) || empty($current_user_info->geo_latitude)) {

                if (empty($current_user_info->time_zone) || empty($current_user_info->country_code) || empty($current_user_info->geo_latitude)) {
                    include('fns/firewall/geo_plugin.php');
                    $geoplugin = new geoPlugin();
                    $geoplugin->locate($current_user_ip_address);
                }

                if (empty($current_user_info->time_zone)) {
                    $data = array();
                    $data['user_id'] = $get_current_user_info[0]['id'];

                    if (!isset($geoplugin->timezone) || empty($geoplugin->timezone)) {
                        $geoplugin->timezone = 'default';
                    }

                    $data['time_zone'] = $geoplugin->timezone;
                    $current_user_info->time_zone = $geoplugin->timezone;

                    $data['updated_on'] = $current_time_stamp;

                    if (empty($current_user_info->user_setting_id)) {
                        DB::connect()->insert('site_users_settings', $data);
                    } else {
                        DB::connect()->update('site_users_settings', $data, ['user_id' => $data['user_id']]);
                    }
                }

                $add_geo_location = false;


                if ($add_geo_location && empty($current_user_info->geo_latitude)) {
                    $data = array();
                    $data['user_id'] = $get_current_user_info[0]['id'];

                    if (isset($geoplugin->latitude) && !empty($geoplugin->latitude)) {
                        $current_user_info->geo_latitude = $data['geo_latitude'] = $geoplugin->latitude;
                    }

                    if (isset($geoplugin->longitude) && !empty($geoplugin->longitude)) {
                        $current_user_info->geo_longitude = $data['geo_longitude'] = $geoplugin->longitude;
                    }

                    $data['updated_on'] = $current_time_stamp;
                    DB::connect()->update('site_users', $data, ['user_id' => $data['user_id']]);
                }

                if (empty($current_user_info->country_code)) {

                    if (!isset($geoplugin->countryCode) || empty($geoplugin->countryCode)) {
                        $geoplugin->countryCode = 'US';
                    }

                    $data = array();
                    $data['user_id'] = $get_current_user_info[0]['id'];
                    $data['field_value'] = $geoplugin->countryCode;
                    $data['field_id'] = 6;
                    $data['updated_on'] = $current_time_stamp;

                    if (empty($current_user_info->country_code_field_value_id)) {

                        $sql_query = 'INSERT INTO <custom_fields_values> (<user_id>, <field_value>, <field_id>, <updated_on>) ';
                        $sql_query .= 'SELECT * FROM (SELECT :user_id, :field_value, :field_id, :updated_on) AS country_field WHERE NOT EXISTS ';
                        $sql_query .= '(SELECT <field_value_id> FROM <custom_fields_values> WHERE <user_id> = :user_id AND <field_id> = :field_id) LIMIT 1;';

                        DB::connect()->query($sql_query, $data);

                    } else {
                        DB::connect()->update('custom_fields_values', $data, ['field_value_id' => $current_user_info->country_code_field_value_id]);
                    }
                }
            }
        }
    }
}

$current_user_info->login_session_id = $login_session_id;
$current_user_info->ip_address = $current_user_ip_address;
$current_user_info->user_agent = $current_user_agent;
$current_user_info->banned = $current_user_banned;
$current_user_info->time_stamp = $current_time_stamp;
$current_user_info->allowed_file_types = '';