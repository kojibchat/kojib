<?php

include 'fns/filters/load.php';
include 'fns/files/load.php';

$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';
$user_ids = array();


if ($force_request || role(['permissions' => ['site_users' => 'delete_users']])) {

    if (isset($data['user_id'])) {
        if (!is_array($data['user_id'])) {
            $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
            $user_ids[] = $data["user_id"];
        } else {
            $user_ids = array_filter($data["user_id"], 'ctype_digit');
        }
    }

    if ($force_request) {
        if (isset($data['user'])) {
            $columns = $join = $where = null;

            $columns = ['site_users.user_id'];
            $where["OR"] = ["site_users.username" => $data['user'], "site_users.email_address" => $data['user']];
            $where["LIMIT"] = 1;

            $site_user = DB::connect()->select('site_users', $columns, $where);

            $user_ids = array();

            if (isset($site_user[0])) {
                $user_ids[] = $site_user[0]['user_id'];
            } else {
                $result = array();
                $result['success'] = false;
                $result['error_message'] = Registry::load('strings')->account_not_found;
                $result['error_key'] = 'account_not_found';
                $result['error_variables'] = [];
                return;
            }
        }
    }




    if (!empty($user_ids)) {

        $columns = $where = null;
        $columns = ['private_conversations.private_conversation_id'];

        $where["AND"]["OR #first_query"] = [
            "private_conversations.initiator_user_id" => $user_ids,
            "private_conversations.recipient_user_id" => $user_ids,
        ];
        $conversations = DB::connect()->select('private_conversations', $columns, $where);

        foreach ($conversations as $converstation) {

            $converstation_id = $converstation['private_conversation_id'];

            if (!empty($converstation_id)) {
                $delete_audio_messages = [
                    'delete' => 'assets/files/audio_messages/private_chat/'.$converstation_id,
                    'real_path' => true,
                ];

                files('delete', $delete_audio_messages);
            }
        }

        DB::connect()->delete('private_conversations', $where);


        $columns = $where = null;
        $columns = ['group_messages.attachments', 'group_messages.group_id'];

        $where = [
            'attachment_type' => 'audio_message',
            'user_id' => $user_ids
        ];

        $group_audio_messages = DB::connect()->select('group_messages', $columns, $where);

        foreach ($group_audio_messages as $group_audio_message) {

            $audio_message = $group_audio_message['attachments'];
            $group_id = $group_audio_message['group_id'];

            $audio_message = json_decode($audio_message);

            if (!empty($audio_message) && isset($audio_message->audio_message)) {
                $audio_message = basename($audio_message->audio_message);
            }

            if (!empty($audio_message)) {
                $delete_audio_messages = [
                    'delete' => 'assets/files/audio_messages/group_chat/'.$group_id.'/'.$audio_message,
                    'real_path' => true,
                ];

                files('delete', $delete_audio_messages);
            }
        }

        DB::connect()->delete("site_users", ["user_id" => $user_ids]);

        if (!DB::connect()->error) {

            foreach ($user_ids as $user_id) {
                foreach (glob("assets/files/site_users/backgrounds/".$user_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                    unlink($oldimage);
                }
                foreach (glob("assets/files/site_users/cover_pics/".$user_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                    unlink($oldimage);
                }
                foreach (glob("assets/files/site_users/profile_pics/".$user_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                    unlink($oldimage);
                }

                $delete_storage = [
                    'delete' => 'assets/files/storage/'.$user_id,
                    'real_path' => true,
                ];

                files('delete', $delete_storage);
            }

            $result = array();
            $result['success'] = true;
            if ((int)$user_id === (int)Registry::load('current_user')->id) {
                $result['todo'] = 'refresh';
            } else {
                $result['todo'] = 'reload';
                $result['reload'] = 'site_users';

                if (isset($data['info_box'])) {
                    $result['info_box']['user_id'] = Registry::load('current_user')->id;
                }
            }
        } else {
            $result['error_message'] = Registry::load('strings')->something_went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }
    }
}
?>