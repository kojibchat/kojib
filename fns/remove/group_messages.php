<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$no_error = false;
$user_id = Registry::load('current_user')->id;
$message_ids = array();
$super_privileges = false;

if (role(['permissions' => ['groups' => 'super_privileges']])) {
    $super_privileges = true;
}

if (isset($data['group_id']) && isset($data['clear_chat_history'])) {

    if (role(['permissions' => ['groups' => 'clear_chat_history']])) {

        $group_id = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($group_id)) {
            $last_message_id = 0;

            $column = $join = $where = null;
            $columns = [
                'group_messages.group_message_id'
            ];

            $where["group_messages.group_id"] = $group_id;
            $where["ORDER"] = ['group_messages.group_message_id' => 'DESC'];
            $where["LIMIT"] = 1;
            $last_group_message_id = DB::connect()->select('group_messages', $columns, $where);

            if (isset($last_group_message_id[0])) {
                $last_message_id = $last_group_message_id[0]['group_message_id'];
            }

            DB::connect()->update("group_members",
                ['load_message_id_from' => $last_message_id, 'last_read_message_id' => $last_message_id],
                ["group_id" => $group_id, "user_id" => $user_id]
            );

            if (!DB::connect()->error) {
                $result = array();
                $result['success'] = true;
                $result['todo'] = 'refresh';
            } else {
                $result['error_message'] = Registry::load('strings')->went_wrong;
            }
        }
    }

    return;
}

if (isset($data['message_id'])) {
    if (!is_array($data['message_id'])) {
        $data["message_id"] = filter_var($data["message_id"], FILTER_SANITIZE_NUMBER_INT);
        $message_ids[] = $data["message_id"];
    } else {
        $message_ids = array_filter($data["message_id"], 'ctype_digit');
    }
} else if (isset($data['group_id'])) {
    $group_id = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
}

if (!$super_privileges && !empty($message_ids)) {

    $columns = $join = $where = null;
    $columns = [
        'group_messages.group_message_id', 'group_messages.user_id',
        'group_messages.created_on', 'group_members.group_role_id'
    ];

    $join["[>]group_members"] = ["group_messages.group_id" => "group_id", "AND" => ["group_members.user_id" => $user_id]];

    $where["group_messages.group_message_id"] = $message_ids;

    $group_messages = DB::connect()->select('group_messages', $join, $columns, $where);

    $message_ids = array();
    $delete_message_time_limit = role(['find' => 'delete_message_time_limit']);

    foreach ($group_messages as $group_message) {
        if (isset($group_message['group_role_id']) && !empty($group_message['group_role_id'])) {
            if (role(['permissions' => ['messages' => 'delete_messages'], 'group_role_id' => $group_message['group_role_id']])) {
                $message_ids[] = $group_message['group_message_id'];
            } else if (role(['permissions' => ['messages' => 'delete_own_message'], 'group_role_id' => $group_message['group_role_id']])) {
                if ((int)$user_id === (int)$group_message['user_id']) {
                    if (!empty($delete_message_time_limit)) {

                        $to_time = strtotime($group_message['created_on']);
                        $from_time = strtotime("now");
                        $time_difference = round(abs($to_time - $from_time) / 60, 2);

                        if ($time_difference < $delete_message_time_limit) {
                            $message_ids[] = $group_message['group_message_id'];
                        }
                    }
                }
            }
        }
    }

} else if (!$super_privileges && !empty($group_id)) {

    $columns = $join = $where = null;
    $columns = [
        'groups.group_id', 'group_members.group_role_id',
    ];

    $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["group_members.user_id" => $user_id]];

    $where["groups.group_id"] = $group_id;
    $where["LIMIT"] = 1;

    $group = DB::connect()->select('groups', $join, $columns, $where);
    $group_id = 0;

    if (isset($group[0])) {
        $group = $group[0];

        if (isset($group['group_role_id']) && !empty($group['group_role_id'])) {
            if (role(['permissions' => ['messages' => 'delete_messages'], 'group_role_id' => $group['group_role_id']])) {
                $group_id = $group['group_id'];
            }
        }
    }
}


if (!empty($message_ids) || !empty($group_id)) {
    $no_error = true;
}


if ($no_error) {

    if (!empty($message_ids) || !empty($group_id)) {

        include 'fns/filters/load.php';
        include 'fns/files/load.php';

        if (!empty($message_ids)) {
            $columns = $where = null;
            $columns = ['group_messages.attachments', 'group_messages.group_id'];

            $where = [
                'attachment_type' => 'audio_message',
            ];

            $where["group_message_id"] = $message_ids;

            $group_audio_messages = DB::connect()->select('group_messages', $columns, $where);

            foreach ($group_audio_messages as $group_audio_message) {

                $audio_message = $group_audio_message['attachments'];
                $message_group_id = $group_audio_message['group_id'];

                $audio_message = json_decode($audio_message);

                if (!empty($audio_message) && isset($audio_message->audio_message)) {
                    $audio_message = basename($audio_message->audio_message);
                }

                if (!empty($audio_message)) {
                    $delete_audio_messages = [
                        'delete' => 'assets/files/audio_messages/group_chat/'.$message_group_id.'/'.$audio_message,
                        'real_path' => true,
                    ];

                    files('delete', $delete_audio_messages);
                }
            }
        } else if (!empty($group_id)) {
            $delete_audio_messages = [
                'delete' => 'assets/files/audio_messages/group_chat/'.$group_id.'/',
                'real_path' => true,
            ];

            files('delete', $delete_audio_messages);
        }
    }

    $where = array();

    if (!empty($message_ids)) {
        $where["group_message_id"] = $message_ids;
    } else if (!empty($group_id)) {
        $where["group_id"] = $group_id;
    }


    DB::connect()->delete("group_messages", $where);

    $result = array();
    $result['success'] = true;

    if (!empty($message_ids)) {


        $realtime_log_data = array();
        $realtime_log_data["log_type"] = 'deleted_message';
        $realtime_log_data["related_parameters"] = ["message_id" => $message_ids, "conversation_type" => 'group_chat'];
        $realtime_log_data["related_parameters"] = json_encode($realtime_log_data["related_parameters"]);
        $realtime_log_data["created_on"] = Registry::load('current_user')->time_stamp;

        DB::connect()->insert("realtime_logs", $realtime_log_data);

        $result['todo'] = 'remove_messages';
        $result['remove_data']['message_id'] = $message_ids;
        $result['remove_data']['conversation_type'] = 'group_chat';

    } elseif (!empty($group_id)) {


        $realtime_log_data = array();
        $realtime_log_data["log_type"] = 'removed_all_messages';
        $realtime_log_data["related_parameters"] = ["group_id" => $group_id];
        $realtime_log_data["related_parameters"] = json_encode($realtime_log_data["related_parameters"]);
        $realtime_log_data["created_on"] = Registry::load('current_user')->time_stamp;

        DB::connect()->insert("realtime_logs", $realtime_log_data);

        $result['todo'] = 'load_conversation';
        $result['identifier_type'] = 'group_id';
        $result['identifier'] = $group_id;
    }
}

?>