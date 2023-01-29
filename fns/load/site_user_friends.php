<?php

if (Registry::load('settings')->friend_system === 'enable') {
    if (role(['permissions' => ['friend_system' => 'view_friends']])) {
        $current_user_id = $user_id = Registry::load('current_user')->id;
        $view_friends_list = false;

        if (role(['permissions' => ['site_users' => 'edit_users']])) {
            $view_friends_list = true;
        }

        if (isset($data["user_id"])) {
            $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);

            if (!empty($data["user_id"])) {
                $user_id = $data["user_id"];
            }
        }

        if ((int)$current_user_id !== (int)$user_id && !$view_friends_list) {
            $columns = $join = $where = null;
            $columns = ['friendship_id', 'from_user_id', 'to_user_id', 'relation_status'];

            $where["OR"]["AND #first_query"] = [
                "friends.from_user_id" => $user_id,
                "friends.to_user_id" => $current_user_id,
            ];
            $where["OR"]["AND #second_query"] = [
                "friends.from_user_id" => $current_user_id,
                "friends.to_user_id" => $user_id,
            ];

            $where["LIMIT"] = 1;

            $check_friend_list = DB::connect()->select('friends', $columns, $where);

            if (isset($check_friend_list[0])) {
                if (!empty($check_friend_list[0]['relation_status'])) {
                    $view_friends_list = true;
                }
            }

            if (!$view_friends_list) {
                return;
            }
        }



        $columns = $join = $where = null;
        $columns = [
            'from_user.display_name(from_fullname)', 'from_user.email_address(from_email)',
            'from_user.username(from_username)', 'from_user.online_status(from_online)', 'friends.friendship_id',
            'friends.from_user_id', 'friends.to_user_id', 'friends.relation_status',
            'to_user.display_name(to_fullname)', 'to_user.email_address(to_email)',
            'to_user.username(to_username)', 'to_user.online_status(to_online)',
        ];

        $join["[>]site_users(from_user)"] = ["friends.from_user_id" => "user_id"];
        $join["[>]site_users(to_user)"] = ["friends.to_user_id" => "user_id"];

        $where = ["relation_status" => 1, "OR" => ["from_user_id" => $user_id, "to_user_id" => $user_id]];

        if (!empty($data["offset"])) {
            $data["offset"] = array_map('intval', explode(',', $data["offset"]));
            $where["friends.friendship_id[!]"] = $data["offset"];
        }

        if (!empty($data["search"])) {
            $where["AND #search_query"]["OR"] = [
                "from_user.display_name[~]" => $data["search"],
                "friends.from_user_id[!]" => $user_id,
            ];

            $where["AND #search_query"]["OR"]["AND #second_query"] = [
                "to_user.display_name[~]" => $data["search"],
                "friends.to_user_id[!]" => $user_id,
            ];

            $where["AND #search_query"]["OR"]["AND #third_query"] = [
                "from_user.username[~]" => $data["search"],
                "friends.from_user_id[!]" => $user_id,
            ];

            $where["AND #search_query"]["OR"]["AND #fourth_query"] = [
                "to_user.username[~]" => $data["search"],
                "friends.to_user_id[!]" => $user_id,
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

        if (role(['permissions' => ['site_users' => 'view_online_users']])) {
            $check_online_status = true;
        } else {
            $check_online_status = false;
        }

        foreach ($site_users as $user) {

            if ((int)$user['from_user_id'] === (int)$user_id) {
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
            $output['content'][$i]->class = "friends get_info";
            $output['content'][$i]->icon = 0;
            $output['content'][$i]->unread = 0;
            $output['content'][$i]->identifier = $user['user_id'];

            $output['content'][$i]->attributes = ['user_id' => $user['user_id'], 'stopPropagation' => true];

            if ($check_online_status) {
                if ((int)$user['online_status'] === 1) {
                    $output['content'][$i]->online_status = 'online';
                } else if ((int)$user['online_status'] === 2) {
                    $output['content'][$i]->online_status = 'idle';
                }
            }

            $output['content'][$i]->subtitle = '@'.$user['username'];


            $option_index = 1;

            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->profile;
            $output['options'][$i][$option_index]->class = 'get_info';
            $output['options'][$i][$option_index]->attributes['user_id'] = $user['user_id'];
            $option_index++;


            $i++;
        }
    }
}
?>