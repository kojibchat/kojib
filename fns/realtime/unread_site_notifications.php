<?php

$data["unread_site_notifications"] = filter_var($data["unread_site_notifications"], FILTER_SANITIZE_NUMBER_INT);

if (empty($data["unread_site_notifications"])) {
    $data["unread_site_notifications"] = 0;
}

$columns = $join = $where = null;

$where["site_notifications.user_id"] = Registry::load('current_user')->id;
$where["site_notifications.read_status"] = 0;

$unread_site_notifications = DB::connect()->count('site_notifications', $where);

if ((int)$unread_site_notifications !== (int)$data["unread_site_notifications"]) {
    $result['unread_site_notifications'] = $unread_site_notifications;

    if (isset(Registry::load('settings')->play_notification_sound->on_new_site_notification)) {
        if ($unread_site_notifications > $data["unread_site_notifications"]) {
            $result['play_sound_notification'] = true;
        }
    }

    $escape = true;
}