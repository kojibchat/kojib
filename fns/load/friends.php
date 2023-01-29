<?php

if (Registry::load('settings')->friend_system === 'enable') {
    if (role(['permissions' => ['friend_system' => 'view_friends']])) {
        $columns = $join = $where = null;
        $current_user_id = Registry::load('current_user')->id;
        $columns = [
            'from_user.display_name(from_fullname)', 'from_user.email_address(from_email)',
            'from_user.username(from_username)', 'from_user.online_status(from_online)', 'friends.friendship_id',
            'friends.from_user_id', 'friends.to_user_id', 'friends.relation_status',
            'to_user.display_name(to_fullname)', 'to_user.email_address(to_email)',
            'to_user.username(to_username)', 'to_user.online_status(to_online)',
        ];

        $join["[>]site_users(from_user)"] = ["friends.from_user_id" => "user_id"];
        $join["[>]site_users(to_user)"] = ["friends.to_user_id" => "user_id"];

        if ($data["filter"] === 'sent_requests') {
            $where["AND #friend_list"]["OR"]["AND #first_query"] = [
                "friends.from_user_id" => $current_user_id,
                "friends.relation_status" => 0,
            ];
        } else {
            $where["AND #friend_list"]["OR"]["AND #first_query"] = [
                "friends.to_user_id" => $current_user_id,
            ];
            $where["AND #friend_list"]["OR"]["AND #second_query"] = [
                "friends.from_user_id" => $current_user_id,
                "friends.relation_status" => 1,
            ];
        }

        if (!empty($data["offset"])) {
            $data["offset"] = array_map('intval', explode(',', $data["offset"]));
            $where["friends.friendship_id[!]"] = $data["offset"];
        }

        if (!empty($data["search"])) {
            $where["AND #search_query"]["OR"]["AND #first_query"] = [
                "from_user.display_name[~]" => $data["search"],
                "friends.from_user_id[!]" => $current_user_id,
            ];

            $where["AND #search_query"]["OR"]["AND #second_query"] = [
                "to_user.display_name[~]" => $data["search"],
                "friends.to_user_id[!]" => $current_user_id,
            ];

            $where["AND #search_query"]["OR"]["AND #third_query"] = [
                "from_user.username[~]" => $data["search"],
                "friends.from_user_id[!]" => $current_user_id,
            ];

            $where["AND #search_query"]["OR"]["AND #fourth_query"] = [
                "to_user.username[~]" => $data["search"],
                "friends.to_user_id[!]" => $current_user_id,
            ];
        }

        $where["LIMIT"] = Registry::load('settings')->records_per_call;

        $where["ORDER"] = ["friends.relation_status" => "ASC"];

        $site_users = DB::connect()->select('friends', $join, $columns, $where);

        $i = 1;
        $output = array();
        $output['loaded'] = new stdClass();
        $output['loaded']->title = Registry::load('strings')->friends;
        $output['loaded']->offset = array();

        if (!empty($data["offset"])) {
            $output['loaded']->offset = $data["offset"];
        }

        $output['filters'][1] = new stdClass();
        $output['filters'][1]->filter = Registry::load('strings')->friends;
        $output['filters'][1]->class = 'load_aside';
        $output['filters'][1]->attributes['load'] = 'friends';

        $output['filters'][2] = new stdClass();
        $output['filters'][2]->filter = Registry::load('strings')->sent;
        $output['filters'][2]->class = 'load_aside';
        $output['filters'][2]->attributes['load'] = 'friends';
        $output['filters'][2]->attributes['filter'] = 'sent_requests';

        if (role(['permissions' => ['site_users' => 'view_online_users']])) {
            $check_online_status = true;
        } else {
            $check_online_status = false;
        }

        foreach ($site_users as $user) {

            if ((int)$user['from_user_id'] === (int)$current_user_id) {
                $user['user_id'] = $user['to_user_id'];
                $user['username'] = $user['to_username'];
                $user['display_name'] = $user['to_fullname'];
                $user['email_address'] = $user['to_email'];
                $user['online_status'] = $user['to_online'];
            } else {
                $user['user_id'] = $user['from_user_id'];
                $user['username'] = $user['from_username'];
                $user['display_name'] = $user['from_fullname'];
                $user['email_address'] = $user['from_email'];
                $user['online_status'] = $user['from_online'];
            }

            $output['loaded']->offset[] = $user['friendship_id'];

            $output['content'][$i] = new stdClass();
            $output['content'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $user['user_id'], 'gravatar' => $user['email_address']]);
            $output['content'][$i]->title = $user['display_name'];
            $output['content'][$i]->class = "friends";
            $output['content'][$i]->icon = 0;
            $output['content'][$i]->unread = 0;
            $output['content'][$i]->identifier = $user['user_id'];

            if ($check_online_status) {
                if ((int)$user['online_status'] === 1) {
                    $output['content'][$i]->online_status = 'online';
                } else if ((int)$user['online_status'] === 2) {
                    $output['content'][$i]->online_status = 'idle';
                }
            }

            $output['content'][$i]->subtitle = '@'.$user['username'];

            if ($data["filter"] !== 'sent_requests' && empty($user["relation_status"])) {
                $output['content'][$i]->subtitle = Registry::load('strings')->pending;
            }

            $option_index = 1;

            if ((int)$user['to_user_id'] === (int)$current_user_id && empty($user["relation_status"])) {

                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->accept_friend;
                $output['options'][$i][$option_index]->class = 'api_request';
                $output['options'][$i][$option_index]->attributes['data-update'] = 'friend';
                $output['options'][$i][$option_index]->attributes['data-user_id'] = $user['user_id'];
                $option_index++;

                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->reject_request;
                $output['options'][$i][$option_index]->class = 'ask_confirmation';
                $output['options'][$i][$option_index]->attributes['data-remove'] = 'friend';
                $output['options'][$i][$option_index]->attributes['data-user_id'] = $user['user_id'];
                $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
                $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                $option_index++;

            } else if (!empty($user["relation_status"])) {
                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->unfriend;
                $output['options'][$i][$option_index]->class = 'ask_confirmation';
                $output['options'][$i][$option_index]->attributes['data-remove'] = 'friend';
                $output['options'][$i][$option_index]->attributes['data-user_id'] = $user['user_id'];
                $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
                $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                $option_index++;
            } else if ((int)$user['from_user_id'] === (int)$current_user_id && empty($user["relation_status"])) {
                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->cancel_request;
                $output['options'][$i][$option_index]->class = 'ask_confirmation';
                $output['options'][$i][$option_index]->attributes['data-remove'] = 'friend';
                $output['options'][$i][$option_index]->attributes['data-user_id'] = $user['user_id'];
                $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
                $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                $option_index++;
            }

            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->profile;
            $output['options'][$i][$option_index]->class = 'get_info';
            $output['options'][$i][$option_index]->attributes['user_id'] = $user['user_id'];
            $option_index++;

            $allow_private_messages = true;

            if (empty($user["relation_status"])) {
                if (!role(['permissions' => ['private_conversations' => 'message_non_friends']])) {
                    $allow_private_messages = false;
                }
            }

            if ($allow_private_messages) {
                if (role(['permissions' => ['private_conversations' => 'send_message']])) {
                    $output['options'][$i][$option_index] = new stdClass();
                    $output['options'][$i][$option_index]->option = Registry::load('strings')->message;
                    $output['options'][$i][$option_index]->class = 'load_conversation force_request';
                    $output['options'][$i][$option_index]->attributes['user_id'] = $user['user_id'];
                    $option_index++;
                }
            }


            $i++;
        }
    }
}
?>