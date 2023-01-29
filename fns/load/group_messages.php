<?php

if (isset($data["group_id"])) {

    if ($data["group_id"] !== 'all') {
        $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
    } else {
        if (!role(['permissions' => ['super_privileges' => 'monitor_group_chats']])) {
            $data["group_id"] = 0;
            $output['loaded'] = new stdClass();
            $output['loaded']->title = Registry::load('strings')->permission_denied;
            $output['loaded']->image = Registry::load('config')->site_url.'assets/files/defaults/error.png';


            $output['error_message'] = new stdClass();
            $output['error_message']->title = Registry::load('strings')->permission_denied;
            $output['error_message']->subtitle = Registry::load('strings')->access_denied_message;
            $output['error_message']->image = Registry::load('config')->site_url.'assets/files/defaults/denied.png';
        }
    }

    if (!empty($data["group_id"])) {

        $super_privileges = false;
        $delete_message_time_limit = role(['find' => 'delete_message_time_limit']);
        $react_messages = false;

        $permission = [
            'mention_users' => false,
            'reply_messages' => false,
            'forward_messages' => false,
            'report_messages' => false,
            'view_reactions' => false,
            'check_read_receipts' => false,
            'delete_messages' => false,
            'check_message_time' => false,
            'download_attachments' => false,
        ];

        if (role(['permissions' => ['groups' => 'super_privileges']])) {
            $super_privileges = true;
        }

        if (role(['permissions' => ['complaints' => 'report']])) {
            $permission['report_messages'] = true;
        }

        $force_open_group = false;

        $output['loaded'] = new stdClass();
        $output['loaded']->title = Registry::load('strings')->not_found;
        $output['loaded']->image = Registry::load('config')->site_url.'assets/files/defaults/conversation.png';

        if ($data["group_id"] !== 'all') {
            $columns = $join = $where = null;
            $columns = [
                'groups.name(group_name)', 'group_roles.group_role_attribute', 'groups.who_all_can_send_messages',
                'groups.slug', 'groups.secret_group', 'groups.secret_code', 'groups.password', 'groups.suspended', 'groups.updated_on',
                'group_members.group_role_id', 'group_members.banned_till', 'groups.suspended',
                'groups.meta_title', 'group_members.load_message_id_from', 'groups.group_header_status',
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

            if (!$super_privileges) {

                if (isset($_COOKIE['current_group_secret_code']) && !empty($_COOKIE['current_group_secret_code'])) {
                    if ($_COOKIE['current_group_secret_code'] === $group_info['secret_code']) {
                        $force_open_group = true;
                    }
                }

                if (!empty($group_info['suspended'])) {
                    $output['loaded'] = new stdClass();
                    $output['loaded']->title = Registry::load('strings')->suspended;
                    $output['loaded']->image = Registry::load('config')->site_url.'assets/files/defaults/error.png';

                    $output['error_message'] = new stdClass();
                    $output['error_message']->title = Registry::load('strings')->suspended;
                    $output['error_message']->subtitle = Registry::load('strings')->group_suspended;
                    $output['error_message']->image = Registry::load('config')->site_url.'assets/files/defaults/denied.png';
                    return;
                }

                if (isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                    if (isset($group_info['group_role_attribute']) && $group_info['group_role_attribute'] === 'banned_users') {

                        $output['loaded'] = new stdClass();
                        $output['loaded']->title = Registry::load('strings')->banned;
                        $output['loaded']->image = Registry::load('config')->site_url.'assets/files/defaults/error.png';

                        $output['error_message'] = new stdClass();
                        $output['error_message']->image = Registry::load('config')->site_url.'assets/files/defaults/banned.png';

                        if (!empty($group_info['banned_till'])) {

                            $current_time_stamp = Registry::load('current_user')->time_stamp;
                            $time_diff_in_seconds = strtotime($current_time_stamp) - strtotime($group_info['banned_till']);
                            if ($time_diff_in_seconds < 5) {
                                $banned_till['date'] = $group_info['banned_till'];
                                $banned_till['auto_format'] = true;
                                $banned_till['timezone'] = Registry::load('current_user')->time_zone;
                                $banned_till = get_date($banned_till);

                                $output['error_message']->subtitle = Registry::load('strings')->temporarily_banned_from_group_message;
                                $output['error_message']->title = Registry::load('strings')->banned_till;
                                $output['error_message']->title .= ' ['.$banned_till.']';
                                return;
                            } else {

                                unset($output['error_message']);

                                include_once('fns/update/load.php');

                                $unban_user = array();
                                $unban_user['update'] = 'group_user_role';
                                $unban_user['group_id'] = $data["group_id"];
                                $unban_user['unban_user_id'] = Registry::load('current_user')->id;
                                $unban_user['return'] = true;
                                update($unban_user, ['force_request' => true]);

                            }
                        } else {
                            $output['error_message']->title = Registry::load('strings')->banned;
                            $output['error_message']->subtitle = Registry::load('strings')->banned_from_group_message;
                            return;
                        }
                    }
                } else {
                    if (!$force_open_group && !empty($group_info['secret_group']) && !role(['permissions' => ['groups' => 'view_secret_groups']])) {
                        $output['loaded'] = new stdClass();
                        $output['loaded']->title = Registry::load('strings')->permission_denied;
                        $output['loaded']->image = Registry::load('config')->site_url.'assets/files/defaults/error.png';

                        $output['error_message'] = new stdClass();
                        $output['error_message']->title = Registry::load('strings')->permission_denied;
                        $output['error_message']->subtitle = Registry::load('strings')->access_denied_message;
                        $output['error_message']->image = Registry::load('config')->site_url.'assets/files/defaults/denied.png';
                        return;
                    } else if (!empty($group_info['password'])) {
                        $output['loaded'] = new stdClass();
                        $output['loaded']->title = Registry::load('strings')->permission_denied;
                        $output['loaded']->image = Registry::load('config')->site_url.'assets/files/defaults/error.png';

                        $output['error_message'] = new stdClass();
                        $output['error_message']->title = Registry::load('strings')->permission_denied;
                        $output['error_message']->subtitle = Registry::load('strings')->access_denied_message;
                        $output['error_message']->image = Registry::load('config')->site_url.'assets/files/defaults/denied.png';
                        return;
                    }
                }
            }

        } else {
            $group_info['group_name'] = Registry::load('strings')->monitor_group_chats;
        }

        if (Registry::load('current_user')->logged_in) {

            if (!$super_privileges && !isset($group_info['group_role_id']) || !$super_privileges && empty($group_info['group_role_id'])) {

                if (isset(Registry::load('settings')->view_public_group_messages_non_member) && Registry::load('settings')->view_public_group_messages_non_member !== 'enable') {

                    $output['loaded'] = new stdClass();
                    $output['loaded']->title = Registry::load('strings')->permission_denied;
                    $output['loaded']->image = Registry::load('config')->site_url.'assets/files/defaults/error.png';

                    $output['error_message'] = new stdClass();
                    $output['error_message']->title = Registry::load('strings')->permission_denied;
                    $output['error_message']->subtitle = Registry::load('strings')->access_denied_non_member_message;
                    $output['error_message']->image = Registry::load('config')->site_url.'assets/files/defaults/denied.png';
                    return;
                }
            }
        }

        if (!isset($group_info['group_name']) && $data["group_id"] !== 'all') {

            $output['error_message'] = new stdClass();
            $output['error_message']->title = Registry::load('strings')->no_conversation_found;
            $output['error_message']->subtitle = Registry::load('strings')->no_conversation_found_subtitle;
            $output['error_message']->image = Registry::load('config')->site_url.'assets/files/defaults/conversations.png';

        } else {

            if ($data["group_id"] !== 'all') {
                $output['loaded']->image = get_image(['from' => 'groups/icons', 'search' => $data["group_id"]]);
            }

            $output['loaded']->title = $group_info['group_name'];
            $output['loaded']->group_id = $data["group_id"];
            $output['loaded']->background_image = get_image(['from' => 'groups/backgrounds', 'search' => $data["group_id"]]);

            if ($data["group_id"] !== 'all') {

                if (!empty($group_info['group_header_status'])) {

                    $group_header_file = 'assets/group_headers/group_'.$data["group_id"].'.php';

                    if (file_exists($group_header_file)) {
                        $output['loaded']->group_header = true;
                    }
                }

                if (isset($group_info['meta_title']) && !empty($group_info['meta_title'])) {
                    $output['loaded']->browser_title = $group_info['meta_title'].' - '.Registry::load('settings')->site_name;
                } else {
                    $output['loaded']->browser_title = $group_info['group_name'].' - '.Registry::load('settings')->site_name;
                }

                if (isset($group_info['slug']) && !empty($group_info['slug'])) {
                    $output['loaded']->browser_address_bar = Registry::load('config')->site_url.$group_info['slug'].'/';
                } else {
                    $output['loaded']->browser_address_bar = Registry::load('config')->site_url.'group/'.$data["group_id"].'/';
                }

                if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                    if ($super_privileges && role(['permissions' => ['groups' => 'react_messages']]) || role(['permissions' => ['groups' => 'react_messages']]) && role(['permissions' => ['messages' => 'react_messages'], 'group_role_id' => $group_info['group_role_id']])) {
                        $output['loaded']->react_messages = true;
                        $react_messages = true;
                    }
                }

                if (!Registry::load('current_user')->logged_in) {

                    $output['info_box'] = new stdClass();
                    $output['info_box']->content = Registry::load('strings')->not_logged_in_message;
                    $output['info_box']->attributes = [
                        'class' => 'open_link',
                        'link' => Registry::load('config')->site_url.'entry/',
                    ];

                    if (isset($output['loaded']->browser_address_bar) && !empty($output['loaded']->browser_address_bar)) {
                        $output['info_box']->attributes['link'] .= '?redirect='.urlencode($output['loaded']->browser_address_bar);
                    }
                } else {
                    if (!$super_privileges) {
                        if (!isset($group_info['group_role_id']) || empty($group_info['group_role_id'])) {
                            if (role(['permissions' => ['groups' => 'join_group']])) {
                                $output['info_box'] = new stdClass();
                                $output['info_box']->content = Registry::load('strings')->not_a_group_member_message;

                                if (isset(Registry::load('settings')->group_join_confirmation) && Registry::load('settings')->group_join_confirmation === 'enable') {
                                    $output['info_box']->attributes = [
                                        'class' => 'ask_confirmation',
                                        'column' => 'second',
                                        'data-add' => 'group_members',
                                        'data-group_id' => $data["group_id"],
                                        'confirmation' => Registry::load('strings')->confirm_join,
                                        'submit_button' => Registry::load('strings')->yes,
                                        'cancel_button' => Registry::load('strings')->no
                                    ];
                                } else {
                                    $output['info_box']->attributes = [
                                        'class' => 'api_request',
                                        'data-add' => 'group_members',
                                        'data-group_id' => $data["group_id"]
                                    ];
                                }
                            }
                        }
                    }
                }
            }


            $last_message_id = 0;
            $log_last_seen_message_id = true;


            if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                if ($super_privileges || role(['permissions' => ['messages' => 'delete_messages'], 'group_role_id' => $group_info['group_role_id']])) {
                    $permission['delete_messages'] = true;
                    $output['loaded']->multi_select = true;
                } else if (role(['permissions' => ['messages' => 'delete_own_message'], 'group_role_id' => $group_info['group_role_id']])) {
                    $permission['delete_messages'] = true;
                    $permission['check_message_time'] = true;
                }
            }

            if ($data["group_id"] !== 'all') {

                $output['loaded']->view_info = true;


                if (role(['permissions' => ['groups' => 'check_read_receipts']])) {
                    if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                        if ($super_privileges || role(['permissions' => ['messages' => 'check_read_receipts'], 'group_role_id' => $group_info['group_role_id']])) {
                            $permission['check_read_receipts'] = true;
                        }
                    }
                }

                if (role(['permissions' => ['groups' => 'download_attachments']])) {
                    if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                        if ($super_privileges || role(['permissions' => ['messages' => 'download_attachments'], 'group_role_id' => $group_info['group_role_id']])) {
                            $permission['download_attachments'] = true;
                        }
                    }
                }


                if (role(['permissions' => ['groups' => 'send_message']])) {
                    if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                        if ($super_privileges || role(['permissions' => ['messages' => 'send_message'], 'group_role_id' => $group_info['group_role_id']])) {

                            if ($super_privileges || !empty($group_info['who_all_can_send_messages'])) {
                                if ($super_privileges || $group_info['who_all_can_send_messages'] === 'all') {
                                    $output['loaded']->messaging = true;
                                } else {
                                    $who_all_can_send_messages = json_decode($group_info['who_all_can_send_messages']);
                                    if (!empty($who_all_can_send_messages)) {
                                        if (in_array($group_info['group_role_id'], $who_all_can_send_messages)) {
                                            $output['loaded']->messaging = true;
                                        }
                                    }
                                }
                            }

                            $output['loaded']->disable_features = array();

                            if (Registry::load('settings')->gif_search_engine === 'disable') {
                                $output['loaded']->disable_features[] = 'gifs';
                            }

                            if (!role(['permissions' => ['groups' => 'attach_gifs']]) || !$super_privileges && !role(['permissions' => ['messages' => 'attach_gifs'], 'group_role_id' => $group_info['group_role_id']])) {
                                $output['loaded']->disable_features[] = 'gifs';
                            }

                            if (!role(['permissions' => ['groups' => 'send_audio_message']]) || !$super_privileges && !role(['permissions' => ['messages' => 'send_audio_message'], 'group_role_id' => $group_info['group_role_id']])) {
                                $output['loaded']->disable_features[] = 'voice_message';
                            }

                            if (!role(['permissions' => ['groups' => 'attach_stickers']]) || !$super_privileges && !role(['permissions' => ['messages' => 'attach_stickers'], 'group_role_id' => $group_info['group_role_id']])) {
                                $output['loaded']->disable_features[] = 'stickers';
                            }

                            if (!role(['permissions' => ['groups' => 'attach_files']]) || !$super_privileges && !role(['permissions' => ['messages' => 'attach_files'], 'group_role_id' => $group_info['group_role_id']])) {
                                $output['loaded']->disable_features[] = 'attach_files';
                            }

                            if (!role(['permissions' => ['groups' => 'attach_from_storage']]) || !$super_privileges && !role(['permissions' => ['messages' => 'attach_from_storage'], 'group_role_id' => $group_info['group_role_id']])) {
                                $output['loaded']->disable_features[] = 'attach_from_storage';
                            }


                            if (role(['permissions' => ['groups' => 'mention_users']])) {
                                if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                                    if ($super_privileges || role(['permissions' => ['messages' => 'mention_users'], 'group_role_id' => $group_info['group_role_id']])) {
                                        $permission['mention_users'] = true;
                                    }
                                }
                            }

                            if (role(['permissions' => ['groups' => 'view_reactions']])) {
                                if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                                    if ($super_privileges || role(['permissions' => ['messages' => 'view_reactions'], 'group_role_id' => $group_info['group_role_id']])) {
                                        $permission['view_reactions'] = true;
                                    }
                                }
                            }

                            if (role(['permissions' => ['groups' => 'reply_messages']])) {
                                if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                                    if ($super_privileges || role(['permissions' => ['messages' => 'reply_messages'], 'group_role_id' => $group_info['group_role_id']])) {
                                        $permission['reply_messages'] = true;
                                    }
                                }
                            }

                            if (role(['permissions' => ['groups' => 'forward_messages']])) {
                                if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                                    if ($super_privileges || role(['permissions' => ['messages' => 'forward_messages'], 'group_role_id' => $group_info['group_role_id']])) {
                                        $permission['forward_messages'] = true;
                                    }
                                }
                            }


                        }
                    }
                }

            } else {
                $log_last_seen_message_id = false;
            }

            $column = $join = $where = null;
            $columns = [
                'site_users.display_name', 'group_messages.group_message_id', 'group_messages.filtered_message',
                'group_messages.system_message', 'group_messages.parent_message_id', 'group_messages.attachments',
                'group_messages.link_preview', 'group_messages.created_on', 'group_messages.updated_on',
                'group_messages.user_id', 'groups.name(group_name)', 'group_messages.attachment_type',
                'reply.filtered_message(reply_message)', 'reply.attachment_type(reply_attachment_type)',
                'reply.attachments(reply_attachments)', 'attached_message_author.display_name(attached_message_author)',
                'site_users.username', 'group_messages.group_id', 'group_messages.total_reactions',
                'group_messages_reactions.reaction_id(user_reaction_id)', 'site_users.site_role_id', 'group_members.group_role_id',
                'site_users_settings.deactivated',
            ];

            $join["[>]group_messages(reply)"] = ["group_messages.parent_message_id" => "group_message_id"];
            $join["[>]group_messages_reactions"] = ["group_messages.group_message_id" => "group_message_id", "AND" => ["group_messages_reactions.user_id" => Registry::load('current_user')->id]];
            $join["[>]site_users(attached_message_author)"] = ["reply.user_id" => "user_id"];
            $join["[>]site_users"] = ["group_messages.user_id" => "user_id"];
            $join["[>]site_users_settings"] = ["group_messages.user_id" => "user_id"];
            $join["[>]group_members"] = ["group_messages.group_id" => "group_id", "group_messages.user_id" => "user_id"];
            $join["[><]groups"] = ["group_messages.group_id" => "group_id"];
            $join["[>]site_users_blacklist(blacklist)"] = ["group_messages.user_id" => "blacklisted_user_id", "AND" => ["blacklist.user_id" => Registry::load('current_user')->id]];
            $join["[>]site_users_blacklist(blocked)"] = ["group_messages.user_id" => "user_id", "AND" => ["blocked.blacklisted_user_id" => Registry::load('current_user')->id]];

            if ($data["group_id"] !== 'all') {
                $where["group_messages.group_id"] = $data["group_id"];

                if (!empty($group_info['load_message_id_from'])) {
                    $where["group_messages.group_message_id[>]"] = $group_info['load_message_id_from'];
                }
            }

            if (isset($data["message_id"])) {
                $data["message_id"] = filter_var($data["message_id"], FILTER_SANITIZE_NUMBER_INT);
                if (!empty($data["message_id"])) {
                    $where["group_messages.group_message_id"] = $data["message_id"];
                    $log_last_seen_message_id = false;
                }
            }

            if (isset($data["search"]) && !empty($data["search"])) {

                $message_id_search = filter_var($data["search"], FILTER_SANITIZE_NUMBER_INT);

                if (empty($message_id_search)) {
                    $message_id_search = 0;
                }

                if (isset($data["search_message_id"])) {
                    $where["AND #search_query"] = [
                        "group_messages.group_message_id" => $message_id_search
                    ];
                } else {
                    $where["AND #search_query"]["OR"] = [
                        "site_users.display_name[~]" => $data["search"],
                        "site_users.username[~]" => $data["search"],
                        "site_users.email_address[~]" => $data["search"],
                        "group_messages.filtered_message[~]" => $data["search"],
                        "group_messages.attachments[~]" => $data["search"],
                        "group_messages.group_message_id" => $message_id_search
                    ];
                }

                $log_last_seen_message_id = false;
            }

            if (isset($data["message_id_less_than"])) {
                $data["message_id_less_than"] = filter_var($data["message_id_less_than"], FILTER_SANITIZE_NUMBER_INT);
                if (!empty($data["message_id_less_than"])) {
                    $where["group_messages.group_message_id[<]"] = $data["message_id_less_than"];
                    $log_last_seen_message_id = false;
                }
            }

            if (isset($data["message_id_from"])) {
                $data["message_id_from"] = filter_var($data["message_id_from"], FILTER_SANITIZE_NUMBER_INT);
                if (!empty($data["message_id_from"])) {
                    $where["group_messages.group_message_id[>=]"] = $data["message_id_from"];
                }
            }

            if (isset($data["message_id_greater_than"])) {
                $data["message_id_greater_than"] = filter_var($data["message_id_greater_than"], FILTER_SANITIZE_NUMBER_INT);
                if (!empty($data["message_id_greater_than"])) {
                    $where["group_messages.group_message_id[>]"] = $data["message_id_greater_than"];
                }
            }

            if ($data["group_id"] !== 'all') {
                $check_user_black_list = true;

                if (isset($group_info['group_role_attribute']) && $group_info['group_role_attribute'] === 'administrators') {
                    $check_user_black_list = false;
                }

                if (isset($group_info['group_role_attribute']) && $group_info['group_role_attribute'] === 'moderators') {
                    $check_user_black_list = false;
                }

                if ($check_user_black_list) {
                    $where["AND"]["OR #first condition"] = ["blacklist.ignore" => NULL, "blacklist.ignore(ignored)" => 0];
                    $where["AND"]["OR #second condition"] = ["blacklist.block" => NULL, "blacklist.block(blocked)" => 0];

                    if (!$super_privileges) {
                        $where["AND"]["OR #third condition"] = ["blocked.block" => NULL, "blocked.block(blocked)" => 0];
                    }
                }
            }

            $where["ORDER"] = ['group_messages.group_message_id' => 'DESC'];
            $where["LIMIT"] = Registry::load('settings')->messages_per_call;


            $group_messages = DB::connect()->select('group_messages', $join, $columns, $where);



            $reactions = [
                1 => 'like', 2 => 'love', 3 => 'haha',
                4 => 'wow', 5 => 'sad', 6 => 'angry'
            ];

            $i = 0;

            foreach ($group_messages as $message) {

                $date['date'] = $message['created_on'];
                $date['auto_format'] = true;
                $date['include_time'] = true;
                $date['compare_with_today'] = true;
                $date['timezone'] = Registry::load('current_user')->time_zone;
                $created_on = get_date($date);

                $output['messages'][$i] = new stdClass();
                $output['messages'][$i]->time = $created_on['time'];
                $output['messages'][$i]->date = $created_on['date'];
                $output['messages'][$i]->message_label = Registry::load('strings')->message;
                $output['messages'][$i]->group_id = $message["group_id"];
                $output['messages'][$i]->own_message = false;
                $output['messages'][$i]->system_message = false;
                $output['messages'][$i]->class = 'group_message';
                $output['messages'][$i]->message_id = $message['group_message_id'];
                $output['messages'][$i]->content = $message['filtered_message'];


                if (isset(Registry::load('settings')->display_username_group_chats) && Registry::load('settings')->display_username_group_chats === 'enable') {
                    $output['messages'][$i]->posted_by = $message['username'];
                } else {
                    $output['messages'][$i]->posted_by = $message['display_name'];
                }

                if ($last_message_id < $message['group_message_id']) {
                    $last_message_id = $message['group_message_id'];
                }

                if (isset($message['site_role_id'])) {

                    $name_color = role(['find' => 'name_color', 'site_role_id' => $message['site_role_id']]);

                    if (!empty($name_color)) {
                        $output['messages'][$i]->name_color = $name_color;
                    }
                }

                $output['messages'][$i]->reactions = array();

                if ($super_privileges || $permission['view_reactions']) {

                    $output['messages'][$i]->reactions = array();

                    if (isset($message['user_reaction_id']) && !empty($message['user_reaction_id'])) {

                        $user_reaction_id = $message['user_reaction_id'];

                        if (isset($reactions[$user_reaction_id])) {
                            $output['messages'][$i]->reactions['user_reaction'] = $reactions[$user_reaction_id];
                        }
                    }

                    if (!empty($message['total_reactions'])) {

                        $message['total_reactions'] = json_decode($message['total_reactions']);

                        if (!empty($message['total_reactions'])) {
                            $output['messages'][$i]->reactions['total_reactions'] = $message['total_reactions'];
                        }
                    }
                }

                if ($created_on['date'] === 'today') {
                    $output['messages'][$i]->date = Registry::load('strings')->today;
                } else if ($created_on['date'] === 'yesterday') {
                    $output['messages'][$i]->date = Registry::load('strings')->yesterday;
                }

                if (isset($message["system_message"]) && !empty($message["system_message"])) {
                    $output['messages'][$i]->system_message = true;
                    $output['messages'][$i]->class = 'system_message';

                    if (!isset($message['deactivated']) || empty($message['deactivated']) || $super_privileges) {
                        $output['messages'][$i]->sender_user_id = $message['user_id'];
                    }

                    $system_message = json_decode($message['filtered_message']);
                    $system_language_string = $system_message->message;
                    $output['messages'][$i]->content = Registry::load('strings')->$system_language_string;

                    if ($system_message->message === 'new_badge_awarded') {
                        if ($data["group_id"] !== 'all') {
                            $output['messages'][$i]->posted_by = $group_info['group_name'];
                        } else {
                            $output['messages'][$i]->posted_by = $message['group_name'];
                        }
                    }

                } else {
                    if (isset($data["message_id"]) && !empty($data["message_id"])) {

                        if (isset($data["find_message"])) {
                            $output['messages'][$i]->class .= ' skip_message';
                        }

                        $output['messages'][$i]->highlight_message = true;
                    }

                    if ($data["group_id"] === 'all') {
                        $output['messages'][$i]->badge = ['text' => $message['group_name']];
                    } else {

                        $group_role_name = 'group_role_'.$message['group_role_id'];

                        $label = role([
                            'find' => ['label_text_color', 'label_background_color', 'show_label'],
                            'group_role_id' => $message['group_role_id']
                        ]);

                        if ($label['show_label'] === 'yes') {
                            $output['messages'][$i]->badge = [
                                'text_color' => $label['label_text_color'],
                                'background' => $label['label_background_color'],
                                'text' => Registry::load('strings')->$group_role_name
                            ];
                        }
                    }

                    if (isset($message['deactivated']) && !empty($message['deactivated']) && !$super_privileges) {
                        $output['messages'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => 0]);
                    } else {
                        $output['messages'][$i]->sender_user_id = $message['user_id'];
                        $output['messages'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $message['user_id']]);
                    }

                    $output['messages'][$i]->attachment_type = $message['attachment_type'];

                    if (isset($message['parent_message_id']) && !empty($message['parent_message_id'])) {
                        $output['messages'][$i]->parent_message_id = $message['parent_message_id'];
                        $output['messages'][$i]->attached_message_author = $message['attached_message_author'];

                        if (!empty($message['reply_attachment_type']) && !empty($message['reply_attachments'])) {

                            $reply_attachments = json_decode($message['reply_attachments']);
                            $output['messages'][$i]->reply_message = Registry::load('strings')->attachments;

                            if (isset($reply_attachments->gif_url)) {
                                $output['messages'][$i]->reply_message = Registry::load('strings')->gif;
                                $output['messages'][$i]->reply_thumbnail = $reply_attachments->gif_url;
                            } else if (isset($reply_attachments->sticker)) {
                                $output['messages'][$i]->reply_message = Registry::load('strings')->sticker;
                                $output['messages'][$i]->reply_thumbnail = Registry::load('config')->site_url.$reply_attachments->sticker;
                            } else if (isset($reply_attachments->screenshot)) {
                                $output['messages'][$i]->reply_message = Registry::load('strings')->screenshot;
                                if (file_exists($reply_attachments->thumbnail)) {
                                    $output['messages'][$i]->reply_thumbnail = $reply_attachments->thumbnail;
                                }
                            } else if (isset($reply_attachments->audio_message)) {
                                $output['messages'][$i]->reply_message = Registry::load('strings')->audio_message;
                            } else if ($message['reply_attachment_type'] === 'image_files' || $message['reply_attachment_type'] === 'video_files') {
                                $reply_attachment = reset($reply_attachments);
                                if (isset($reply_attachment->thumbnail)) {
                                    $output['messages'][$i]->reply_thumbnail = $reply_attachment->thumbnail;
                                }
                            }
                        }

                        if (!empty($message['reply_message'])) {
                            $output['messages'][$i]->reply_message = $message['reply_message'];
                        }
                    }

                    if ((int)$message['user_id'] === (int)Registry::load('current_user')->id) {
                        $output['messages'][$i]->own_message = true;
                        $output['messages'][$i]->class .= ' own_message';

                        if ($data["group_id"] !== 'all' && Registry::load('settings')->own_message_alignment === 'right') {
                            $output['messages'][$i]->class .= ' align_right';
                        }
                    } else {
                        if ($data["group_id"] !== 'all' && Registry::load('settings')->message_alignment === 'right') {
                            $output['messages'][$i]->class .= ' align_right';
                        }
                    }

                    if ($permission['check_message_time']) {
                        if ((int)$message['user_id'] !== (int)Registry::load('current_user')->id) {
                            $permission['delete_messages'] = false;
                        } else {
                            $permission['delete_messages'] = false;
                            if (!empty($delete_message_time_limit)) {

                                $to_time = strtotime($message['created_on']);
                                $from_time = strtotime("now");
                                $time_difference = round(abs($to_time - $from_time) / 60, 2);

                                if ($time_difference < $delete_message_time_limit) {
                                    $permission['delete_messages'] = true;
                                }
                            }
                        }
                    }

                    if (!empty($message['attachment_type']) && !empty($message['attachments'])) {
                        $attachments = json_decode($message['attachments']);

                        if (isset($attachments->gif_url)) {
                            $output['messages'][$i]->message_label = Registry::load('strings')->gif;
                            $output['messages'][$i]->class = $output['messages'][$i]->class.' gif';
                            $output['messages'][$i]->attachments[0]['image'] = $attachments->gif_url;
                            $output['messages'][$i]->attachments[0]['original'] = $attachments->gif_url;
                        } else if (isset($attachments->sticker)) {
                            $output['messages'][$i]->message_label = Registry::load('strings')->sticker;
                            $output['messages'][$i]->class = $output['messages'][$i]->class.' sticker';
                            $output['messages'][$i]->attachments[0]['image'] = Registry::load('config')->site_url.$attachments->sticker;
                            $output['messages'][$i]->attachments[0]['original'] = Registry::load('config')->site_url.$attachments->sticker;
                        } else if (isset($attachments->screenshot)) {
                            $output['messages'][$i]->message_label = Registry::load('strings')->screenshot;
                            $output['messages'][$i]->class = $output['messages'][$i]->class.' screenshot';
                            $output['messages'][$i]->attachments[0]['original'] = $attachments->screenshot;
                            if (file_exists($attachments->thumbnail)) {
                                $output['messages'][$i]->attachments[0]['image'] = $attachments->thumbnail;
                            } else {
                                $output['messages'][$i]->attachments[0]['image'] = Registry::load('config')->site_url.'assets/files/defaults/image_thumb.jpg';

                                if (!file_exists($attachments->screenshot)) {
                                    $output['messages'][$i]->attachments[0]['original'] = Registry::load('config')->site_url.'assets/files/defaults/image_not_found.jpg';
                                }
                            }
                        } else if ($message['attachment_type'] === 'audio_message') {
                            $output['messages'][$i]->message_label = Registry::load('strings')->audio_message;
                            $output['messages'][$i]->class = $output['messages'][$i]->class.' audio_message';
                            $output['messages'][$i]->attachments[0]['audio_file'] = Registry::load('config')->site_url.$attachments->audio_message;
                            $output['messages'][$i]->attachments[0]['file_type'] = $attachments->mime_type;
                        } else if ($message['attachment_type'] === 'url_meta') {

                            if (empty($attachments->image)) {
                                $attachments->image = Registry::load('config')->site_url.'assets/files/defaults/image_not_found_alternative.jpg';
                            }

                            $output['messages'][$i]->class = $output['messages'][$i]->class.' url_preview';
                            $output['messages'][$i]->attachments[0]['meta_title'] = $attachments->title;
                            $output['messages'][$i]->attachments[0]['meta_description'] = $attachments->description;
                            $output['messages'][$i]->attachments[0]['meta_image'] = $attachments->image;
                            $output['messages'][$i]->attachments[0]['host_name'] = $attachments->host_name;
                            $output['messages'][$i]->attachments[0]['url'] = $attachments->url;
                            $output['messages'][$i]->attachments[0]['mime_type'] = $attachments->mime_type;

                            if (isset($attachments->iframe_embed)) {
                                $output['messages'][$i]->attachments[0]['iframe_embed'] = $attachments->iframe_embed;
                            }
                            if (isset($attachments->iframe_class)) {
                                $output['messages'][$i]->attachments[0]['iframe_class'] = $attachments->iframe_class;
                            }
                        } else {
                            $output['messages'][$i]->message_label = Registry::load('strings')->attachments;
                            foreach ($attachments as $index => $attachment) {
                                if (isset($attachment->thumbnail) && $message['attachment_type'] === 'image_files') {

                                    $output['messages'][$i]->attachments[$index]['original'] = $attachment->file;

                                    if (file_exists($attachment->thumbnail)) {
                                        $output['messages'][$i]->attachments[$index]['image'] = $attachment->thumbnail;
                                    } else {
                                        $output['messages'][$i]->attachments[$index]['image'] = Registry::load('config')->site_url.'assets/files/defaults/image_thumb.jpg';

                                        if (!file_exists($attachment->file)) {
                                            $output['messages'][$i]->attachments[$index]['original'] = Registry::load('config')->site_url.'assets/files/defaults/image_not_found.jpg';
                                        }

                                    }
                                    if (isset($attachment->thumbnail_size)) {
                                        $output['messages'][$i]->attachments[$index]['image_size'] = $attachment->thumbnail_size;
                                    }
                                } else {
                                    $file_icon = mb_strtolower(pathinfo($attachment->trimmed_name, PATHINFO_EXTENSION));
                                    $file_icon = "assets/files/file_extensions/".$file_icon.".png";
                                    $default_file_icon = "assets/files/file_extensions/unknown.png";

                                    if (isset(Registry::load('settings')->display_full_file_name_of_attachments) && Registry::load('settings')->display_full_file_name_of_attachments === 'yes') {
                                        $output['messages'][$i]->attachments[$index]['file_name'] = $attachment->name;
                                    } else {
                                        $output['messages'][$i]->attachments[$index]['file_name'] = $attachment->trimmed_name;
                                    }

                                    $output['messages'][$i]->attachments[$index]['file_size'] = $attachment->file_size;
                                    $output['messages'][$i]->attachments[$index]['file_type'] = $attachment->file_type;
                                    if (file_exists($file_icon)) {
                                        $output['messages'][$i]->attachments[$index]['file_icon'] = Registry::load('config')->site_url.$file_icon;
                                    } else {
                                        $output['messages'][$i]->attachments[$index]['file_icon'] = Registry::load('config')->site_url.$default_file_icon;
                                    }

                                    if ($message['attachment_type'] === 'audio_files') {
                                        $output['messages'][$i]->attachments[$index]['audio_file'] = Registry::load('config')->site_url.$attachment->file;
                                    } else if ($message['attachment_type'] === 'video_files' && file_exists($attachment->file)) {
                                        $output['messages'][$i]->attachments[$index]['video'] = Registry::load('config')->site_url.$attachment->file;
                                        $output['messages'][$i]->attachments[$index]['thumbnail'] = Registry::load('config')->site_url.'assets/files/defaults/video_thumb.jpg';
                                        if (isset($attachment->thumbnail) && file_exists($attachment->thumbnail)) {
                                            $output['messages'][$i]->attachments[$index]['thumbnail'] = Registry::load('config')->site_url.$attachment->thumbnail;
                                        }
                                    }
                                }

                                if ($permission['download_attachments'] && file_exists($attachment->file)) {
                                    $output['messages'][$i]->attachments[$index]['download_file'] = array();
                                    $output['messages'][$i]->attachments[$index]['download_file']['data-group_id'] = $message['group_id'];
                                    $output['messages'][$i]->attachments[$index]['download_file']['data-message_id'] = $message['group_message_id'];
                                    $output['messages'][$i]->attachments[$index]['download_file']['data-attachment_index'] = $index;
                                }
                            }
                        }
                    }

                    $option_index = 1;

                    if ($data["group_id"] !== 'all') {

                        if ($react_messages) {
                            $output['messages'][$i]->options[$option_index] = new stdClass();
                            $output['messages'][$i]->options[$option_index]->option = Registry::load('strings')->react;
                            $output['messages'][$i]->options[$option_index]->class = 'react_to_messages';
                            $option_index++;
                        }

                        if ($permission['reply_messages']) {
                            $output['messages'][$i]->options[$option_index] = new stdClass();
                            $output['messages'][$i]->options[$option_index]->option = Registry::load('strings')->reply;
                            $output['messages'][$i]->options[$option_index]->class = 'attach_message';
                            $output['messages'][$i]->options[$option_index]->attributes['message_id'] = $message['group_message_id'];
                            $option_index++;
                        }


                        if ($permission['forward_messages']) {
                            $output['messages'][$i]->options[$option_index] = new stdClass();
                            $output['messages'][$i]->options[$option_index]->option = Registry::load('strings')->forward;
                            $output['messages'][$i]->options[$option_index]->class = 'load_aside';
                            $output['messages'][$i]->options[$option_index]->attributes['load'] = 'forward_messages';
                            $output['messages'][$i]->options[$option_index]->attributes['data-group_id'] = $message['group_id'];
                            $output['messages'][$i]->options[$option_index]->attributes['data-message_id'] = $message['group_message_id'];
                            $option_index++;
                        }

                        if ($permission['mention_users']) {
                            $output['messages'][$i]->options[$option_index] = new stdClass();
                            $output['messages'][$i]->options[$option_index]->option = Registry::load('strings')->mention;
                            $output['messages'][$i]->options[$option_index]->class = 'add_to_editor';
                            $output['messages'][$i]->options[$option_index]->attributes['content'] = '@['.$message['username'].'] ';
                            $option_index++;
                        }

                        if ($permission['report_messages']) {
                            $output['messages'][$i]->options[$option_index] = new stdClass();
                            $output['messages'][$i]->options[$option_index]->option = Registry::load('strings')->report;
                            $output['messages'][$i]->options[$option_index]->class = 'load_form';
                            $output['messages'][$i]->options[$option_index]->attributes['form'] = 'complaint';
                            $output['messages'][$i]->options[$option_index]->attributes['data-group_id'] = $message['group_id'];
                            $output['messages'][$i]->options[$option_index]->attributes['data-message_id'] = $message['group_message_id'];
                            $option_index++;
                        }

                    } else {

                        $output['messages'][$i]->options[$option_index] = new stdClass();
                        $output['messages'][$i]->options[$option_index]->option = Registry::load('strings')->view_group;
                        $output['messages'][$i]->options[$option_index]->class = 'load_conversation';
                        $output['messages'][$i]->options[$option_index]->attributes['group_id'] = $message['group_id'];
                        $option_index++;

                        $output['messages'][$i]->options[$option_index] = new stdClass();
                        $output['messages'][$i]->options[$option_index]->option = Registry::load('strings')->group_info;
                        $output['messages'][$i]->options[$option_index]->class = 'get_info';
                        $output['messages'][$i]->options[$option_index]->attributes['group_id'] = $message['group_id'];
                        $option_index++;
                    }

                    if ($data["group_id"] === 'all' && $super_privileges || $permission['view_reactions']) {
                        $output['messages'][$i]->options[$option_index] = new stdClass();
                        $output['messages'][$i]->options[$option_index]->option = Registry::load('strings')->reactions;
                        $output['messages'][$i]->options[$option_index]->class = 'load_aside';
                        $output['messages'][$i]->options[$option_index]->attributes['load'] = 'group_message_reactions';
                        $output['messages'][$i]->options[$option_index]->attributes['data-group_id'] = $message['group_id'];
                        $output['messages'][$i]->options[$option_index]->attributes['data-message_id'] = $message['group_message_id'];
                        $option_index++;
                    }

                    if ($data["group_id"] === 'all' && $super_privileges || (int)$message['user_id'] === (int)Registry::load('current_user')->id) {
                        if ($permission['check_read_receipts']) {
                            $output['messages'][$i]->options[$option_index] = new stdClass();
                            $output['messages'][$i]->options[$option_index]->option = Registry::load('strings')->read_receipts;
                            $output['messages'][$i]->options[$option_index]->class = 'load_aside';
                            $output['messages'][$i]->options[$option_index]->attributes['load'] = 'group_message_read_receipts';
                            $output['messages'][$i]->options[$option_index]->attributes['data-group_id'] = $message['group_id'];
                            $output['messages'][$i]->options[$option_index]->attributes['data-message_id'] = $message['group_message_id'];
                            $option_index++;
                        }
                    }

                    if ($data["group_id"] === 'all' && $super_privileges || $permission['delete_messages']) {
                        $output['messages'][$i]->options[$option_index] = new stdClass();
                        $output['messages'][$i]->options[$option_index]->option = Registry::load('strings')->delete;
                        $output['messages'][$i]->options[$option_index]->class = 'ask_confirmation';
                        $output['messages'][$i]->options[$option_index]->attributes['data-remove'] = 'group_messages';
                        $output['messages'][$i]->options[$option_index]->attributes['data-message_id'] = $message['group_message_id'];
                        $output['messages'][$i]->options[$option_index]->attributes['confirmation'] = Registry::load('strings')->confirm_delete;
                        $output['messages'][$i]->options[$option_index]->attributes['submit_button'] = Registry::load('strings')->yes;
                        $output['messages'][$i]->options[$option_index]->attributes['cancel_button'] = Registry::load('strings')->no;
                        $output['messages'][$i]->options[$option_index]->attributes['column'] = 'second';
                        $option_index++;
                    }

                    $output['messages'][$i]->options[$option_index] = new stdClass();
                    $output['messages'][$i]->options[$option_index]->option = Registry::load('strings')->profile;
                    $output['messages'][$i]->options[$option_index]->class = 'get_info';
                    $output['messages'][$i]->options[$option_index]->attributes['user_id'] = $message['user_id'];
                    $output['messages'][$i]->options[$option_index]->attributes['data-group_identifier'] = $message['group_id'];
                    $option_index++;
                }

                $i++;
            }

            if ($log_last_seen_message_id && Registry::load('current_user')->logged_in && !empty($last_message_id)) {

                if (!isset($data["message_id_greater_than"])) {
                    DB::connect()->update("group_members", ["currently_browsing" => 0], ['user_id' => Registry::load('current_user')->id]);
                }

                DB::connect()->update("group_members",
                    ["last_read_message_id" => $last_message_id, "currently_browsing" => 1],
                    ['group_id' => $data["group_id"], 'user_id' => Registry::load('current_user')->id]
                );
            }
        }

    }
}

?>