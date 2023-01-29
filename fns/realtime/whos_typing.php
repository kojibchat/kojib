<?php

$check_typing_status = false;
$private_conversation_id = 0;

if (isset($data["user_id"])) {

    $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data["user_id"])) {

        $columns = $where = null;
        $columns = [
            'private_conversations.private_conversation_id'
        ];

        $where["OR"]["AND #first_query"] = [
            "private_conversations.initiator_user_id" => $data["user_id"],
            "private_conversations.recipient_user_id" => Registry::load('current_user')->id,
        ];
        $where["OR"]["AND #second_query"] = [
            "private_conversations.initiator_user_id" => Registry::load('current_user')->id,
            "private_conversations.recipient_user_id" => $data["user_id"],
        ];

        $where["LIMIT"] = 1;
        $conversation_id = DB::connect()->select('private_conversations', $columns, $where);

        if (isset($conversation_id[0])) {
            $private_conversation_id = $conversation_id[0]['private_conversation_id'];
            $check_typing_status = true;
        }
    }
}

if (isset($data["group_id"])) {
    $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
    if (!empty($data["group_id"])) {
        $check_typing_status = true;
    }
}

if ($check_typing_status) {

    $time_from = get_date();
    $time_from = strtotime($time_from);
    $time_from = $time_from - 10;
    $time_from = date("Y-m-d H:i:s", $time_from);

    $data["whos_typing_last_logged_user_id"] = filter_var($data["whos_typing_last_logged_user_id"], FILTER_SANITIZE_NUMBER_INT);

    if (empty($data["whos_typing_last_logged_user_id"])) {
        $data["whos_typing_last_logged_user_id"] = 0;
    }

    $whos_typing_last_logged_user_id = 0;
    $columns = $join = $where = null;

    $columns = ['site_users.display_name', 'site_users.user_id'];

    $join["[>]site_users"] = ["typing_status.user_id" => "user_id"];

    if (!empty($private_conversation_id)) {
        $where["typing_status.private_conversation_id"] = $private_conversation_id;
    } else if (isset($data["group_id"]) && !empty($data["group_id"])) {
        $where["typing_status.group_id"] = $data["group_id"];
    }

    $where["typing_status.user_id[!]"] = Registry::load('current_user')->id;

    $where["typing_status.updated_on[>]"] = $time_from;
    $where["ORDER"] = ["typing_status.updated_on" => "DESC"];
    $where["LIMIT"] = 10;

    $users = DB::connect()->select('typing_status', $join, $columns, $where);

    $users_typing = array();

    foreach ($users as $user) {

        $users_typing[] = $user['display_name'];

        if (empty($whos_typing_last_logged_user_id)) {
            $whos_typing_last_logged_user_id = $user['user_id'];
        }
    }

    if ((int)$whos_typing_last_logged_user_id !== (int)$data["whos_typing_last_logged_user_id"]) {
        $result['users_typing'] = array();
        $result['users_typing']['users'] = $users_typing;
        $result['users_typing']['last_inserted_user_id'] = $whos_typing_last_logged_user_id;

        if (isset($data["group_id"])) {
            $result['users_typing']['group_id'] = $data["group_id"];
        } else if (isset($data["user_id"])) {
            $result['users_typing']['user_id'] = $data["user_id"];
        }

        $escape = true;
    }
}