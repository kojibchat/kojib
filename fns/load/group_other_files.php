<?php

if (isset($data["group_id"])) {
    $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
}


$output = array();
$output['loaded'] = new stdClass();
$output['loaded']->format = 'list';
$output['loaded']->offset = array();
$output['loaded']->title = Registry::load('strings')->other_files;

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
                $where["group_messages.attachment_type"] = 'other_files';

                if (isset($data["offset"])) {
                    if (!empty($data["offset"])) {
                        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
                        $where["group_messages.group_message_id[!]"] = $data["offset"];
                    }
                }

                $where["AND"]["OR #first condition"] = ["blacklist.ignore" => NULL, "blacklist.ignore(ignored)" => 0];
                $where["AND"]["OR #second condition"] = ["blacklist.block" => NULL, "blacklist.block(blocked)" => 0];
                $where["AND"]["OR #third condition"] = ["blocked.block" => NULL, "blocked.block(blocked)" => 0];

                $where["ORDER"] = ['group_messages.group_message_id' => 'DESC'];
                $where["LIMIT"] = 10;


                $other_files = DB::connect()->select('group_messages', $join, $columns, $where);

                $index = 0;

                if (!empty($data["offset"])) {
                    $output['loaded']->offset = $data["offset"];
                }


                if (count($other_files) < 10) {
                    unset($output['loaded']->load_more);
                }

                foreach ($other_files as $other_file) {

                    $attachments = json_decode($other_file['attachments']);
                    $output['loaded']->offset[] = $other_file['group_message_id'];

                    foreach ($attachments as $attachment_index => $attachment) {
                        if (isset($attachment->file)) {
                            $file_icon = mb_strtolower(pathinfo($attachment->trimmed_name, PATHINFO_EXTENSION));
                            $file_icon = "assets/files/file_extensions/".$file_icon.".png";
                            $output['content'][$index] = new stdClass();
                            $output['content'][$index]->image = Registry::load('config')->site_url."assets/files/file_extensions/unknown.png";

                            if (file_exists($file_icon)) {
                                $output['content'][$index]->image = Registry::load('config')->site_url.$file_icon;
                            }

                            $output['content'][$index]->attributes = [
                                'class' => 'download_file',
                                'download' => 'attachment',
                                'data-group_id' => $data["group_id"],
                                'data-message_id' => $other_file['group_message_id'],
                                'data-attachment_index' => $attachment_index,
                            ];

                            if (isset(Registry::load('settings')->display_full_file_name_of_attachments) && Registry::load('settings')->display_full_file_name_of_attachments === 'yes') {
                                $output['content'][$index]->heading = $attachment->name;
                            } else {
                                $output['content'][$index]->heading = $attachment->trimmed_name;
                            }
                            $output['content'][$index]->description = $attachment->file_size;

                        }

                        $index++;
                    }
                }
            }
        }
    }
}