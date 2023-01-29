<?php

$group_id = 0;

$super_privileges = false;

if (role(['permissions' => ['groups' => 'super_privileges']])) {
    $super_privileges = true;
}

if (isset($data['group_id'])) {
    $data['group_id'] = filter_var($data['group_id'], FILTER_SANITIZE_NUMBER_INT);
    if (!empty($data['group_id'])) {
        $group_id = $data['group_id'];
    }
}

if (!empty($group_id)) {
    $columns = $where = $join = null;

    $columns = [
        'groups.group_id', 'groups.name', 'groups.slug', 'groups.total_members',
        'groups.description', 'groups.created_on', 'groups.password', 'groups.unleavable', 'groups.suspended', 'groups.secret_group'
    ];

    if (Registry::load('current_user')->logged_in) {
        $columns[] = 'group_members.group_role_id';
        $columns[] = 'group_roles.group_role_attribute';
        $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => Registry::load('current_user')->id]];
        $join["[>]group_roles"] = ["group_members.group_role_id" => "group_role_id"];
    }

    $where["groups.group_id"] = $group_id;
    $where["LIMIT"] = 1;

    if (!empty($join)) {
        $group = DB::connect()->select('groups', $join, $columns, $where);
    } else {
        $group = DB::connect()->select('groups', $columns, $where);
    }

    if (isset($group[0])) {
        $output = array();
        $group = $group[0];

        if (!$super_privileges && isset($group['suspended']) && !empty($group['suspended'])) {
            return;
        }

        $output['loaded'] = new stdClass();
        $output['loaded']->heading = $group['name'];
        $output['loaded']->cover_pic = get_image(['from' => 'groups/cover_pics', 'search' => $group['group_id']]);
        $output['loaded']->image = get_image(['from' => 'groups/icons', 'search' => $group['group_id']]);
        $output['loaded']->subheading = '';

        if (!empty($group['description'])) {
            $output['loaded']->subheading = $group['description'];
        } else if (!empty($group['slug'])) {
            $output['loaded']->subheading = $group['slug'];
        }


        if (Registry::load('current_user')->logged_in) {
            if (role(['permissions' => ['groups' => 'join_group']])) {
                if (!isset($group['group_role_id']) || empty($group['group_role_id'])) {
                    $output['button'] = new stdClass();
                    $output['button']->title = Registry::load('strings')->join_group;

                    if (empty($group['password']) || $super_privileges) {

                        if (isset(Registry::load('settings')->group_join_confirmation) && Registry::load('settings')->group_join_confirmation === 'enable') {
                            $output['button']->attributes['class'] = 'ask_confirmation';
                            $output['button']->attributes['data-add'] = 'group_members';
                            $output['button']->attributes['data-group_id'] = $group['group_id'];
                            $output['button']->attributes['confirmation'] = Registry::load('strings')->confirm_join;
                            $output['button']->attributes['submit_button'] = Registry::load('strings')->yes;
                            $output['button']->attributes['cancel_button'] = Registry::load('strings')->no;
                            $output['button']->attributes['column'] = 'fourth';
                        } else {
                            $output['button']->attributes['class'] = 'api_request';
                            $output['button']->attributes['data-add'] = 'group_members';
                            $output['button']->attributes['data-group_id'] = $group['group_id'];
                        }
                    } else {
                        $output['button']->attributes['class'] = 'load_form';
                        $output['button']->attributes['form'] = 'join_group';
                        $output['button']->attributes['data-group_id'] = $group['group_id'];
                    }
                }
            }

            if (role(['permissions' => ['groups' => 'leave_group']])) {
                if (isset($group['group_role_id']) && !empty($group['group_role_id'])) {
                    if ($super_privileges || role(['permissions' => ['groups' => 'leave_group']]) && empty($group['unleavable']) && $group['group_role_attribute'] !== 'banned_users') {
                        $output['button'] = new stdClass();
                        $output['button']->title = Registry::load('strings')->leave_group;
                        $output['button']->attributes['class'] = 'ask_confirmation';
                        $output['button']->attributes['data-remove'] = 'group_members';
                        $output['button']->attributes['data-info_box'] = true;
                        $output['button']->attributes['data-group_id'] = $group['group_id'];
                        $output['button']->attributes['confirmation'] = Registry::load('strings')->confirm_leave;
                        $output['button']->attributes['submit_button'] = Registry::load('strings')->yes;
                        $output['button']->attributes['cancel_button'] = Registry::load('strings')->no;
                        $output['button']->attributes['column'] = 'fourth';
                    }
                }
            }
        }

        if (Registry::load('current_user')->logged_in) {

            $option_index = 1;

            $output['options'][$option_index] = new stdClass();
            $output['options'][$option_index]->option = Registry::load('strings')->view_group;
            $output['options'][$option_index]->class = 'load_conversation info_panel_message_button';
            $output['options'][$option_index]->attributes['group_id'] = $group['group_id'];
            $option_index++;


            if (isset($group['group_role_id']) && !empty($group['group_role_id']) && $group['group_role_attribute'] !== 'banned_users' || $super_privileges) {

                if ($super_privileges || role(['permissions' => ['group' => 'edit_group'], 'group_role_id' => $group['group_role_id']])) {
                    $output['options'][$option_index] = new stdClass();
                    $output['options'][$option_index]->option = Registry::load('strings')->edit_group;
                    $output['options'][$option_index]->class = 'load_form';
                    $output['options'][$option_index]->attributes['form'] = 'groups';
                    $output['options'][$option_index]->attributes['data-group_id'] = $group['group_id'];
                    $option_index++;
                }

                if (role(['permissions' => ['super_privileges' => 'group_headers']])) {
                    $output['options'][$option_index] = new stdClass();
                    $output['options'][$option_index]->option = Registry::load('strings')->group_header;
                    $output['options'][$option_index]->class = 'load_form';
                    $output['options'][$option_index]->attributes['form'] = 'group_headers';
                    $output['options'][$option_index]->attributes['enlarge'] = true;
                    $output['options'][$option_index]->attributes['data-group_id'] = $group['group_id'];
                    $option_index++;
                }

                if ($super_privileges) {
                    if (isset($group['suspended']) && !empty($group['suspended'])) {
                        $output['options'][$option_index] = new stdClass();
                        $output['options'][$option_index]->option = Registry::load('strings')->unsuspend;
                        $output['options'][$option_index]->class = 'ask_confirmation';
                        $output['options'][$option_index]->attributes['data-update'] = 'group_status';
                        $output['options'][$option_index]->attributes['data-unsuspend_group_id'] = $group['group_id'];
                        $output['options'][$option_index]->attributes['data-info_box'] = true;
                        $output['options'][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
                        $output['options'][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                        $output['options'][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                        $output['options'][$option_index]->attributes['column'] = 'fourth';
                        $option_index++;
                    } else {
                        $output['options'][$option_index] = new stdClass();
                        $output['options'][$option_index]->option = Registry::load('strings')->suspend;
                        $output['options'][$option_index]->class = 'ask_confirmation';
                        $output['options'][$option_index]->attributes['data-update'] = 'group_status';
                        $output['options'][$option_index]->attributes['data-suspend_group_id'] = $group['group_id'];
                        $output['options'][$option_index]->attributes['data-info_box'] = true;
                        $output['options'][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
                        $output['options'][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                        $output['options'][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                        $output['options'][$option_index]->attributes['column'] = 'fourth';
                        $option_index++;
                    }
                }

                if (role(['permissions' => ['badges' => 'assign']])) {
                    $output['options'][$option_index] = new stdClass();
                    $output['options'][$option_index]->option = Registry::load('strings')->assign_badges;
                    $output['options'][$option_index]->class = 'load_aside';
                    $output['options'][$option_index]->attributes['load'] = 'badges';
                    $output['options'][$option_index]->attributes['data-group_id'] = $group['group_id'];
                    $option_index++;
                }

                if (role(['permissions' => ['groups' => 'embed_group']])) {
                    if ($super_privileges || isset($group['group_role_id']) && !empty($group['group_role_id'])) {

                        $embed_group = false;

                        if ($super_privileges || $group['group_role_attribute'] === 'administrators') {
                            $embed_group = true;
                        } else if (empty($group['password']) && empty($group['secret_group'])) {
                            $embed_group = true;
                        }

                        if ($embed_group) {
                            $output['options'][$option_index] = new stdClass();
                            $output['options'][$option_index]->option = Registry::load('strings')->embed;
                            $output['options'][$option_index]->class = 'load_form';
                            $output['options'][$option_index]->attributes['form'] = 'embed_group';
                            $output['options'][$option_index]->attributes['data-group_id'] = $group['group_id'];
                            $option_index++;
                        }
                    }
                }

                if (role(['permissions' => ['groups' => 'invite_users']])) {
                    $invite_users = false;

                    if ($super_privileges || $group['group_role_attribute'] === 'administrators') {
                        $invite_users = true;
                    } else if (empty($group['password']) && empty($group['secret_group'])) {
                        $invite_users = true;
                    }

                    if ($invite_users) {
                        $output['options'][$option_index] = new stdClass();
                        $output['options'][$option_index]->option = Registry::load('strings')->invite_users;
                        $output['options'][$option_index]->class = 'load_form';
                        $output['options'][$option_index]->attributes['form'] = 'invite_group_members';
                        $output['options'][$option_index]->attributes['data-group_id'] = $group['group_id'];
                        $option_index++;
                    }
                }

                if ($super_privileges && role(['permissions' => ['groups' => 'add_site_members']]) || role(['permissions' => ['groups' => 'add_site_members']]) && empty($group['password']) && empty($group['secret_group'])) {
                    $output['options'][$option_index] = new stdClass();
                    $output['options'][$option_index]->option = Registry::load('strings')->add_members;
                    $output['options'][$option_index]->class = 'load_aside';
                    $output['options'][$option_index]->attributes['load'] = 'non_group_members';
                    $output['options'][$option_index]->attributes['data-group_id'] = $group['group_id'];
                    $option_index++;
                }

                if (role(['permissions' => ['groups' => 'clear_chat_history']])) {
                    $output['options'][$option_index] = new stdClass();
                    $output['options'][$option_index]->option = Registry::load('strings')->clear_chat;
                    $output['options'][$option_index]->class = 'ask_confirmation';
                    $output['options'][$option_index]->attributes['data-remove'] = 'group_messages';
                    $output['options'][$option_index]->attributes['data-group_id'] = $group['group_id'];
                    $output['options'][$option_index]->attributes['data-clear_chat_history'] = true;
                    $output['options'][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
                    $output['options'][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                    $output['options'][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                    $output['options'][$option_index]->attributes['column'] = 'fourth';
                    $option_index++;
                }

                if ($super_privileges || role(['permissions' => ['group' => 'delete_group'], 'group_role_id' => $group['group_role_id']])) {
                    $output['options'][$option_index] = new stdClass();
                    $output['options'][$option_index]->option = Registry::load('strings')->delete_group;
                    $output['options'][$option_index]->class = 'ask_confirmation';
                    $output['options'][$option_index]->attributes['data-remove'] = 'groups';
                    $output['options'][$option_index]->attributes['data-group_id'] = $group['group_id'];
                    $output['options'][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_delete;
                    $output['options'][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                    $output['options'][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                    $output['options'][$option_index]->attributes['column'] = 'fourth';
                    $option_index++;
                }


                if ($super_privileges || role(['permissions' => ['messages' => 'delete_messages'], 'group_role_id' => $group['group_role_id']])) {
                    $output['options'][$option_index] = new stdClass();
                    $output['options'][$option_index]->option = Registry::load('strings')->delete_messages;
                    $output['options'][$option_index]->class = 'ask_confirmation';
                    $output['options'][$option_index]->attributes['data-remove'] = 'group_messages';
                    $output['options'][$option_index]->attributes['data-group_id'] = $group['group_id'];
                    $output['options'][$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_delete_all_messages;
                    $output['options'][$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                    $output['options'][$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                    $output['options'][$option_index]->attributes['column'] = 'fourth';
                    $option_index++;
                }

                if (role(['permissions' => ['groups' => 'export_chat']])) {
                    $output['options'][$option_index] = new stdClass();
                    $output['options'][$option_index]->option = Registry::load('strings')->export_chat;
                    $output['options'][$option_index]->class = 'download_file';
                    $output['options'][$option_index]->attributes['download'] = 'messages';
                    $output['options'][$option_index]->attributes['data-group_id'] = $group['group_id'];
                    $option_index++;
                }

            }

            if (role(['permissions' => ['complaints' => 'report']])) {
                $output['options'][$option_index] = new stdClass();
                $output['options'][$option_index]->option = Registry::load('strings')->report;
                $output['options'][$option_index]->class = 'load_form';
                $output['options'][$option_index]->attributes['form'] = 'complaint';
                $output['options'][$option_index]->attributes['data-group_id'] = $group['group_id'];
                $option_index++;
            }

        }

        $columns = $where = $join = null;
        $columns = ['custom_fields.string_constant(field_name)', 'custom_fields.field_type', 'custom_fields.required', 'custom_fields_values.field_value'];
        $join["[>]custom_fields_values"] = ["custom_fields.field_id" => "field_id", "AND" => ["group_id" => $group['group_id']]];

        $where['AND'] = ['custom_fields.field_category' => 'group', 'custom_fields.disabled' => 0, 'custom_fields.show_on_info_page' => 1];
        $where["ORDER"] = ["custom_fields.field_id" => "ASC"];

        $custom_fields = DB::connect()->select('custom_fields', $join, $columns, $where);

        if ($super_privileges && !empty($group['description']) && !empty($group['slug'])) {
            $output['content'][3] = new stdClass();
            $output['content'][3]->field['title'] = Registry::load('strings')->slug;
            $output['content'][3]->field['value'] = $group['slug'];
        }

        $i = 4;
        foreach ($custom_fields as $custom_field) {
            $field_name = $custom_field['field_name'];
            if (isset($custom_field['field_value']) && !empty($custom_field['field_value'])) {

                $output['content'][$i] = new stdClass();
                $output['content'][$i]->field['title'] = Registry::load('strings')->$field_name;

                if ($custom_field['field_type'] === 'dropdown') {
                    $dropdownoptions = $field_name.'_options';

                    if (isset(Registry::load('strings')->$dropdownoptions)) {

                        $field_options = json_decode(Registry::load('strings')->$dropdownoptions);
                        if (!empty($field_options)) {
                            $find = $custom_field['field_value'];
                            if (isset($field_options->$find)) {
                                $output['content'][$i]->field['value'] = $field_options->$find;
                            }
                        }

                    }
                } else if ($custom_field['field_type'] === 'datefield') {

                    if (Registry::load('settings')->dateformat === 'mdy_format') {
                        $output['content'][$i]->field['value'] = date("M-d-Y", strtotime($custom_field['field_value']));
                    } else if (Registry::load('settings')->dateformat === 'ymd_format') {
                        $output['content'][$i]->field['value'] = date("Y-M-d", strtotime($custom_field['field_value']));
                    } else {
                        $output['content'][$i]->field['value'] = date("d-M-Y", strtotime($custom_field['field_value']));
                    }
                } else if ($custom_field['field_type'] === 'link') {

                    $field_value = $custom_field['field_value'];

                    if (mb_strlen($field_value) > 50) {
                        $field_value = parse_url($field_value);
                        $field_value = $field_value['scheme']."://".$field_value['host'];
                    }

                    $custom_field['field_value'] = '<a href="'.$custom_field['field_value'].'" rel="nofollow noreferrer noopener" target="_blank">'.$field_value.'</a>';
                    $output['content'][$i]->field['value'] = $custom_field['field_value'];
                } else {
                    $output['content'][$i]->field['value'] = $custom_field['field_value'];
                }
                $i++;
            }
        }

        if (Registry::load('current_user')->logged_in) {
            if ($super_privileges || isset($group['group_role_id']) && !empty($group['group_role_id']) && $group['group_role_attribute'] !== 'banned_users') {

                $dropdown_index = 1;

                $records = array();

                if ($super_privileges || role(['permissions' => ['group' => 'view_shared_files'], 'group_role_id' => $group['group_role_id']])) {
                    $records['dropdown'][$dropdown_index]['title'] = Registry::load('strings')->media;
                    $records['dropdown'][$dropdown_index]['load'] = 'group_media_files';
                    $records['dropdown'][$dropdown_index]['attributes']['data-group_id'] = $group_id;
                    $dropdown_index++;
                }

                if ($super_privileges || role(['permissions' => ['group' => 'view_shared_files'], 'group_role_id' => $group['group_role_id']])) {
                    $records['dropdown'][$dropdown_index]['title'] = Registry::load('strings')->other_files;
                    $records['dropdown'][$dropdown_index]['load'] = 'group_other_files';
                    $dropdown_index++;
                }

                if ($super_privileges || role(['permissions' => ['group' => 'view_shared_links'], 'group_role_id' => $group['group_role_id']])) {
                    $records['dropdown'][$dropdown_index]['title'] = Registry::load('strings')->links;
                    $records['dropdown'][$dropdown_index]['load'] = 'group_shared_links';
                    $dropdown_index++;
                }

                if (!empty($records)) {
                    $records['identifier'] = 'group_records_'.$group['group_id'];
                    $output['content'][$i] = new stdClass();
                    $output['content'][$i]->field['multiple_records'] = $records;
                    $i++;
                }

            }

            if (!empty($group['created_on'])) {

                $created_on['date'] = $group['created_on'];
                $created_on['auto_format'] = true;

                $output['content'][$i] = new stdClass();
                $output['content'][$i]->field['title'] = Registry::load('strings')->created_on;
                $output['content'][$i]->field['value'] = get_date($created_on);
                $i++;
            }
        }

        $view_group_members = false;

        if (!isset($group['group_role_id']) || empty($group['group_role_id'])) {
            if (isset(Registry::load('settings')->hide_group_member_list_from_non_members) && Registry::load('settings')->hide_group_member_list_from_non_members === 'no') {
                $view_group_members = true;
            }
        }

        if ($view_group_members || $super_privileges || isset($group['group_role_id']) && !empty($group['group_role_id']) && $group['group_role_attribute'] !== 'banned_users') {

            if ($view_group_members || $super_privileges || role(['permissions' => ['group_members' => 'view_group_members'], 'group_role_id' => $group['group_role_id']])) {

                $members = array();

                $columns = $where = $join = null;
                $columns = [
                    'group_members.user_id', 'site_users.display_name'
                ];

                $join["[>]site_users"] = ["group_members.user_id" => "user_id"];

                $where["group_members.group_id"] = $group_id;
                $where["ORDER"] = ["group_members.group_role_id" => "ASC"];
                $where["LIMIT"] = 5;

                $group_members = DB::connect()->select('group_members', $join, $columns, $where);

                $i = 1;

                foreach ($group_members as $group_member) {
                    $members[$i]['title'] = $group_member['display_name'];
                    $members[$i]['image'] = get_image(['from' => 'site_users/profile_pics', 'search' => $group_member['user_id']]);
                    $members[$i]['attributes']['class'] = 'get_info hide_tooltip_on_click';
                    $members[$i]['attributes']['user_id'] = $group_member['user_id'];
                    $i = $i+1;
                }

                if (count($members) > 0) {

                    $members[$i]['title'] = Registry::load('strings')->view_all;
                    $members[$i]['image'] = Registry::load('config')->site_url.'assets/files/defaults/view_all.png';
                    $members[$i]['attributes']['class'] = 'load_aside hide_tooltip_on_click';
                    $members[$i]['attributes']['load'] = 'group_members';
                    $members[$i]['attributes']['data-group_id'] = $group_id;

                    $output['content'][2] = new stdClass();
                    $output['content'][2]->field['title'] = Registry::load('strings')->members;
                    $output['content'][2]->field['images'] = $members;
                    $output['content'][2]->field['class'] = 'rounded';

                    if (!empty($group['total_members'])) {
                        $output['content'][2]->field['title'] .= ' ['.$group['total_members'].']';
                    }
                }
            }
        }

        $columns = $join = $where = null;
        $columns = [
            'badges.string_constant', 'badges_assigned.badge_id',
        ];

        $join["[>]badges"] = ["badges_assigned.badge_id" => "badge_id"];

        $where["badges_assigned.group_id"] = $group_id;
        $where["badges.disabled"] = 0;
        $where["badges.badge_category"] = 'group';

        $group_badges = DB::connect()->select('badges_assigned', $join, $columns, $where);
        $badge_index = 1;
        $badges = array();

        foreach ($group_badges as $group_badge) {
            $badge_string_constant = $group_badge['string_constant'];
            $badges[$badge_index]['title'] = Registry::load('strings')->$badge_string_constant;
            $badges[$badge_index]['image'] = get_image(['from' => 'badges', 'search' => $group_badge['badge_id']]);
            $badge_index++;
        }

        if (!empty($badges)) {
            $output['content'][1] = new stdClass();
            $output['content'][1]->field['title'] = Registry::load('strings')->badges;
            $output['content'][1]->field['images'] = $badges;
        }

    }
}

?>