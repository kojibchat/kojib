<?php

if (role(['permissions' => ['groups' => 'mention_users']])) {

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

            $super_privileges = false;

            if (role(['permissions' => ['groups' => 'super_privileges']])) {
                $super_privileges = true;
            }

            if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                if ($super_privileges || role(['permissions' => ['messages' => 'mention_users'], 'group_role_id' => $group_info['group_role_id']])) {

                    $group_id = $data["group_id"];

                    $columns = $join = $where = null;
                    $columns = [
                        'site_users.display_name', 'site_users.username', 'group_members.user_id', 'site_users.email_address'
                    ];

                    $join["[>]site_users"] = ["group_members.user_id" => "user_id"];
                    $join["[>]site_users_settings"] = ["group_members.user_id" => "user_id"];
                    $join["[>]group_roles"] = ["group_members.group_role_id" => "group_role_id"];

                    $where["group_members.group_id"] = $group_id;
                    $where["site_users_settings.deactivated"] = 0;


                    if (isset($data["search"]) && !empty($data["search"])) {
                        $where["AND #search_query"]["OR"] = [
                            "site_users.display_name[~]" => $data["search"],
                            "site_users.username" => $data["search"],
                            "site_users.email_address" => $data["search"]
                        ];
                    }

                    $where["LIMIT"] = 10;

                    $where["ORDER"] = ["group_members.group_member_id" => "DESC"];

                    $group_members = DB::connect()->select('group_members', $join, $columns, $where);
                    $output = array();
                    $i = 0;

                    foreach ($group_members as $group_member) {
                        $output[$i] = new stdClass();
                        $output[$i]->name = $group_member['display_name'];
                        $output[$i]->username = $group_member['username'];
                        $output[$i]->user_id = $group_member['user_id'];
                        $output[$i]->avatar = get_image(['from' => 'site_users/profile_pics', 'search' => $group_member['user_id'], 'gravatar' => $group_member['email_address']]);
                        $i = $i+1;
                    }
                }
            }
        }
    }
}
?>