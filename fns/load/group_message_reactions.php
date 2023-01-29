<?php

if (role(['permissions' => ['groups' => 'view_reactions']])) {

    if (isset($data["group_id"]) && isset($data["message_id"])) {

        $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
        $data["message_id"] = filter_var($data["message_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data["group_id"]) && !empty($data["message_id"])) {

            $super_privileges = false;

            if (role(['permissions' => ['groups' => 'super_privileges']])) {
                $super_privileges = true;
            }

            $group_id = $data["group_id"];

            $columns = $where = $join = null;
            $columns = [
                'groups.group_id', 'groups.secret_group', 'groups.password', 'group_members.group_role_id',
                'group_roles.group_role_attribute'
            ];

            $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => Registry::load('current_user')->id]];
            $join["[>]group_roles"] = ["group_members.group_role_id" => "group_role_id"];

            $where['groups.group_id'] = $group_id;

            $group_info = DB::connect()->select('groups', $join, $columns, $where);

            if (isset($group_info[0])) {
                $group_info = $group_info[0];
            } else {
                return false;
            }

            $output = array();
            $output['loaded'] = new stdClass();
            $output['loaded']->title = Registry::load('strings')->reactions;
            $output['loaded']->loaded = 'group_message_reactions';
            $output['loaded']->null_search = true;
            $output['loaded']->offset = array();

            if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {

                if ($super_privileges || role(['permissions' => ['messages' => 'view_reactions'], 'group_role_id' => $group_info['group_role_id']])) {

                    $columns = $where = $join = null;
                    $columns = [
                        'group_messages_reactions.user_id', 'site_users.display_name',
                        'group_messages_reactions.reaction_id', 'site_users.username',
                        'group_messages_reactions.group_message_reaction_id',
                    ];

                    $join["[>]site_users"] = ["group_messages_reactions.user_id" => "user_id"];

                    $where["group_messages_reactions.group_message_id"] = $data["message_id"];

                    if (!empty($data["offset"])) {
                        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
                        $where["group_messages_reactions.group_message_reaction_id[!]"] = $data["offset"];
                    }

                    if (!empty($data["search"])) {
                        $where["AND #search_query"] = ["OR" => [
                            "site_users.display_name[~]" => $data["search"],
                            "site_users.username" => $data["search"],
                            "site_users.email_address" => $data["search"],
                        ]];
                    }

                    $where["LIMIT"] = Registry::load('settings')->records_per_call;

                    $where["ORDER"] = ["group_messages_reactions.group_message_reaction_id" => "DESC"];

                    $message_reactions = DB::connect()->select('group_messages_reactions', $join, $columns, $where);

                    $i = 1;

                    if (!empty($data["offset"])) {
                        $output['loaded']->offset = $data["offset"];
                    }


                    foreach ($message_reactions as $message_reaction) {

                        $output['loaded']->offset[] = $message_reaction['group_message_reaction_id'];

                        $output['content'][$i] = new stdClass();
                        $output['content'][$i]->title = $message_reaction['display_name'];
                        $output['content'][$i]->class = "user";
                        $output['content'][$i]->icon = 0;
                        $output['content'][$i]->unread = 0;

                        $reaction_id = $message_reaction['reaction_id'];
                        $reaction_img = Registry::load('config')->site_url.'assets/files/reactions/react.png';

                        if ((int)$reaction_id === 1) {
                            $reaction_img = Registry::load('config')->site_url.'assets/files/reactions/like.png';
                        } else if ((int)$reaction_id === 2) {
                            $reaction_img = Registry::load('config')->site_url.'assets/files/reactions/love.png';
                        } else if ((int)$reaction_id === 3) {
                            $reaction_img = Registry::load('config')->site_url.'assets/files/reactions/haha.png';
                        } else if ((int)$reaction_id === 4) {
                            $reaction_img = Registry::load('config')->site_url.'assets/files/reactions/wow.png';
                        } else if ((int)$reaction_id === 5) {
                            $reaction_img = Registry::load('config')->site_url.'assets/files/reactions/sad.png';
                        } else if ((int)$reaction_id === 6) {
                            $reaction_img = Registry::load('config')->site_url.'assets/files/reactions/angry.png';
                        }

                        $output['content'][$i]->image = $reaction_img;


                        $output['content'][$i]->subtitle = $message_reaction['username'];

                        $option_index = 1;

                        $output['options'][$i][$option_index] = new stdClass();
                        $output['options'][$i][$option_index]->option = Registry::load('strings')->profile;
                        $output['options'][$i][$option_index]->class = 'get_info';
                        $output['options'][$i][$option_index]->attributes['data-group_identifier'] = $data['group_id'];
                        $output['options'][$i][$option_index]->attributes['user_id'] = $message_reaction['user_id'];
                        $option_index++;

                        $i++;
                    }
                }
            }
        }
    }
}
?>