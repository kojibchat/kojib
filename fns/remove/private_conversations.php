<?php
$result = array();
$noerror = true;
$current_user_id = Registry::load('current_user')->id;
$private_conversation_ids = array();

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';


if (isset($data['private_conversation_id'])) {

    if (role(['permissions' => ['private_conversations' => 'super_privileges']])) {
        if (!is_array($data['private_conversation_id'])) {
            $data["private_conversation_id"] = filter_var($data["private_conversation_id"], FILTER_SANITIZE_NUMBER_INT);
            $private_conversation_ids[] = $data["private_conversation_id"];
        } else {
            $private_conversation_ids = array_filter($data["private_conversation_id"], 'ctype_digit');
        }

        if (!empty($private_conversation_ids)) {

            include 'fns/filters/load.php';
            include 'fns/files/load.php';

            foreach ($private_conversation_ids as $private_conversation_id) {
                if (!empty($private_conversation_id)) {
                    $delete_audio_messages = [
                        'delete' => 'assets/files/audio_messages/private_chat/'.$private_conversation_id,
                        'real_path' => true,
                    ];

                    files('delete', $delete_audio_messages);
                }
            }

            DB::connect()->delete("private_conversations", ["private_conversation_id" => $private_conversation_ids]);

            if (!DB::connect()->error) {

                $result = array();
                $result['success'] = true;
                $result['todo'] = 'reload';
                $result['reload'] = 'site_user_private_chats';

            } else {
                $result['errormsg'] = Registry::load('strings')->went_wrong;
            }
        }
    }
}
?>