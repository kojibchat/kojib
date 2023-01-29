<?php

if (role(['permissions' => ['private_conversations' => 'view_private_chats']])) {

    if (isset($data["user_id"])) {

        $current_user_id = Registry::load('current_user')->id;
        $delete_message_time_limit = role(['find' => 'delete_message_time_limit']);
        $super_privileges = false;
        $disable_private_chat = false;
        $load_conversation_id = 0;

        $check_user_id = explode('[', $data["user_id"]);

        if (isset($check_user_id[1]) && isset($check_user_id[0]) && $check_user_id[0] === 'all') {
            $data["user_id"] = "all";
            $load_conversation_id = filter_var($check_user_id[1], FILTER_SANITIZE_NUMBER_INT);
        }

        if ((int)$current_user_id === (int)$data["user_id"]) {
            return;
        }

        $permission = [
            'reply_messages' => false,
            'check_read_receipts' => false,
            'delete_own_message' => false,
        ];

        if (role(['permissions' => ['private_conversations' => ['reply_messages', 'send_message']]])) {
            $permission['reply_messages'] = true;
        }

        if (role(['permissions' => ['private_conversations' => 'delete_own_message']])) {
            $permission['delete_own_message'] = true;
        }

        if (role(['permissions' => ['private_conversations' => 'check_read_receipts']])) {
            $permission['check_read_receipts'] = true;
        }

        if (role(['permissions' => ['private_conversations' => 'super_privileges']])) {
            $super_privileges = true;
        }

        if ($data["user_id"] !== 'all') {
            $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
        } else {
            if (!role(['permissions' => ['super_privileges' => 'monitor_private_chats']])) {

                $data["user_id"] = 0;
                $output['loaded'] = new stdClass();
                $output['loaded']->title = Registry::load('strings')->permission_denied;
                $output['loaded']->image = Registry::load('config')->site_url.'assets/files/defaults/error.png';


                $output['error_message'] = new stdClass();
                $output['error_message']->title = Registry::load('strings')->permission_denied;
                $output['error_message']->subtitle = Registry::load('strings')->access_denied_message;
                $output['error_message']->image = Registry::load('config')->site_url.'assets/files/defaults/denied.png';
            }
        }

        if (!empty($data["user_id"])) {

            $output['loaded'] = new stdClass();
            $output['loaded']->title = Registry::load('strings')->not_found;
            $output['loaded']->user_id = $data["user_id"];
            $log_read_status = true;

            if ($super_privileges) {
                $output['loaded']->multi_select = true;
            }

            if ($data["user_id"] !== 'all') {

                $column = $join = $where = null;
                $columns = [
                    'site_users.display_name', 'blacklist.block(blocked)',
                    'site_users_settings.deactivated', 'site_users_settings.disable_private_messages',
                    'site_users.username',
                ];

                $join["[>]site_users_settings"] = ["site_users.user_id" => "user_id"];
                $join["[>]site_users_blacklist(blacklist)"] = ["site_users.user_id" => "user_id", "AND" => ["blacklist.blacklisted_user_id" => $current_user_id]];

                $where["site_users.user_id"] = $data["user_id"];
                $where["LIMIT"] = 1;
                $user_info = DB::connect()->select('site_users', $join, $columns, $where);

                if (isset($user_info[0]['display_name'])) {
                    $output['loaded']->title = $user_info[0]['display_name'];
                }

                $column = $join = $where = null;
                $columns = [
                    'private_conversations.private_conversation_id',
                    'private_conversations.initiator_user_id', 'private_conversations.recipient_user_id',
                    'private_conversations.initiator_load_message_id_from', 'private_conversations.recipient_load_message_id_from'
                ];

                $where["OR"]["AND #first_query"] = [
                    "private_conversations.initiator_user_id" => $data["user_id"],
                    "private_conversations.recipient_user_id" => $current_user_id,
                ];
                $where["OR"]["AND #second_query"] = [
                    "private_conversations.initiator_user_id" => $current_user_id,
                    "private_conversations.recipient_user_id" => $data["user_id"],
                ];

                $where["LIMIT"] = 1;
                $private_conversation = DB::connect()->select('private_conversations', $columns, $where);


                if (isset($user_info[0]['blocked']) && !empty($user_info[0]['blocked']) && !$super_privileges) {
                    $output['loaded']->image = get_image(['from' => 'site_users/profile_pics', 'search' => 0]);
                } else {
                    $output['loaded']->image = get_image(['from' => 'site_users/profile_pics', 'search' => $data["user_id"]]);
                }

            } else {

                $log_read_status = false;
                $output['loaded']->title = Registry::load('strings')->monitor_private_chats;
                $output['loaded']->image = Registry::load('config')->site_url.'assets/files/defaults/conversation.png';

                if (!empty($load_conversation_id)) {
                    $column = $join = $where = null;
                    $columns = ['recipient.display_name(recipient_name)', 'initiator.display_name(initiator_name)'];

                    $join["[>]site_users(recipient)"] = ["private_conversations.recipient_user_id" => "user_id"];
                    $join["[>]site_users(initiator)"] = ["private_conversations.initiator_user_id" => "user_id"];

                    $where = ["private_conversations.private_conversation_id" => $load_conversation_id];
                    $where["LIMIT"] = 1;
                    $chat_users = DB::connect()->select('private_conversations', $join, $columns, $where);

                    if (isset($chat_users[0])) {
                        $output['loaded']->title = $chat_users[0]['initiator_name'].' - '.$chat_users[0]['recipient_name'];
                        $output['loaded']->user_id = 'all['.$load_conversation_id.']';
                    } else {
                        $load_conversation_id = 0;
                    }
                }

            }

            if ($data["user_id"] !== 'all') {

                if (isset($user_info[0]['display_name']) && !empty($user_info[0]['display_name'])) {
                    $output['loaded']->browser_title = $user_info[0]['display_name'].' - '.Registry::load('settings')->site_name;
                }

                if (isset($user_info[0]['username']) && !empty($user_info[0]['username'])) {
                    $output['loaded']->browser_address_bar = Registry::load('config')->site_url.$user_info[0]['username'].'/chat/';
                }

                $output['loaded']->view_info = true;

                if (isset($user_info[0]['blocked']) && !empty($user_info[0]['blocked']) && !$super_privileges) {
                    $disable_private_chat = true;
                } else if (isset($user_info[0]['deactivated']) && !empty($user_info[0]['deactivated']) && !$super_privileges) {
                    $disable_private_chat = true;
                } else if (isset($user_info[0]['disable_private_messages']) && !empty($user_info[0]['disable_private_messages']) && !$super_privileges) {
                    $disable_private_chat = true;
                }

                if ($disable_private_chat) {

                    $output['loaded']->blocked = true;
                    $output['loaded']->view_info = false;

                } else if (isset($private_conversation[0]['private_conversation_id']) || role(['permissions' => ['private_conversations' => 'initiate_private_chat']])) {
                    if (role(['permissions' => ['private_conversations' => 'send_message']])) {
                        $output['loaded']->messaging = true;
                        $output['loaded']->disable_features = array();

                        if (!isset($private_conversation[0]['private_conversation_id'])) {
                            if (Registry::load('settings')->friend_system === 'enable') {

                                if (!role(['permissions' => ['private_conversations' => 'message_non_friends']])) {
                                    $columns = $join = $where = null;
                                    $columns = ['friendship_id', 'from_user_id', 'to_user_id', 'relation_status'];

                                    $where["OR"]["AND #first_query"] = [
                                        "friends.from_user_id" => $data["user_id"],
                                        "friends.to_user_id" => $current_user_id,
                                        "friends.relation_status" => 1
                                    ];
                                    $where["OR"]["AND #second_query"] = [
                                        "friends.from_user_id" => $current_user_id,
                                        "friends.to_user_id" => $data["user_id"],
                                        "friends.relation_status" => 1
                                    ];

                                    $where["LIMIT"] = 1;

                                    $check_friend_list = DB::connect()->select('friends', $columns, $where);

                                    if (!isset($check_friend_list[0])) {
                                        $output['loaded']->messaging = false;
                                    }
                                }
                            }
                        }

                        if (Registry::load('settings')->gif_search_engine === 'disable' || !role(['permissions' => ['private_conversations' => 'attach_gifs']])) {
                            $output['loaded']->disable_features[] = 'gifs';
                        }

                        if (!role(['permissions' => ['private_conversations' => 'send_audio_message']])) {
                            $output['loaded']->disable_features[] = 'voice_message';
                        }

                        if (!role(['permissions' => ['private_conversations' => 'attach_stickers']])) {
                            $output['loaded']->disable_features[] = 'stickers';
                        }

                        if (!role(['permissions' => ['private_conversations' => 'attach_files']])) {
                            $output['loaded']->disable_features[] = 'attach_files';
                        }

                        if (!role(['permissions' => ['private_conversations' => 'attach_from_storage']])) {
                            $output['loaded']->disable_features[] = 'attach_from_storage';
                        }
                    }
                }
            }

            if ($data["user_id"] === 'all' || isset($private_conversation[0]['private_conversation_id'])) {

                if ($data["user_id"] !== 'all') {

                    $private_conversation = $private_conversation[0];

                    if ((int)$private_conversation['initiator_user_id'] === (int)$current_user_id) {
                        $load_message_id_from = $private_conversation['initiator_load_message_id_from'];
                    } else {
                        $load_message_id_from = $private_conversation['recipient_load_message_id_from'];
                    }

                    $conversation_id = $private_conversation['private_conversation_id'];
                }

                $column = $join = $where = null;

                $columns = [
                    'message_author.display_name', 'private_chat_messages.private_chat_message_id', 'private_chat_messages.filtered_message',
                    'private_chat_messages.system_message', 'private_chat_messages.parent_message_id', 'private_chat_messages.attachments',
                    'private_chat_messages.link_preview', 'private_chat_messages.created_on', 'private_chat_messages.updated_on',
                    'private_chat_messages.user_id', 'private_chat_messages.attachment_type',
                    'reply.filtered_message(reply_message)', 'reply.attachment_type(reply_attachment_type)',
                    'reply.attachments(reply_attachments)', 'attached_message_author.display_name(attached_message_author)',
                    'message_author.username', 'private_chat_messages.read_status', 'private_chat_messages.private_conversation_id',
                    'message_author_site_role.site_role_id'
                ];

                $join["[>]private_chat_messages(reply)"] = ["private_chat_messages.parent_message_id" => "private_chat_message_id"];
                $join["[>]site_users(attached_message_author)"] = ["reply.user_id" => "user_id"];
                $join["[>]site_users(message_author)"] = ["private_chat_messages.user_id" => "user_id"];
                $join["[>]site_roles(message_author_site_role)"] = ["message_author.site_role_id" => "site_role_id"];

                if ($data["user_id"] !== 'all') {
                    $where["private_chat_messages.private_conversation_id"] = $conversation_id;

                    if (!empty($load_message_id_from)) {
                        $where["private_chat_messages.private_chat_message_id[>]"] = $load_message_id_from;
                    }

                } else {
                    $columns[] = 'private_conversations.initiator_user_id';
                    $columns[] = 'private_conversations.recipient_user_id';
                    $columns[] = 'initiator_username.display_name(initiator_user_name)';
                    $columns[] = 'recipient_username.display_name(recipient_user_name)';

                    $join["[>]private_conversations"] = ["private_chat_messages.private_conversation_id" => "private_conversation_id"];
                    $join["[>]site_users(initiator_username)"] = ["private_conversations.initiator_user_id" => "user_id"];
                    $join["[>]site_users(recipient_username)"] = ["private_conversations.recipient_user_id" => "user_id"];

                    if (!empty($load_conversation_id)) {
                        $where["private_chat_messages.private_conversation_id"] = $load_conversation_id;
                    }
                }

                if (isset($data["message_id"])) {
                    $data["message_id"] = filter_var($data["message_id"], FILTER_SANITIZE_NUMBER_INT);
                    if (!empty($data["message_id"])) {
                        $log_read_status = false;
                        $where["private_chat_messages.private_chat_message_id"] = $data["message_id"];
                    }
                }


                if (isset($data["search"]) && !empty($data["search"])) {
                    $where["AND #search_query"]["OR"] = [
                        "message_author.display_name[~]" => $data["search"],
                        "message_author.username[~]" => $data["search"],
                        "message_author.email_address[~]" => $data["search"],
                        "private_chat_messages.filtered_message[~]" => $data["search"],
                        "private_chat_messages.attachments[~]" => $data["search"]
                    ];
                    $log_read_status = false;
                }

                if (isset($data["message_id_less_than"])) {
                    $data["message_id_less_than"] = filter_var($data["message_id_less_than"], FILTER_SANITIZE_NUMBER_INT);
                    if (!empty($data["message_id_less_than"])) {
                        $where["private_chat_messages.private_chat_message_id[<]"] = $data["message_id_less_than"];
                        $log_read_status = false;
                    }
                }

                if (isset($data["message_id_from"])) {
                    $data["message_id_from"] = filter_var($data["message_id_from"], FILTER_SANITIZE_NUMBER_INT);
                    if (!empty($data["message_id_from"])) {
                        $where["private_chat_messages.private_chat_message_id[>=]"] = $data["message_id_from"];
                    }
                }

                if (isset($data["message_id_greater_than"])) {
                    $data["message_id_greater_than"] = filter_var($data["message_id_greater_than"], FILTER_SANITIZE_NUMBER_INT);
                    if (!empty($data["message_id_greater_than"])) {
                        $where["private_chat_messages.private_chat_message_id[>]"] = $data["message_id_greater_than"];
                    }
                }

                $where["ORDER"] = ['private_chat_messages.private_chat_message_id' => 'DESC'];
                $where["LIMIT"] = Registry::load('settings')->messages_per_call;

                $private_chat_messages = DB::connect()->select('private_chat_messages', $join, $columns, $where);

                $i = 0;

                foreach ($private_chat_messages as $message) {

                    $date['date'] = $message['created_on'];
                    $date['auto_format'] = true;
                    $date['include_time'] = true;
                    $date['compare_with_today'] = true;
                    $date['timezone'] = Registry::load('current_user')->time_zone;
                    $created_on = get_date($date);

                    $output['messages'][$i] = new stdClass();
                    $output['messages'][$i]->content = $message['filtered_message'];
                    $output['messages'][$i]->time = $created_on['time'];
                    $output['messages'][$i]->date = $created_on['date'];
                    $output['messages'][$i]->attachment_type = $message['attachment_type'];
                    $output['messages'][$i]->message_label = Registry::load('strings')->message;
                    $output['messages'][$i]->message_id = $message['private_chat_message_id'];
                    $output['messages'][$i]->own_message = false;
                    $output['messages'][$i]->class = 'private_message';

                    if (isset(Registry::load('settings')->display_username_private_chats) && Registry::load('settings')->display_username_private_chats === 'enable') {
                        $output['messages'][$i]->posted_by = $message['username'];
                    } else {
                        $output['messages'][$i]->posted_by = $message['display_name'];
                    }

                    if ($disable_private_chat && $message['user_id'] !== $current_user_id) {
                        $output['messages'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => 0]);
                    } else {
                        $output['messages'][$i]->sender_user_id = $message['user_id'];
                        $output['messages'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $message['user_id']]);
                    }

                    if (isset($message['site_role_id'])) {

                        $name_color = role(['find' => 'name_color', 'site_role_id' => $message['site_role_id']]);

                        if (!empty($name_color)) {
                            $output['messages'][$i]->name_color = $name_color;
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

                        $system_message = json_decode($message['filtered_message']);
                        $system_language_string = $system_message->message;
                        $output['messages'][$i]->content = Registry::load('strings')->$system_language_string;

                    } else {

                        if (isset($data["message_id"]) && !empty($data["message_id"])) {
                            $output['messages'][$i]->class .= ' skip_message';
                            $output['messages'][$i]->highlight_message = true;
                        }


                        if ($data["user_id"] === 'all') {

                            if ((int)$message['recipient_user_id'] !== (int)$message['user_id']) {
                                $sent_to = $message['recipient_user_name'];
                                $recipient_user_id = $message['recipient_user_id'];
                            } else {
                                $sent_to = $message['initiator_user_name'];
                                $recipient_user_id = $message['initiator_user_id'];
                            }
                            $output['messages'][$i]->badge = ['text' => $sent_to];
                        }

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
                                    if (isset($reply_attachments[0]->thumbnail)) {
                                        $output['messages'][$i]->reply_thumbnail = $reply_attachments[0]->thumbnail;
                                    }
                                }
                            }

                            if (!empty($message['reply_message'])) {
                                $output['messages'][$i]->reply_message = $message['reply_message'];
                            }
                        }

                        if ($data["user_id"] === 'all') {
                            if ($permission['check_read_receipts']) {
                                $output['messages'][$i]->read_status = 'unread';

                                if (!empty($message['read_status'])) {
                                    $output['messages'][$i]->read_status = 'read';
                                }
                            }
                        } else {
                            if ((int)$message['user_id'] === (int)Registry::load('current_user')->id) {

                                $output['messages'][$i]->own_message = true;
                                $output['messages'][$i]->class .= ' own_message';

                                if ($data["user_id"] !== 'all' && Registry::load('settings')->own_message_alignment === 'right') {
                                    $output['messages'][$i]->class .= ' align_right';
                                }

                                if ($permission['check_read_receipts']) {
                                    $output['messages'][$i]->read_status = 'unread';

                                    if (!empty($message['read_status'])) {
                                        $output['messages'][$i]->class .= ' seen_by_recipient';
                                        $output['messages'][$i]->read_status = 'read';
                                    }
                                }
                            } else {
                                if ($data["user_id"] !== 'all' && Registry::load('settings')->message_alignment === 'right') {
                                    $output['messages'][$i]->class .= ' align_right';
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

                                    if (file_exists($attachment->file)) {
                                        $output['messages'][$i]->attachments[$index]['download_file'] = array();
                                        $output['messages'][$i]->attachments[$index]['download_file']['data-private_conversation_id'] = $message['private_conversation_id'];
                                        $output['messages'][$i]->attachments[$index]['download_file']['data-message_id'] = $message['private_chat_message_id'];
                                        $output['messages'][$i]->attachments[$index]['download_file']['data-attachment_index'] = $index;
                                    }
                                }
                            }
                        }

                        if ($data["user_id"] !== 'all') {
                            if ($permission['reply_messages']) {
                                $output['messages'][$i]->options[1] = new stdClass();
                                $output['messages'][$i]->options[1]->option = Registry::load('strings')->reply;
                                $output['messages'][$i]->options[1]->class = 'attach_message';
                                $output['messages'][$i]->options[1]->attributes['message_id'] = $message['private_chat_message_id'];
                            }
                        } else {
                            $output['messages'][$i]->options[1] = new stdClass();
                            $output['messages'][$i]->options[1]->option = Registry::load('strings')->sender;
                            $output['messages'][$i]->options[1]->class = 'get_info';
                            $output['messages'][$i]->options[1]->attributes['user_id'] = $message['user_id'];

                            $output['messages'][$i]->options[3] = new stdClass();
                            $output['messages'][$i]->options[3]->option = Registry::load('strings')->recipient;
                            $output['messages'][$i]->options[3]->class = 'get_info';
                            $output['messages'][$i]->options[3]->attributes['user_id'] = $recipient_user_id;
                        }

                        $allow_delete_message = false;

                        if ($super_privileges) {
                            $allow_delete_message = true;
                        } else if ((int)$message['user_id'] === (int)Registry::load('current_user')->id) {
                            if ($permission['delete_own_message']) {
                                if (!empty($delete_message_time_limit)) {

                                    $to_time = strtotime($message['created_on']);
                                    $from_time = strtotime("now");
                                    $time_difference = round(abs($to_time - $from_time) / 60, 2);

                                    if ($time_difference < $delete_message_time_limit) {
                                        $allow_delete_message = true;
                                    }
                                }
                            }
                        }

                        if ($allow_delete_message) {
                            $output['messages'][$i]->options[2] = new stdClass();
                            $output['messages'][$i]->options[2]->option = Registry::load('strings')->delete;
                            $output['messages'][$i]->options[2]->class = 'ask_confirmation';
                            $output['messages'][$i]->options[2]->attributes['data-remove'] = 'private_chat_messages';
                            $output['messages'][$i]->options[2]->attributes['data-message_id'] = $message['private_chat_message_id'];
                            $output['messages'][$i]->options[2]->attributes['confirmation'] = Registry::load('strings')->confirm_delete;
                            $output['messages'][$i]->options[2]->attributes['submit_button'] = Registry::load('strings')->yes;
                            $output['messages'][$i]->options[2]->attributes['cancel_button'] = Registry::load('strings')->no;
                            $output['messages'][$i]->options[2]->attributes['column'] = 'second';
                        }
                    }

                    $i++;
                }

                if ($log_read_status && isset($conversation_id)) {
                    DB::connect()->update("private_chat_messages", ["read_status" => 1], [
                        'private_conversation_id' => $conversation_id,
                        'user_id[!]' => Registry::load('current_user')->id
                    ]);
                }
            }
        }
    }
}

?>