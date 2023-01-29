<?php

use Medoo\Medoo;

$super_privileges = false;

if (role(['permissions' => ['groups' => 'super_privileges']])) {
    $super_privileges = true;
}

$columns = $join = $where = null;
$sql_statement = '';

$columns = [
    'groups.group_id', 'groups.name', 'groups.slug', 'groups.secret_group',
    'groups.unleavable', 'groups.password', 'groups.pin_group',
    'groups.suspended'
];

if (Registry::load('current_user')->logged_in) {

    $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => Registry::load('current_user')->id]];
    $join["[>]group_roles"] = ["group_members.group_role_id" => "group_role_id"];
    $join["[>]groups(group_info)"] = ["groups.group_id" => "group_id", "AND" => ["group_members.group_role_id[!]" => NULL]];

    $columns[] = 'group_members.group_role_id';
    $columns[] = 'group_members.last_read_message_id';
    $columns[] = 'group_roles.group_role_attribute';
    $columns[] = 'group_info.updated_on(last_updated)';

    $sql_statement .= '(SELECT count(<group_message_id>) FROM <group_messages> ';

    $sql_statement .= 'LEFT JOIN <site_users_blacklist> AS blacklist ON <group_messages.user_id> = blacklist.blacklisted_user_id ';
    $sql_statement .= 'AND blacklist.user_id = '.Registry::load('current_user')->id.' ';
    $sql_statement .= 'LEFT JOIN <site_users_blacklist> AS blocked ON <group_messages.user_id> = blocked.user_id ';
    $sql_statement .= 'AND blocked.blacklisted_user_id = '.Registry::load('current_user')->id.' ';

    $sql_statement .= 'WHERE <group_members.last_read_message_id> IS NOT NULL ';
    $sql_statement .= 'AND ((blacklist.ignore IS NULL OR blacklist.ignore = 0) ';

    if (!$super_privileges) {
        $sql_statement .= 'AND (blocked.block IS NULL OR blocked.block = 0)';
    }

    $sql_statement .= 'AND (blacklist.block IS NULL OR blacklist.block = 0)) ';
    $sql_statement .= 'AND  <group_messages.user_id> != '.Registry::load('current_user')->id.' ';
    $sql_statement .= 'AND <group_id>=<groups.group_id> AND <group_message_id> > <group_members.last_read_message_id>)';

    $columns['unread_messages'] = Medoo::raw($sql_statement);

    if ($data["sortby"] === 'members_asc' || $data["sortby"] === 'members_desc') {
        $columns[] = 'groups.total_members';
    }
} else {
    $data["sortby"] = $data["filter"] = 0;
}


