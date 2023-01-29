<?php

include 'fns/filters/load.php';

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';
$ban_till = null;
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
            $data["user_id"] = $site_user[0]['user_id'];
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
    if (isset($data['group_role'])) {
        $columns = $join = $where = null;
        $columns = ['group_roles.group_role_id'];
        $where["group_roles.group_role_id"] = $data['group_role'];

        $where["LIMIT"] = 1;

        $group_role_id = DB::connect()->select('group_roles', $columns, $where);

        if (isset($group_role_id[0])) {
            $data['group_role_id'] = $group_role_id[0]['group_role_id'];
        }
    }
}

if (isset($data['group_id'])) {
    $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data['group_id'])) {
        $columns = $where = $join = null;
        $columns = [
            'groups.group_id', 'group_members.group_role_id',
            'group_roles.group_role_attribute'
        ];

        $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => Registry::load('current_user')->id]];
        $join["[>]group_roles"] = ["group_members.group_role_id" => "group_role_id"];

        $where['groups.group_id'] = $data['group_id'];

        $group_info = DB::connect()->select('groups', $join, $columns, $where);

        if (isset($group_info[0])) {
            $group_info = $group_info[0];
        } else {
            return false;
        }

        if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
            if (isset($data['ban_user_id'])) {
                $data["user_id"] = filter_var($data["ban_user_id"], FILTER_SANITIZE_NUMBER_INT);
                $data["group_role_id"] = null;

                if (!$super_privileges && !role(['permissions' => ['group_members' => 'ban_users_from_group'], 'group_role_id' => $group_info['group_role_id']])) {
                    return false;
                }

                if (!empty($data['user_id'])) {
                    $banned_role_id = DB::connect()->select('group_roles', ['group_role_id'], ['group_roles.group_role_attribute' => 'banned_users']);

                    if (isset($banned_role_id[0])) {
                        $data["group_role_id"] = $banned_role_id[0]['group_role_id'];
                    }
                }
            } elseif (isset($data['temporary_ban_user_id'])) {
                $data["user_id"] = filter_var($data["temporary_ban_user_id"], FILTER_SANITIZE_NUMBER_INT);
                $data["group_role_id"] = null;

                if (!$super_privileges && !role(['permissions' => ['group_members' => 'ban_users_from_group'], 'group_role_id' => $group_info['group_role_id']])) {
                    return false;
                }

                if (!empty($data['user_id'])) {
                    $banned_role_id = DB::connect()->select('group_roles', ['group_role_id'], ['group_roles.group_role_attribute' => 'banned_users']);

                    if (isset($banned_role_id[0])) {
                        $data["group_role_id"] = $banned_role_id[0]['group_role_id'];
                    }

                    if (isset($data['ban_till'])) {
                        if (!validate_date($data['ban_till'], 'Y-m-d')) {
                            $data['ban_till'] = null;
                        }
                    }

                    if (!isset($data['ban_till']) || empty($data['ban_till'])) {
                        $result['error_message'] = Registry::load('strings')->invalid_value;
                        $result['error_variables'] = ['ban_till'];
                        $result['error_key'] = 'invalid_value';
                        $data['user_id'] = null;
                    } else {
                        $ban_till = $data['ban_till'];
                    }
                }
            } elseif (isset($data['unban_user_id'])) {
                $data["user_id"] = filter_var($data["unban_user_id"], FILTER_SANITIZE_NUMBER_INT);
                $data["group_role_id"] = null;

                if (!$super_privileges && !role(['permissions' => ['group_members' => 'unban_users_from_group'], 'group_role_id' => $group_info['group_role_id']])) {
                    return false;
                }
                if (!empty($data['user_id'])) {
                    $where = null;
                    $where['AND'] = ['group_members.group_id' => $data['group_id'], 'group_members.user_id' => $data['user_id']];
                    $previous_role_id = DB::connect()->select('group_members', ['previous_group_role_id'], $where);

                    if (isset($previous_role_id[0])) {
                        $data["group_role_id"] = $previous_role_id[0]['previous_group_role_id'];
                    }
                }
            } elseif (!$super_privileges && !role(['permissions' => ['group_members' => 'manage_user_roles'], 'group_role_id' => $group_info['group_role_id']])) {
                return false;
            }


            if (!isset($data['group_role_id']) || empty($data["group_role_id"])) {
                $result['error_message'] = Registry::load('strings')->invalid_value;
                $result['error_key'] = 'invalid_value';
                $result['error_variables'] = ['group_role_id'];
            }

            if (isset($data['user_id']) && isset($data["group_role_id"])) {
                $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
                $data["group_role_id"] = filter_var($data["group_role_id"], FILTER_SANITIZE_NUMBER_INT);

                if (!empty($data['user_id']) && !empty($data['group_role_id'])) {
                    $columns = $where = $join = null;

                    $columns = ['group_members.group_member_id', 'group_members.group_role_id', 'group_roles.group_role_attribute'];
                    $where['AND'] = ['group_members.group_id' => $data['group_id'], 'group_members.user_id' => $data['user_id']];
                    $join["[>]group_roles"] = ["group_members.group_role_id" => "group_role_id"];

                    $group_member = DB::connect()->select('group_members', $join, $columns, $where);


                    if (isset($group_member[0])) {
                        $group_member = $group_member[0];

                        if (!$super_privileges) {
                            if (isset($data['ban_user_id']) || isset($data['unban_user_id']) || isset($data['temporary_ban_user_id'])) {
                                if (isset($group_member['group_role_attribute']) && $group_member['group_role_attribute'] === 'administrators') {
                                    $result['error_message'] = Registry::load('strings')->permission_denied;
                                    $result['error_key'] = 'permission_denied';
                                    return false;
                                }
                            }
                        }

                        if ((int)$group_member['group_role_id'] !== (int)$data['group_role_id']) {
                            $columns = $join = $where = null;
                            $where['AND'] = ['group_members.group_id' => $data['group_id'], 'group_members.user_id' => $data['user_id']];

                            DB::connect()->update("group_members", [
                                "group_role_id" => $data['group_role_id'],
                                "banned_till" => $ban_till,
                                "previous_group_role_id" => $group_member['group_role_id'],
                                "updated_on" => Registry::load('current_user')->time_stamp,
                            ], $where);

                            if (isset($data['ban_user_id']) && isset(Registry::load('settings')->system_messages_groups->on_getting_banned_from_group)) {
                                $system_message = [
                                    'message' => 'banned_from_group',
                                    'user_id' => $data['user_id']
                                ];
                            } elseif (isset($data['unban_user_id']) && isset(Registry::load('settings')->system_messages_groups->on_getting_unbanned_from_group)) {
                                $system_message = [
                                    'message' => 'unbanned_from_group',
                                    'user_id' => $data['user_id']
                                ];
                            } elseif (isset($data['temporary_ban_user_id']) && isset(Registry::load('settings')->system_messages_groups->on_getting_temporarily_banned_from_group)) {
                                $system_message = [
                                    'message' => 'temporarily_banned_from_group',
                                    'user_id' => $data['user_id']
                                ];
                            }

                            if (isset($system_message) && !empty($system_message)) {
                                $system_message = json_encode($system_message);
                                DB::connect()->insert("group_messages", [
                                    "system_message" => 1,
                                    "original_message" => 'system_message',
                                    "filtered_message" => $system_message,
                                    "group_id" => $data['group_id'],
                                    "user_id" => $data['user_id'],
                                    "created_on" => Registry::load('current_user')->time_stamp,
                                    "updated_on" => Registry::load('current_user')->time_stamp,
                                ]);

                                DB::connect()->update("groups", ["updated_on" => Registry::load('current_user')->time_stamp], ['group_id' => $data['group_id']]);
                            }
                        }

                        $result = array();
                        $result['success'] = true;
                        $result['todo'] = 'reload';
                        $result['reload'] = 'group_members';

                        if (isset($data['info_box'])) {
                            $result['info_box']['user_id'] = $data['user_id'];
                            $result['info_box']['group_identifier'] = $data['group_id'];
                        }
                    }
                }
            }
        }
    }
}
