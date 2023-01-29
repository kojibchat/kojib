<?php

function push_notification($data) {

    $result = array();

    if (!empty(Registry::load('settings')->push_notifications) && Registry::load('settings')->push_notifications !== 'disable') {

        $push_notification_service = Registry::load('settings')->push_notifications;

        if (isset($data['user_id']) && !empty($data['user_id'])) {

            $columns = [
                'push_subscriptions.device_token'
            ];

            $join["[>]site_users"] = ["push_subscriptions.user_id" => "user_id"];
            $join["[>]site_roles"] = ["site_users.site_role_id" => "site_role_id"];

            $where = [
                "push_subscriptions.push_notification_service" => $push_notification_service,
                "push_subscriptions.user_id" => $data['user_id'],
                "site_roles.site_role_attribute[!]" => "banned_users",
                "ORDER" => ["push_subscriptions.push_subscriber_id" => "DESC"],
                "LIMIT" => 5,
            ];

            $device_tokens = DB::connect()->select('push_subscriptions', $join, $columns, $where);

            $data['device_tokens'] = array_map('current', $device_tokens);

        }

        if (isset($data['device_tokens']) && is_array($data['device_tokens']) && !empty($data['device_tokens'])) {

            if (isset($data['message']) && isset($data['title']) && !empty($data['message']) && !empty($data['title'])) {

                if (!isset($data['image']) || empty($data['image'])) {
                    $data['image'] = Registry::load('config')->site_url.'assets/files/defaults/push_notification_icon.png';
                }

                if (!isset($data['url']) || empty($data['url'])) {
                    $data['url'] = Registry::load('config')->site_url;
                }

                if (!isset($data['language_code']) || empty($data['language_code'])) {
                    $data['language_code'] = Registry::load('strings')->iso_code;
                }

                if (isset($push_notification_service) && !empty($push_notification_service)) {
                    $load_fn_file = 'fns/push_notification/'.$push_notification_service.'.php';
                    if (file_exists($load_fn_file)) {
                        include($load_fn_file);
                    }
                }
            }

        }
    }

    if (isset($data["return"]) && $data["return"]) {
        return $result;
    }
}