if (!Registry::load('current_user')->logged_in && Registry::load('settings')->view_groups_without_login === 'enable') {

    $where["AND"] = [
        "OR" => [
            "groups.password(password_null)" => null,
            "groups.password(password_empty)" => '',
            "groups.password(password_zero)" => "0"
        ],
        "groups.secret_group" => "0"
    ];

} else if (role(['permissions' => ['groups' => ['view_public_groups', 'view_secret_groups', 'view_password_protected_groups']]])) {

    $where["groups.group_id[!]"] = 0;

} else if (role(['permissions' => ['groups' => ['view_password_protected_groups', 'view_secret_groups']]])) {

    if (role(['permissions' => ['groups' => ['view_joined_groups']]])) {
        $where["AND"] = [
            "OR" => [
                "group_members.group_role_id[!]" => null,
                "groups.password[!](password_null)" => null,
                "groups.password[!](password_empty)" => '',
                "groups.password[!](password_zero)" => "0",
                "groups.secret_group[!]" => "0"
            ]
        ];
    } else {
        $where["AND"] = [
            "OR" => [
                "groups.password[!](password_null)" => null,
                "groups.password[!](password_empty)" => '',
                "groups.password[!](password_zero)" => "0",
                "groups.secret_group[!]" => "0"
            ]
        ];
    }

} else if (role(['permissions' => ['groups' => ['view_public_groups', 'view_password_protected_groups']]])) {
    if (role(['permissions' => ['groups' => ['view_joined_groups']]])) {
        $where["AND"] = [
            "OR" => [
                "group_members.group_role_id[!]" => null,
                "groups.secret_group" => "0"
            ]
        ];
    } else {
        $where["AND"] = ["groups.secret_group" => "0"];
    }

} else if (role(['permissions' => ['groups' => ['view_public_groups', 'view_secret_groups']]])) {
    if (role(['permissions' => ['groups' => ['view_joined_groups']]])) {
        $where["AND"] = [
            "OR" => [
                "groups.password(password_null)" => null,
                "groups.password(password_zero)" => "0",
                "groups.password(password_empty)" => '',
                "group_members.group_role_id[!]" => null,
            ]
        ];
    } else {
        $where["AND"] = [
            "OR" => [
                "groups.password(password_null)" => null,
                "groups.password(password_empty)" => '',
                "groups.password(password_zero)" => "0"
            ]
        ];

    }

} else if (role(['permissions' => ['groups' => 'view_password_protected_groups']])) {
    if (role(['permissions' => ['groups' => ['view_joined_groups']]])) {
        $where["AND"] = [
            "OR" => [
                "groups.password[!](password_null)" => null,
                "groups.password[!](password_empty)" => '',
                "groups.password[!](password_zero)" => "0",
                "group_members.group_role_id[!]" => null,
            ],
            "OR #second_query" => [
                "group_members.group_role_id[!]" => null,
                "groups.secret_group" => "0"
            ]
        ];
    } else {
        $where["AND"] = [
            "OR #first_query" => [
                "groups.password[!](password_null)" => null,
                "groups.password[!](password_empty)" => '',
                "groups.password[!](password_zero)" => "0"
            ],
            "groups.secret_group" => "0"
        ];
    }
} else if (role(['permissions' => ['groups' => 'view_secret_groups']])) {

    if (role(['permissions' => ['groups' => ['view_joined_groups']]])) {
        $where["AND"] = [
            "OR" => [
                "group_members.group_role_id[!]" => null,
                "groups.secret_group[!]" => "0"
            ]
        ];
    } else {
        $where["groups.secret_group[!]"] = "0";
    }

} else if (role(['permissions' => ['groups' => 'view_public_groups']])) {

    if (role(['permissions' => ['groups' => ['view_joined_groups']]])) {
        $where["AND"] = [
            "OR #first_query" => [
                "group_members.group_role_id[!]" => null,
                "groups.password(password_null)" => null,
                "groups.password(password_empty)" => '',
                "groups.password(password_zero)" => "0"
            ],
            "OR #second_query" => [
                "group_members.group_role_id[!]" => null,
                "groups.secret_group" => "0"
            ]
        ];
    } else {
        $where["AND"] = [
            "OR" => [
                "groups.password(password_null)" => null,
                "groups.password(password_empty)" => '',
                "groups.password(password_zero)" => "0"
            ],
            "groups.secret_group" => "0"
        ];
    }
} else {
    if (role(['permissions' => ['groups' => ['view_joined_groups']]])) {
        $where["group_members.group_role_id[!]"] = null;
    } else {
        $where["groups.group_id(disable_view_groups)"] = 0;
    }
}


if (!$super_privileges) {
    $where["groups.suspended"] = 0;
}

if (!empty($data["offset"])) {
    $data["offset"] = array_map('intval', explode(',', $data["offset"]));
    $where["groups.group_id[!]"] = $data["offset"];
}

if (!empty($data["search"])) {
    $where["AND #search_query"]["OR"] = ["groups.name[~]" => $data["search"], "groups.slug[~]" => $data["search"]];
}


$where["LIMIT"] = Registry::load('settings')->records_per_call;



if ($data["filter"] === 'joined') {
    $where["group_members.group_role_id[!]"] = null;
} elseif ($data["filter"] === 'unjoined') {
    $where["group_members.group_role_id"] = null;
}

