<?php

$check_user_id = explode('[', $data["user_id"]);
$load_conversation_id = 0;

if (isset($check_user_id[1]) && isset($check_user_id[0]) && $check_user_id[0] === 'all') {
   $load_conversation_id = filter_var($check_user_id[1], FILTER_SANITIZE_NUMBER_INT);
}

if ($data["user_id"] !== 'all' && empty($load_conversation_id)) {
    $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
}

if (!isset($data["message_id_greater_than"])) {
    $data["message_id_greater_than"] = 0;
}

if (!empty($data["user_id"])) {

    include_once('fns/load/load.php');

    $load = array();
    $load["load"] = 'private_chat_messages';
    $load["user_id"] = $data["user_id"];
    $load["return"] = true;

    if (isset($data["message_id_greater_than"])) {

        $data["message_id_greater_than"] = filter_var($data["message_id_greater_than"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data["message_id_greater_than"])) {
            $load["message_id_greater_than"] = $data["message_id_greater_than"];
        }
    }

    $result['private_chat_messages'] = load($load);

    if (isset($result['private_chat_messages']['messages'])) {
        if (count($result['private_chat_messages']['messages']) > 0) {

            if (isset(Registry::load('settings')->play_notification_sound->on_new_message)) {
                if (!isset($result['private_chat_messages']['messages'][0]->own_message) || !$result['private_chat_messages']['messages'][0]->own_message) {
                    $result['play_sound_notification'] = true;
                }
            }

            $escape = true;
        }
    }
}