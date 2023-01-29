<?php

use Medoo\Medoo;

if (role(['permissions' => ['super_privileges' => 'monitor_private_chats']])) {

    $find_user_id = Registry::load('current_user')->id;
    $super_privileges = false;

    if (isset($data["user_id"])) {

        $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data["user_id"])) {
            $find_user_id = $data["user_id"];
        }
    }

    if (role(['permissions' => ['private_conversations' => 'super_privileges']])) {
        $super_privileges = true;
    }


    $columns = $join = $where = null;
    $columns = ['site_users.display_name'];
    $where["site_users.user_id"] = $find_user_id;
    $where["LIMIT"] = 1;

    $user_info = DB::connect()->select('site_users', $columns, $where);

    if (isset($user_info[0])) {
        $user_info = $user_info[0];
    } else {
        return;
    }

    $columns = $join = $where = null;
    $columns = [
        'recipient.display_name(recipient_name)', 'initiator.display_name(initiator_name)',
        'recipient.username(recipient_username)', 'initiator.username(initiator_username)',
        'recipient.online_status(recipient_online_status)', 'initiator.online_status(initiator_online_status)',
        'recipient_settings.offline_mode(recipient_offline_mode)', 'initiator_settings.offline_mode(initiator_offline_mode)',
        'private_conversations.initiator_user_id', 'private_conversations.recipient_user_id', 'private_conversations.private_conversation_id',
    ];


    $columns['unread_messages'] = Medoo::raw('(SELECT count(<private_chat_message_id>) FROM <private_chat_messages> WHERE <user_id> != :current_user_id AND <private_conversation_id> = <private_conversations.private_conversation_id> AND <read_status> = 0)', ['current_user_id' => $find_user_id]);

    $columns['last_message_content'] = Medoo::raw('(SELECT <filtered_message> FROM <private_chat_messages> WHERE <private_conversation_id> = <private_conversations.private_conversation_id> ORDER BY <private_chat_message_id> DESC LIMIT 1)', ['current_user_id' => $find_user_id]);
    $columns['last_message_attachment'] = Medoo::raw('(SELECT <attachment_type> FROM <private_chat_messages> WHERE <private_conversation_id> = <private_conversations.private_conversation_id> ORDER BY <private_chat_message_id> DESC LIMIT 1)', ['current_user_id' => $find_user_id]);


    $join["[>]site_users(recipient)"] = ["private_conversations.recipient_user_id" => "user_id"];
    $join["[>]site_users(initiator)"] = ["private_conversations.initiator_user_id" => "user_id"];

    $join["[>]site_users_settings(recipient_settings)"] = ["private_conversations.recipient_user_id" => "user_id"];
    $join["[>]site_users_settings(initiator_settings)"] = ["private_conversations.initiator_user_id" => "user_id"];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["private_conversations.private_conversation_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {
        $where["AND #search_query"]["OR"]["AND #first_query"] = [
            "recipient.display_name[~]" => $data["search"],
            "recipient.user_id[!]" => $find_user_id,
        ];

        $where["AND #search_query"]["OR"]["AND #second_query"] = [
            "initiator.display_name[~]" => $data["search"],
            "initiator.user_id[!]" => $find_user_id,
        ];

        $where["AND #search_query"]["OR"]["AND #third_query"] = [
            "recipient.username[~]" => $data["search"],
            "recipient.user_id[!]" => $find_user_id,
        ];

        $where["AND #search_query"]["OR"]["AND #fourth_query"] = [
            "initiator.username[~]" => $data["search"],
            "initiator.user_id[!]" => $find_user_id,
        ];
    }

    $where["AND"]["OR #first_query"] = [
        "private_conversations.initiator_user_id" => $find_user_id,
        "private_conversations.recipient_user_id" => $find_user_id,
    ];

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    $where["ORDER"] = ["private_conversations.updated_on" => "DESC"];
    $conversations = DB::connect()->select('private_conversations', $join, $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->chats;
    $output['loaded']->offset = array();

    $full_name = $user_info['display_name'];

    if (strlen($full_name) > 10) {
        $full_name = trim(mb_substr($full_name, 0, 10));
    }

    $output['loaded']->title .= ' ['.$full_name.']';

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    if ($super_privileges) {
        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'private_conversations';
        $output['multiple_select']->attributes['multi_select'] = 'private_conversation_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }

    foreach ($conversations as $conversation) {

        $output['loaded']->offset[] = $conversation['private_conversation_id'];

        if ((int)$conversation['initiator_user_id'] === (int)$find_user_id) {
            $user_id = $conversation['recipient_user_id'];
            $display_name = $conversation['recipient_name'];
            $user_name = $conversation['recipient_username'];
            $online_status = $conversation['recipient_online_status'];
            $offline_mode = $conversation['recipient_offline_mode'];

        } else {
            $user_id = $conversation['initiator_user_id'];
            $display_name = $conversation['initiator_name'];
            $user_name = $conversation['initiator_username'];
            $online_status = $conversation['initiator_online_status'];
            $offline_mode = $conversation['initiator_offline_mode'];
        }

        $load_conversation_id = 'all['.$conversation['private_conversation_id'].']';

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $user_id]);
        $output['content'][$i]->title = $display_name;
        $output['content'][$i]->class = "private_conversation load_conversation";
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;
        $output['content'][$i]->subtitle = '@'.$user_name;
        $output['content'][$i]->identifier = $conversation['private_conversation_id'];
        $output['content'][$i]->attributes = ['user_id' => $load_conversation_id, 'stopPropagation' => true];

        if (isset($conversation['last_message_content'])) {
            $output['content'][$i]->subtitle = strip_tags($conversation['last_message_content'], "<span>");
        }

        if (empty($output['content'][$i]->subtitle)) {
            if (isset($conversation['last_message_attachment'])) {
                if ($conversation['last_message_attachment'] === 'screenshot') {
                    $output['content'][$i]->subtitle = Registry::load('strings')->screenshot;
                } else if ($conversation['last_message_attachment'] === 'gif') {
                    $output['content'][$i]->subtitle = Registry::load('strings')->gif;
                } else if ($conversation['last_message_attachment'] === 'sticker') {
                    $output['content'][$i]->subtitle = Registry::load('strings')->sticker;
                } else if ($conversation['last_message_attachment'] === 'audio_message') {
                    $output['content'][$i]->subtitle = Registry::load('strings')->audio_message;
                } else {
                    $output['content'][$i]->subtitle = Registry::load('strings')->attachments;
                }

            }
        }

        if (role(['permissions' => ['site_users' => 'view_online_users']])) {

            if ((int)$online_status === 1) {
                $output['content'][$i]->online_status = 'online';
            } else if ((int)$online_status === 2) {
                $output['content'][$i]->online_status = 'idle';
            }

            if ((int)$offline_mode === 1) {
                if (!role(['permissions' => ['site_users' => 'view_invisible_users']])) {
                    unset($output['content'][$i]->online_status);
                }
            }
        }

        $option_index = 1;

        if (role(['permissions' => ['private_conversations' => 'export_chat']])) {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->export_chat;
            $output['options'][$i][$option_index]->class = 'download_file';
            $output['options'][$i][$option_index]->attributes['download'] = 'messages';
            $output['options'][$i][$option_index]->attributes['data-private_conversation_id'] = $conversation['private_conversation_id'];
            $option_index++;
        }

        if ($super_privileges) {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->delete;
            $output['options'][$i][$option_index]->class = 'ask_confirmation';
            $output['options'][$i][$option_index]->attributes['data-remove'] = 'private_conversations';
            $output['options'][$i][$option_index]->attributes['data-private_conversation_id'] = $conversation['private_conversation_id'];
            $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
            $option_index++;
        }

        $output['options'][$i][$option_index] = new stdClass();
        $output['options'][$i][$option_index]->option = Registry::load('strings')->profile;
        $output['options'][$i][$option_index]->class = 'get_info force_request';
        $output['options'][$i][$option_index]->attributes['user_id'] = $user_id;
        $option_index++;

        $i++;
    }
}
?>