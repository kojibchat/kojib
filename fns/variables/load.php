<?php
$settings = extract_json(['file' => 'assets/cache/settings.cache']);
$settings->social_share_image = Registry::load('config')->site_url.'assets/files/defaults/social_share_image.jpg';
$settings->reservedslugs = array("group");

include 'fns/variables/get_current_user_info.php';
$current_user = $current_user_info;

if (!isset($current_user->language_id) || empty($current_user->language_id)) {
    $current_user->language_id = $settings->default_language;
}

if (!$current_user_info->logged_in) {
    if (isset($_COOKIE["current_language_id"]) && !empty($_COOKIE["current_language_id"])) {
        $_COOKIE["current_language_id"] = filter_var($_COOKIE["current_language_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($_COOKIE["current_language_id"])) {
            $current_user->language_id = $_COOKIE["current_language_id"];
        }
    }
}

if (isset($current_user->time_zone) && $current_user->time_zone === 'default') {
    $current_user->time_zone = '';
}

if (isset($current_user->notification_tone) && !empty($current_user->notification_tone)) {
    $settings->notification_tone = $current_user->notification_tone;
}

if (!isset($current_user->time_zone) || empty($current_user->time_zone)) {
    if (!isset($settings->default_timezone) || empty($settings->default_timezone)) {
        $settings->default_timezone = 'Australia/Sydney';
    }
    $current_user->time_zone = $settings->default_timezone;
}

$current_user->language = $current_user->language_id;
$settings->notification_tone = Registry::load('config')->site_url.$settings->notification_tone;

$strings = extract_json(['file' => 'assets/cache/languages/language-'.$current_user->language.'.cache']);

$apperance = new stdClass();
$apperance->body_class = 'light_mode';

if ($settings->color_scheme == 'dark_mode') {
    $apperance->body_class = 'dark_mode';
}

if (isset($current_user->color_scheme) && !empty($current_user->color_scheme)) {
    if ($current_user->color_scheme == 'dark_mode') {
        $apperance->body_class = 'dark_mode';
    } else if ($current_user->color_scheme === 'default') {
        $current_user->color_scheme = $apperance->body_class;
    } else {
        $apperance->body_class = 'light_mode';
    }

} else {
    $current_user->color_scheme = $apperance->body_class;
}

if (!empty($strings->text_direction)) {
    $apperance->body_class = $apperance->body_class.' '.$strings->text_direction.'_language';
}

$permissions = extract_json(['file' => 'assets/cache/site_roles.cache', 'extract' => $current_user->site_role]);

Registry::add('settings', $settings);
Registry::add('current_user', $current_user);
Registry::add('appearance', $apperance);
Registry::add('permissions', $permissions);
Registry::add('strings', $strings);