<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

$user_id = Registry::load('current_user')->id;
$group_id = 0;
$group_member_ids = array();

$super_privileges = false;

if ($force_request || role(['permissions' => ['groups' => 'super_privileges']])) {
    $super_privileges = true;
}

if ($force_request) {
    if (isset($data['user'])) {
        $columns = $join = $where = null;

        $columns = ['site_users.user_id'];
        $where["OR"] = ["site_users.username" => $data['user'], "site_users.email_address" => $data['user']];
        $where["LIMIT"] = 1;

        $site_user = DB::connect()->select('site_users', $columns, $where);

        if (isset($site_user[0])) {
            $data['user_id'] = $site_user[0]['user_id'];
        } else {
            $result = array();
            $result['success'] = false;
            $result['error_message'] = Registry::load('strings')->account_not_found;
            $result['error_key'] = 'account_not_found';
            $result['error_variables'] = [];
            return;
        }
    }

    if (isset($data['group'])) {
        $columns = $join = $where = null;

        $columns = ['groups.group_id'];
        $where["OR"] = ["groups.group_id" => $data['group'], "groups.slug" => $data['group']];
        $where["LIMIT"] = 1;

        $find_group = DB::connect()->select('groups', $columns, $where);

        if (isset($find_group[0])) {
            $data['group_id'] = $find_group[0]['group_id'];
        } else {
            $result = array();
            $result['success'] = false;
            $result['error_message'] = 'Group Not Found';
            $result['error_key'] = 'group_not_found';
            $result['error_variables'] = [];
            return;
        }
    }
}

if (isset($data['group_id'])) {
    $group_id = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
}

if (!empty($group_id)) {
    $columns = $join = $where = null;
    $columns = [
        'groups.unleavable', 'group_roles.group_role_attribute',
        'group_members.group_role_id'
    ];

    $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => $user_id]];
    $join["[>]group_roles"] = ['group_members.group_role_id' => 'group_role_id'];

    $where["groups.group_id"] = $group_id;

    if (!$super_privileges) {
        $where["group_members.user_id"] = $user_id;
    }

    $where["LIMIT"] = 1;

    $group_info = DB::connect()->select('groups', $join, $columns, $where);

    if (isset($group_info[0])) {
        $group_info = $group_info[0];

        if ($super_privileges && isset($data['group_member_id'])) {
            if (!is_array($data['group_member_id'])) {
                $data["group_member_id"] = filter_var($data["group_member_id"], FILTER_SANITIZE_NUMBER_INT);
                $group_member_ids[] = $data["group_member_id"];
            } else {
                $group_member_ids = array_filter($data["group_member_id"], 'ctype_digit');
            }
        } elseif ($super_privileges || role(['permissions' => ['group_members' => 'remove_group_members'], 'group_role_id' => $group_info['group_role_id']])) {
            if (isset($data['user_id'])) {
                $user_id = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);

                if (!empty($user_id)) {
                    $columns = $join = $where = null;

                    $columns = ['group_members.group_role_id'];

                    $where["group_members.group_id"] = $group_id;
                    $where["group_members.user_id"] = $user_id;
                    $where["LIMIT"] = 1;

                    $member_info = DB::connect()->select('group_members', $columns, $where);

                    if (!isset($member_info[0])) {
                        $user_id = null;
                        $result['error_key'] = 'not_a_member';
                    }
                }
            }
        } elseif ($group_info['group_role_attribute'] === 'banned_users') {
            $user_id = null;
        } elseif (!role(['permissions' => ['groups' => 'leave_group']])) {
            $user_id = null;
        } elseif (!empty($group_info['unleavable'])) {
            $user_id = null;
        }

        if ($super_privileges && !empty($group_member_ids)) {
            DB::connect()->delete("group_members", [
                "group_id" => $group_id,
                "group_member_id" => $group_member_ids
            ]);

            $total_members = DB::connect()->count("group_members", ["group_id" => $group_id]);

            DB::connect()->update("groups", ["total_members" => $total_members], ["group_id" => $group_id]);

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = ['group_members', 'groups'];
        } elseif (!empty($user_id)) {
            DB::connect()->delete("group_members", [
                "group_id" => $group_id,
                "user_id" => $user_id
            ]);

            $total_members = DB::connect()->count("group_members", ["group_id" => $group_id]);

            DB::connect()->update("groups", ["total_members" => $total_members], ["group_id" => $group_id]);

            $system_message = array();

            if ((int)$user_id !== (int)Registry::load('current_user')->id) {
                if (isset(Registry::load('settings')->system_messages_groups->on_removal_from_group)) {
                    $system_message = [
                        'message' => 'removed_from_group',
                        'removed_by' => Registry::load('current_user')->id,
                        'user_id' => $user_id
                    ];
                }
            } elseif (isset(Registry::load('settings')->system_messages_groups->on_leaving_group_chat)) {
                $system_message = [
                    'message' => 'left_group',
                    'user_id' => $user_id
                ];
            }


            if (!empty($system_message)) {
                $system_message = json_encode($system_message);

                DB::connect()->insert("group_messages", [
                    "system_message" => 1,
                    "original_message" => 'system_message',
                    "filtered_message" => $system_message,
                    "group_id" => $group_id,
                    "user_id" => $user_id,
                    "created_on" => Registry::load('current_user')->time_stamp,
                    "updated_on" => Registry::load('current_user')->time_stamp,
                ]);

                DB::connect()->update("groups", ["updated_on" => Registry::load('current_user')->time_stamp], ['group_id' => $group_id]);
            }

            $result = array();
            $result['success'] = true;

            if ((int)$user_id !== (int)Registry::load('current_user')->id) {
                $result['todo'] = 'reload';
                $result['reload'] = ['group_members', 'groups'];

                if (isset($data['info_box'])) {
                    $result['info_box']['group_id'] = $group_id;
                }
            } else {
                $result['todo'] = 'refresh';
            }
        }
    }
}
