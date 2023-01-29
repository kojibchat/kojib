<?php

$current_user_id = Registry::load('current_user')->id;

if (isset($data['send_as_user_id'])) {

    $data['send_as_user_id'] = filter_var($data['send_as_user_id'], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data['send_as_user_id']) && role(['permissions' => ['groups' => 'send_as_another_user']])) {
        $current_user_id = $data['send_as_user_id'];
    }
}

if (isset($data['group_id'])) {
    $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data['group_id'])) {

        $columns = $where = null;
        $columns = ['group_members.group_member_id'];
        $where = [
            "group_id" => $data['group_id'],
            "user_id" => $current_user_id,
            "LIMIT" => 1
        ];
        $group_member = DB::connect()->select('group_members', $columns, $where);

        if (isset($group_member[0])) {

            $check_log = DB::connect()->select("typing_status", ['user_input_log_id'], [
                "group_id" => $data['group_id'],
                "user_id" => $current_user_id,
                "LIMIT" => 1
            ]);

            if (isset($check_log[0])) {
                DB::connect()->update("typing_status", ["updated_on" => Registry::load('current_user')->time_stamp],
                    ["user_input_log_id" => $check_log[0]["user_input_log_id"]]
                );
            } else {
                $insert_data = [
                    "group_id" => $data['group_id'],
                    "user_id" => $current_user_id,
                    "created_on" => Registry::load('current_user')->time_stamp,
                    "updated_on" => Registry::load('current_user')->time_stamp
                ];
                DB::connect()->insert("typing_status", $insert_data);
            }
        }
    }
} else if (isset($data['user_id'])) {
    $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data['user_id'])) {
        $columns = $where = null;
        $columns = [
            'private_conversations.private_conversation_id'
        ];

        $where["OR"]["AND #first_query"] = [
            "private_conversations.initiator_user_id" => $data["user_id"],
            "private_conversations.recipient_user_id" => $current_user_id,
        ];
        $where["OR"]["AND #second_query"] = [
            "private_conversations.initiator_user_id" => $current_user_id,
            "private_conversations.recipient_user_id" => $data["user_id"],
        ];

        $where["LIMIT"] = 1;
        $conversation_id = DB::connect()->select('private_conversations', $columns, $where);

        if (isset($conversation_id[0])) {

            $check_log = DB::connect()->select("typing_status", ['user_input_log_id'], [
                "private_conversation_id" => $conversation_id[0]['private_conversation_id'],
                "user_id" => $current_user_id,
                "LIMIT" => 1
            ]);

            if (isset($check_log[0])) {
                DB::connect()->update("typing_status", ["updated_on" => Registry::load('current_user')->time_stamp],
                    ["user_input_log_id" => $check_log[0]["user_input_log_id"]]
                );
            } else {
                $insert_data = [
                    "private_conversation_id" => $conversation_id[0]['private_conversation_id'],
                    "user_id" => $current_user_id,
                    "created_on" => Registry::load('current_user')->time_stamp,
                    "updated_on" => Registry::load('current_user')->time_stamp
                ];
                DB::connect()->insert("typing_status", $insert_data);
            }
        }
    }
}

$result = array();
$result['success'] = true;