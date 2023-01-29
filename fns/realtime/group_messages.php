<?php

if ($data["group_id"] !== 'all') {
    $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
}

if (!isset($data["message_id_greater_than"])) {
    $data["message_id_greater_than"] = 0;
}

if (!empty($data["group_id"])) {

    include_once('fns/load/load.php');

    $load = array();
    $load["load"] = 'group_messages';
    $load["group_id"] = $data["group_id"];
    $load["return"] = true;

    if (isset($data["message_id_greater_than"])) {

        $data["message_id_greater_than"] = filter_var($data["message_id_greater_than"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data["message_id_greater_than"])) {
            $load["message_id_greater_than"] = $data["message_id_greater_than"];
        }
    }

    $result['group_messages'] = load($load);

    if (isset($result['group_messages']['messages'])) {
        if (count($result['group_messages']['messages']) > 0) {

            if (isset(Registry::load('settings')->play_notification_sound->on_new_message)) {
                if (!isset($result['group_messages']['messages'][0]->own_message) || !$result['group_messages']['messages'][0]->own_message) {
                    $result['play_sound_notification'] = true;
                }
            }

            $escape = true;
        }
    }
}