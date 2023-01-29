<?php

if (role(['permissions' => ['groups' => 'add_site_members']])) {
    if (isset($data["group_id"])) {

        $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data["group_id"])) {

            $columns = $join = $where = null;
            $columns = [
                'groups.name(group_name)', 'group_roles.group_role_attribute',
                'group_members.group_role_id'
            ];

            $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => Registry::load('current_user')->id]];
            $join["[>]group_roles"] = ["group_members.group_role_id" => "group_role_id"];

            $where["groups.group_id"] = $data["group_id"];
            $where["LIMIT"] = 1;

            $group_info = DB::connect()->select('groups', $join, $columns, $where);

            if (isset($group_info[0])) {
                $group_info = $group_info[0];
            } else {
                return;
            }

            $group_id = $data["group_id"];

            $super_privileges = false;

            if (role(['permissions' => ['groups' => 'super_privileges']])) {
                $super_privileges = true;
            }

            if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                if ($super_privileges || isset($group_info['group_role_attribute']) && $group_info['group_role_attribute'] !== 'banned_users') {

                    $columns = $join = $where = null;

                    $columns = [
                        'site_users.user_id', 'site_users.display_name', 'site_users.username', 'site_users.email_address',
                    ];

                    $join["[>]group_members"] = ["site_users.user_id" => "user_id", "AND" => ["group_members.group_id" => $group_id]];

                    $where["group_members.group_member_id"] = NULL;

                    if (!empty($data["offset"])) {
                        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
                        $where["site_users.user_id[!]"] = $data["offset"];
                    }

                    if (!empty($data["search"])) {
                        $where["AND #search_query"]["OR"] = [
                            "site_users.display_name[~]" => $data["search"],
                            "site_users.username" => $data["search"],
                            "site_users.email_address" => $data["search"]
                        ];
                    }

                    $where["LIMIT"] = Registry::load('settings')->records_per_call;

                    if ($data["sortby"] === 'nameasc') {
                        $where["ORDER"] = ["site_users.display_name" => "ASC"];
                    } else if ($data["sortby"] === 'namedesc') {
                        $where["ORDER"] = ["site_users.display_name" => "DESC"];
                    } else {
                        $where["ORDER"] = ["site_users.user_id" => "DESC"];
                    }

                    $group_members = DB::connect()->select('site_users', $join, $columns, $where);

                    $i = 1;
                    $output = array();
                    $output['loaded'] = new stdClass();
                    $output['loaded']->title = Registry::load('strings')->add_members;
                    $output['loaded']->selectable = true;
                    $output['loaded']->select = 'user_id';
                    $output['loaded']->loaded = 'group_members';
                    $output['loaded']->null_search = true;
                    $output['loaded']->offset = array();

                    if (!empty($data["offset"])) {
                        $output['loaded']->offset = $data["offset"];
                    }

                    if ($super_privileges) {
                        $output['multiple_select'] = new stdClass();
                        $output['multiple_select']->title = Registry::load('strings')->add_to_group;
                        $output['multiple_select']->icon = 'bi bi-plus';
                        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
                        $output['multiple_select']->attributes['data-add'] = 'multiple_group_members';
                        $output['multiple_select']->attributes['data-group_id'] = $group_id;
                        $output['multiple_select']->attributes['multi_select'] = 'user_id';
                        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
                        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
                        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
                    }

                    foreach ($group_members as $group_member) {

                        $output['loaded']->offset[] = $group_member['user_id'];

                        $output['content'][$i] = new stdClass();
                        $output['content'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $group_member['user_id'], 'gravatar' => $group_member['email_address']]);
                        $output['content'][$i]->title = $group_member['display_name'];
                        $output['content'][$i]->class = "user";
                        $output['content'][$i]->icon = 0;
                        $output['content'][$i]->unread = 0;
                        $output['content'][$i]->identifier = $group_member['user_id'];

                        $output['content'][$i]->subtitle = $group_member['username'];

                        $output['options'][$i][1] = new stdClass();
                        $output['options'][$i][1]->option = Registry::load('strings')->add_to_group;
                        $output['options'][$i][1]->class = 'api_request';
                        $output['options'][$i][1]->attributes['data-add'] = 'group_members';
                        $output['options'][$i][1]->attributes['data-group_id'] = $group_id;
                        $output['options'][$i][1]->attributes['data-user_id'] = $group_member['user_id'];

                        $i++;
                    }
                }
            }
        }
    }
}
?>