if ($data["sortby"] === 'name_asc') {
    $where["ORDER"] = ["groups.name" => "ASC"];
} elseif ($data["sortby"] === 'name_desc') {
    $where["ORDER"] = ["groups.name" => "DESC"];
} elseif ($data["sortby"] === 'members_asc') {
    $where["GROUP"] = ["groups.group_id", "group_members.group_role_id", "group_members.last_read_message_id"];
    $where["ORDER"] = ["groups.total_members" => "ASC"];
} elseif ($data["sortby"] === 'members_desc') {
    $where["GROUP"] = ["groups.group_id", "group_members.group_role_id"];
    $where["ORDER"] = ["groups.total_members" => "DESC"];
} else {
    if (Registry::load('current_user')->logged_in) {
        $where["GROUP"] = ["groups.group_id", "group_members.group_role_id", "group_members.last_read_message_id"];
        $where["ORDER"] = [
            "groups.pin_group" => "DESC",
            "last_updated" => "DESC",
        ];
    } else {
        $where["ORDER"] = ["groups.pin_group" => "DESC"];
    }
}

if (!empty($join)) {
    $groups = DB::connect()->select('groups', $join, $columns, $where);
} else {
    $groups = DB::connect()->select('groups', $columns, $where);
}


$i = 1;
$output = array();
$output['loaded'] = new stdClass();
$output['loaded']->title = Registry::load('strings')->groups;
$output['loaded']->offset = array();

if (!empty($data["offset"])) {
    $output['loaded']->offset = $data["offset"];
}

if (role(['permissions' => ['groups' => 'create_groups']])) {
    $output['todo'] = new stdClass();
    $output['todo']->class = 'load_form';
    $output['todo']->title = Registry::load('strings')->create_group;
    $output['todo']->attributes['form'] = 'groups';
}

if ($super_privileges) {
    $output['multiple_select'] = new stdClass();
    $output['multiple_select']->title = Registry::load('strings')->delete;
    $output['multiple_select']->attributes['class'] = 'ask_confirmation';
    $output['multiple_select']->attributes['data-remove'] = 'groups';
    $output['multiple_select']->attributes['multi_select'] = 'group_id';
    $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
    $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
    $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
}


if (Registry::load('current_user')->logged_in) {
    $output['filters'][1] = new stdClass();
    $output['filters'][1]->filter = Registry::load('strings')->all;
    $output['filters'][1]->class = 'load_aside';
    $output['filters'][1]->attributes['load'] = 'groups';

    $output['filters'][2] = new stdClass();
    $output['filters'][2]->filter = Registry::load('strings')->joined;
    $output['filters'][2]->class = 'load_aside';
    $output['filters'][2]->attributes['load'] = 'groups';
    $output['filters'][2]->attributes['filter'] = 'joined';

    $output['filters'][3] = new stdClass();
    $output['filters'][3]->filter = Registry::load('strings')->unjoined;
    $output['filters'][3]->class = 'load_aside';
    $output['filters'][3]->attributes['load'] = 'groups';
    $output['filters'][3]->attributes['filter'] = 'unjoined';
}

if (Registry::load('current_user')->logged_in) {
    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'groups';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->name;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'groups';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->name;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'groups';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    $output['sortby'][4] = new stdClass();
    $output['sortby'][4]->sortby = Registry::load('strings')->members;
    $output['sortby'][4]->class = 'load_aside sort_asc';
    $output['sortby'][4]->attributes['load'] = 'groups';
    $output['sortby'][4]->attributes['sort'] = 'members_asc';

    $output['sortby'][5] = new stdClass();
    $output['sortby'][5]->sortby = Registry::load('strings')->members;
    $output['sortby'][5]->class = 'load_aside sort_desc';
    $output['sortby'][5]->attributes['load'] = 'groups';
    $output['sortby'][5]->attributes['sort'] = 'members_desc';
}

