<?php

if (isset($data["group_id"])) {
    $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
}

$output = array();
$output['loaded'] = new stdClass();
$output['loaded']->format = 'grid';
$output['loaded']->offset = array();
$output['loaded']->title = Registry::load('strings')->media;

if (isset($data["group_id"]) && !empty($data["group_id"])) {


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

        if ($super_privileges || isset($group_info['group_role_attribute']) && $group_info['group_role_attribute'] !== 'banned_users') {
            if ($super_privileges || role(['permissions' => ['group' => 'view_shared_files'], 'group_role_id' => $group_info['group_role_id']])) {


                $output['loaded']->load_more = true;

                $columns = $join = $where = null;
                $columns = [
                    'group_messages.group_message_id', 'group_messages.attachments', 'group_messages.attachment_type'
                ];

                $join["[>]site_users_blacklist(blacklist)"] = ["group_messages.user_id" => "blacklisted_user_id", "AND" => ["blacklist.user_id" => Registry::load('current_user')->id]];
                $join["[>]site_users_blacklist(blocked)"] = ["group_messages.user_id" => "user_id", "AND" => ["blocked.blacklisted_user_id" => Registry::load('current_user')->id]];

                $where["group_messages.group_id"] = $data["group_id"];

                if (isset($data["offset"])) {
                    if (!empty($data["offset"])) {
                        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
                        $where["group_messages.group_message_id[!]"] = $data["offset"];
                    }
                }

                $where["AND"]["OR #first condition"] = ["blacklist.ignore" => NULL, "blacklist.ignore(ignored)" => 0];
                $where["AND"]["OR #second condition"] = ["blacklist.block" => NULL, "blacklist.block(blocked)" => 0];
                $where["AND"]["OR #third condition"] = ["blocked.block" => NULL, "blocked.block(blocked)" => 0];


                $where["AND"]["OR #fourth condition"] = [
                    "group_messages.attachment_type(image_files)" => 'image_files',
                    "group_messages.attachment_type(video_files)" => 'video_files',
                    "group_messages.attachment_type(audio_files)" => 'audio_files'
                ];


                $where["ORDER"] = ['group_messages.group_message_id' => 'DESC'];
                $where["LIMIT"] = 10;


                $media_files = DB::connect()->select('group_messages', $join, $columns, $where);

                $index = 0;

                if (!empty($data["offset"])) {
                    $output['loaded']->offset = $data["offset"];
                }


                if (count($media_files) < 10) {
                    unset($output['loaded']->load_more);
                }

                foreach ($media_files as $media_file) {

                    $attachments = json_decode($media_file['attachments']);
                    $output['loaded']->offset[] = $media_file['group_message_id'];

                    foreach ($attachments as $attachment) {
                        if (file_exists($attachment->file)) {
                            if ($media_file['attachment_type'] === 'image_files' && isset($attachment->thumbnail)) {
                                $output['content'][$index] = new stdClass();
                                $output['content'][$index]->image = Registry::load('config')->site_url.'assets/files/defaults/image_thumb.jpg';

                                if (file_exists($attachment->thumbnail)) {
                                    $output['content'][$index]->image = $attachment->thumbnail;
                                }

                                $output['content'][$index]->attributes = [
                                    'class' => 'preview_image',
                                    'load_image' => Registry::load('config')->site_url.$attachment->file,
                                ];

                            } else if ($media_file['attachment_type'] === 'video_files' && isset($attachment->file)) {
                                $output['content'][$index] = new stdClass();
                                $output['content'][$index]->image = Registry::load('config')->site_url.'assets/files/defaults/video_thumb.jpg';

                                if (isset($attachment->thumbnail) && file_exists($attachment->thumbnail)) {
                                    $output['content'][$index]->image = Registry::load('config')->site_url.$attachment->thumbnail;
                                }

                                $output['content'][$index]->attributes = [
                                    'class' => 'preview_video',
                                    'mime_type' => $attachment->file_type,
                                    'thumbnail' => $output['content'][$index]->image,
                                    'video_file' => Registry::load('config')->site_url.$attachment->file,
                                ];


                            } else if ($media_file['attachment_type'] === 'audio_files') {
                                $output['content'][$index] = new stdClass();
                                $output['content'][$index]->image = Registry::load('config')->site_url.'assets/files/defaults/audio_thumb.jpg';

                                $output['content'][$index]->attributes = [
                                    'class' => 'preview_video',
                                    'thumbnail' => $output['content'][$index]->image,
                                    'mime_type' => $attachment->file_type,
                                    'video_file' => Registry::load('config')->site_url.$attachment->file,
                                ];

                            }
                        }

                        $index++;
                    }
                }
            }
        }
    }
}