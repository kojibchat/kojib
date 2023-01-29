<?php
$result = array();
$noerror = true;
$current_user_id = Registry::load('current_user')->id;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';


if (isset($data['clear_chat_history'])) {
    if (isset($data['user_id'])) {
        $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if (isset($data['user_id']) && !empty($data['user_id']) && role(['permissions' => ['private_conversations' => 'clear_chat_history']])) {

        $columns = $join = $where = null;
        $columns = [
            'private_conversations.private_conversation_id', 'initiator_user_id', 'recipient_user_id'
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
        $private_conversation = DB::connect()->select('private_conversations', $columns, $where);
        $last_message_id = null;


        if (isset($private_conversation[0])) {

            $private_conversation = $private_conversation[0];
            $conversation_id = $private_conversation['private_conversation_id'];

            if ((int)$private_conversation['initiator_user_id'] === (int)$current_user_id) {
                $update_column = 'initiator_load_message_id_from';
            } else {
                $update_column = 'recipient_load_message_id_from';
            }


            $column = $join = $where = null;
            $columns = [
                'private_chat_messages.private_chat_message_id'
            ];

            $where["private_chat_messages.private_conversation_id"] = $conversation_id;
            $where["ORDER"] = ['private_chat_messages.private_chat_message_id' => 'DESC'];
            $where["LIMIT"] = 1;
            $private_chat_messages = DB::connect()->select('private_chat_messages', $columns, $where);

            if (isset($private_chat_messages[0])) {
                $last_message_id = $private_chat_messages[0]['private_chat_message_id'];
            }

            DB::connect()->update("private_chat_messages", ["read_status" => 1], [
                'private_conversation_id' => $conversation_id,
                'user_id[!]' => $current_user_id
            ]);

            DB::connect()->update("private_conversations", [$update_column => $last_message_id], ["private_conversation_id" => $conversation_id]);

            if (!DB::connect()->error) {
                $result = array();
                $result['success'] = true;
                $result['todo'] = 'refresh';
            } else {
                $result['error_message'] = Registry::load('strings')->went_wrong;
            }
        }
    }
} else if (isset($data['message_id'])) {

    $message_ids = array();

    if (role(['permissions' => ['private_conversations' => 'super_privileges']])) {
        if (!is_array($data['message_id'])) {
            $data["message_id"] = filter_var($data["message_id"], FILTER_SANITIZE_NUMBER_INT);
            $message_ids[] = $data["message_id"];
        } else {
            $message_ids = array_filter($data["message_id"], 'ctype_digit');
        }
    } else if (role(['permissions' => ['private_conversations' => 'delete_own_message']])) {
        if (!is_array($data['message_id'])) {
            $data["message_id"] = filter_var($data["message_id"], FILTER_SANITIZE_NUMBER_INT);
            $message_ids[] = $data["message_id"];
        } else {
            $message_ids = array_filter($data["message_id"], 'ctype_digit');
        }

        $columns = $join = $where = null;
        $columns = [
            'private_chat_messages.private_chat_message_id', 'private_chat_messages.created_on'
        ];

        $where = [
            "private_chat_messages.private_chat_message_id" => $message_ids,
            "private_chat_messages.user_id" => $current_user_id,
        ];

        $private_messages = DB::connect()->select('private_chat_messages', $columns, $where);

        $message_ids = array();
        $delete_message_time_limit = role(['find' => 'delete_message_time_limit']);

        foreach ($private_messages as $private_message) {
            if (!empty($delete_message_time_limit)) {

                $to_time = strtotime($private_message['created_on']);
                $from_time = strtotime("now");
                $time_difference = round(abs($to_time - $from_time) / 60, 2);

                if ($time_difference < $delete_message_time_limit) {
                    $message_ids[] = $private_message['private_chat_message_id'];
                }
            }
        }

    }

    if (!empty($message_ids)) {

        include 'fns/filters/load.php';
        include 'fns/files/load.php';

        $columns = $where = null;
        $columns = ['private_chat_messages.attachments', 'private_chat_messages.private_conversation_id'];

        $where = [
            'attachment_type' => 'audio_message',
        ];

        $where["private_chat_message_id"] = $message_ids;

        $private_chat_audio_messages = DB::connect()->select('private_chat_messages', $columns, $where);

        foreach ($private_chat_audio_messages as $private_chat_audio_message) {

            $audio_message = $private_chat_audio_message['attachments'];
            $private_conversation_id = $private_chat_audio_message['private_conversation_id'];

            $audio_message = json_decode($audio_message);

            if (!empty($audio_message) && isset($audio_message->audio_message)) {
                $audio_message = basename($audio_message->audio_message);
            }

            if (!empty($audio_message)) {
                $delete_audio_messages = [
                    'delete' => 'assets/files/audio_messages/private_chat/'.$private_conversation_id.'/'.$audio_message,
                    'real_path' => true,
                ];

                files('delete', $delete_audio_messages);
            }
        }

        DB::connect()->delete("private_chat_messages", ["private_chat_message_id" => $message_ids]);

        if (!DB::connect()->error) {

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'refresh';
            $result['todo'] = 'remove_messages';
            $result['remove_data']['message_id'] = $message_ids;
            $result['remove_data']['conversation_type'] = 'private_chat';

            $realtime_log_data = array();
            $realtime_log_data["log_type"] = 'deleted_message';
            $realtime_log_data["related_parameters"] = ["message_id" => $message_ids, "conversation_type" => 'private_chat'];
            $realtime_log_data["related_parameters"] = json_encode($realtime_log_data["related_parameters"]);
            $realtime_log_data["created_on"] = Registry::load('current_user')->time_stamp;

            DB::connect()->insert("realtime_logs", $realtime_log_data);

        } else {
            $result['errormsg'] = Registry::load('strings')->went_wrong;
        }
    }
}
?>