foreach ($groups as $group) {
    $output['loaded']->offset[] = $group['group_id'];

    if (!isset($group['group_role_attribute'])) {
        $group['group_role_attribute'] = null;
    }

    $output['content'][$i] = new stdClass();
    $output['content'][$i]->image = get_image(['from' => 'groups/icons', 'search' => $group['group_id']]);
    $output['content'][$i]->title = $group['name'];
    $output['content'][$i]->class = "group_conversation";
    $output['content'][$i]->subtitle = Registry::load('strings')->public_group;
    $output['content'][$i]->icon = 0;
    $output['content'][$i]->unread = 0;
    $output['content'][$i]->identifier = $group['group_id'];
    $output['content'][$i]->attributes = ['group_id' => $group['group_id'], 'stopPropagation' => true];

    if (!empty($group['pin_group']) && empty($data['sortby'])) {
        $output['content'][$i]->subtitle = $output['content'][$i]->icon_text = Registry::load('strings')->pinned_group;
        $output['content'][$i]->icon = 'bi-check2-square';
    } else if (!empty($group['password'])) {
        $output['content'][$i]->subtitle = $output['content'][$i]->icon_text = Registry::load('strings')->protected_group;
    } else if (!empty($group['secret_group'])) {
        $output['content'][$i]->subtitle = $output['content'][$i]->icon_text = Registry::load('strings')->secret_group;
    } else if (!empty($group['unleavable'])) {
        $output['content'][$i]->subtitle = $output['content'][$i]->icon_text = Registry::load('strings')->unleavable_group;
    }

    if ($data["sortby"] === 'members_asc' || $data["sortby"] === 'members_desc') {
        $output['content'][$i]->subtitle = $group['total_members'].' '.Registry::load('strings')->members;
    }

    if (isset($group['group_role_id']) && !empty($group['group_role_id']) && $group['group_role_attribute'] === 'banned_users') {
        $output['content'][$i]->subtitle = Registry::load('strings')->banned;
    }

    if (Registry::load('current_user')->logged_in) {
        if (empty($data["filter"]) && empty($data["sortby"]) && empty($data["search"])) {
            if (isset($group['group_role_id']) && !empty($group['group_role_id']) && $group['group_role_attribute'] !== 'banned_users') {

                if (isset($group['unread_messages']) && !empty($group['unread_messages'])) {
                    $output['content'][$i]->unread = abbreviateNumber($group['unread_messages']);
                }
            }
        }
    }

    $option_index = 1;




    if (!$super_privileges && empty($group['group_role_id']) && !empty($group['password'])) {
        if (role(['permissions' => ['groups' => 'join_group']])) {
            $output['content'][$i]->class .= " load_form";
            $output['content'][$i]->attributes['form'] = 'join_group';
            $output['content'][$i]->attributes['data-group_id'] = $group['group_id'];
        } else {
            $output['content'][$i]->class .= " load_conversation";
        }
    } else {
        if (Registry::load('current_user')->logged_in) {
            if (!$super_privileges && !isset($group['group_role_id']) || !$super_privileges && empty($group['group_role_id'])) {
                if (isset(Registry::load('settings')->view_public_group_messages_non_member) && Registry::load('settings')->view_public_group_messages_non_member === 'enable') {
                    $output['content'][$i]->class .= " load_conversation";
                } else {
                    unset($output['content'][$i]->attributes['stopPropagation']);
                }
            } else {
                $output['content'][$i]->class .= " load_conversation";
            }

        } else {
            $output['content'][$i]->class .= " load_conversation";
        }
    }

    if (isset($group['group_role_id']) && !empty($group['group_role_id']) || $super_privileges) {
        if ($super_privileges || role(['permissions' => ['group' => 'edit_group'], 'group_role_id' => $group['group_role_id']])) {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->edit_group;
            $output['options'][$i][$option_index]->class = 'load_form';
            $output['options'][$i][$option_index]->attributes['form'] = 'groups';
            $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
            $option_index++;
        }
    }

    if (role(['permissions' => ['super_privileges' => 'group_headers']])) {
        $output['options'][$i][$option_index] = new stdClass();
        $output['options'][$i][$option_index]->option = Registry::load('strings')->group_header;
        $output['options'][$i][$option_index]->class = 'load_form';
        $output['options'][$i][$option_index]->attributes['form'] = 'group_headers';
        $output['options'][$i][$option_index]->attributes['enlarge'] = true;
        $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
        $option_index++;
    }

    if (isset($group['suspended']) && !empty($group['suspended'])) {
        $output['content'][$i]->subtitle = Registry::load('strings')->suspended;
    }

    if ($super_privileges) {
        if (isset($group['suspended']) && !empty($group['suspended'])) {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->unsuspend;
            $output['options'][$i][$option_index]->class = 'ask_confirmation';
            $output['options'][$i][$option_index]->attributes['data-update'] = 'group_status';
            $output['options'][$i][$option_index]->attributes['data-unsuspend_group_id'] = $group['group_id'];
            $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
            $option_index++;
        } else {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->suspend;
            $output['options'][$i][$option_index]->class = 'ask_confirmation';
            $output['options'][$i][$option_index]->attributes['data-update'] = 'group_status';
            $output['options'][$i][$option_index]->attributes['data-suspend_group_id'] = $group['group_id'];
            $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
            $option_index++;
        }
    }

    if (!isset($group['group_role_id']) || empty($group['group_role_id'])) {
        if (role(['permissions' => ['groups' => 'join_group']])) {

            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->join_group;

            if (empty($group['password']) || $super_privileges) {
                if (isset(Registry::load('settings')->group_join_confirmation) && Registry::load('settings')->group_join_confirmation === 'enable') {
                    $output['options'][$i][$option_index]->class = 'ask_confirmation';
                    $output['options'][$i][$option_index]->attributes['data-add'] = 'group_members';
                    $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
                    $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_join;
                    $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                    $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                } else {
                    $output['options'][$i][$option_index]->class = 'api_request';
                    $output['options'][$i][$option_index]->attributes['data-add'] = 'group_members';
                    $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
                }
            } else {
                $output['options'][$i][$option_index]->class = 'load_form';
                $output['options'][$i][$option_index]->attributes['form'] = 'join_group';
                $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
            }
            $option_index++;
        }
    }

    if (isset($group['group_role_id']) && !empty($group['group_role_id'])) {
        if ($super_privileges || role(['permissions' => ['groups' => 'leave_group']]) && empty($group['unleavable']) && $group['group_role_attribute'] !== 'banned_users') {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->leave_group;
            $output['options'][$i][$option_index]->class = 'ask_confirmation';
            $output['options'][$i][$option_index]->attributes['data-remove'] = 'group_members';
            $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
            $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_leave;
            $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
            $option_index++;
        }
    }

    $output['options'][$i][$option_index] = new stdClass();
    $output['options'][$i][$option_index]->option = Registry::load('strings')->group_info;
    $output['options'][$i][$option_index]->class = 'get_info force_request';
    $output['options'][$i][$option_index]->attributes['group_id'] = $group['group_id'];
    $option_index++;

    if (role(['permissions' => ['groups' => 'embed_group']])) {
        if ($super_privileges || isset($group['group_role_id']) && !empty($group['group_role_id'])) {

            $embed_group = false;

            if ($super_privileges || $group['group_role_attribute'] === 'administrators') {
                $embed_group = true;
            } else if (empty($group['password']) && empty($group['secret_group'])) {
                $embed_group = true;
            }

            if ($embed_group) {
                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->embed;
                $output['options'][$i][$option_index]->class = 'load_form';
                $output['options'][$i][$option_index]->attributes['form'] = 'embed_group';
                $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
                $option_index++;
            }
        }
    }

    if (role(['permissions' => ['badges' => 'assign']])) {
        $output['options'][$i][$option_index] = new stdClass();
        $output['options'][$i][$option_index]->option = Registry::load('strings')->assign_badges;
        $output['options'][$i][$option_index]->class = 'load_aside';
        $output['options'][$i][$option_index]->attributes['load'] = 'badges';
        $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
        $option_index++;
    }

    if ($super_privileges || isset($group['group_role_id']) && !empty($group['group_role_id']) && $group['group_role_attribute'] !== 'banned_users') {

        if ($super_privileges || role(['permissions' => ['group_members' => 'view_group_members'], 'group_role_id' => $group['group_role_id']])) {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->members;
            $output['options'][$i][$option_index]->class = 'load_aside';
            $output['options'][$i][$option_index]->attributes['load'] = 'group_members';
            $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
            $option_index++;
        }

        if (role(['permissions' => ['groups' => 'invite_users']])) {
            $invite_users = false;

            if ($super_privileges || $group['group_role_attribute'] === 'administrators') {
                $invite_users = true;
            } else if (empty($group['password']) && empty($group['secret_group'])) {
                $invite_users = true;
            }

            if ($invite_users) {
                $output['options'][$i][$option_index] = new stdClass();
                $output['options'][$i][$option_index]->option = Registry::load('strings')->invite_users;
                $output['options'][$i][$option_index]->class = 'load_form';
                $output['options'][$i][$option_index]->attributes['form'] = 'invite_group_members';
                $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
                $option_index++;
            }
        }


        if ($super_privileges && role(['permissions' => ['groups' => 'add_site_members']]) || role(['permissions' => ['groups' => 'add_site_members']]) && empty($group['password']) && empty($group['secret_group'])) {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->add_members;
            $output['options'][$i][$option_index]->class = 'load_aside';
            $output['options'][$i][$option_index]->attributes['load'] = 'non_group_members';
            $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
            $option_index++;
        }


        if (role(['permissions' => ['groups' => 'clear_chat_history']])) {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->clear_chat;
            $output['options'][$i][$option_index]->class = 'ask_confirmation';
            $output['options'][$i][$option_index]->attributes['data-remove'] = 'group_messages';
            $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
            $output['options'][$i][$option_index]->attributes['data-clear_chat_history'] = true;
            $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
            $option_index++;
        }

        if ($super_privileges || role(['permissions' => ['group' => 'delete_group'], 'group_role_id' => $group['group_role_id']])) {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->delete_group;
            $output['options'][$i][$option_index]->class = 'ask_confirmation';
            $output['options'][$i][$option_index]->attributes['data-remove'] = 'groups';
            $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
            $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_delete;
            $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
            $option_index++;
        }

        if ($super_privileges || role(['permissions' => ['messages' => 'delete_messages'], 'group_role_id' => $group['group_role_id']])) {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->delete_messages;
            $output['options'][$i][$option_index]->class = 'ask_confirmation';
            $output['options'][$i][$option_index]->attributes['data-remove'] = 'group_messages';
            $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
            $output['options'][$i][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_delete_all_messages;
            $output['options'][$i][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
            $option_index++;
        }

        if (role(['permissions' => ['groups' => 'export_chat']])) {
            $output['options'][$i][$option_index] = new stdClass();
            $output['options'][$i][$option_index]->option = Registry::load('strings')->export_chat;
            $output['options'][$i][$option_index]->class = 'download_file';
            $output['options'][$i][$option_index]->attributes['download'] = 'messages';
            $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
            $option_index++;
        }
    }

    if (role(['permissions' => ['complaints' => 'report']])) {
        $output['options'][$i][$option_index] = new stdClass();
        $output['options'][$i][$option_index]->option = Registry::load('strings')->report;
        $output['options'][$i][$option_index]->class = 'load_form';
        $output['options'][$i][$option_index]->attributes['form'] = 'complaint';
        $output['options'][$i][$option_index]->attributes['data-group_id'] = $group['group_id'];
        $option_index++;
    }

    $i++;
}