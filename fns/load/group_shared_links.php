<?php

if (isset($data["group_id"])) {
    $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
}


$output = array();
$output['loaded'] = new stdClass();
$output['loaded']->format = 'list';
$output['loaded']->offset = array();
$output['loaded']->title = Registry::load('strings')->links;

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


    if (role(['permissions' => ['groups' => 'super_privileges']])) {
        $super_privileges = true;
    }

    if ($super_privileges || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {

        if ($super_privileges || isset($group_info['group_role_attribute']) && $group_info['group_role_attribute'] !== 'banned_users') {
            if ($super_privileges || role(['permissions' => ['group' => 'view_shared_links'], 'group_role_id' => $group_info['group_role_id']])) {

                $output['loaded']->load_more = true;

                $columns = $join = $where = null;
                $columns = [
                    'group_messages.group_message_id', 'group_messages.attachments', 'group_messages.attachment_type'
                ];

                $join["[>]site_users_blacklist(blacklist)"] = ["group_messages.user_id" => "blacklisted_user_id", "AND" => ["blacklist.user_id" => Registry::load('current_user')->id]];
                $join["[>]site_users_blacklist(blocked)"] = ["group_messages.user_id" => "user_id", "AND" => ["blocked.blacklisted_user_id" => Registry::load('current_user')->id]];

                $where["group_messages.group_id"] = $data["group_id"];
                $where["group_messages.attachment_type"] = 'url_meta';

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


                $shared_links = DB::connect()->select('group_messages', $join, $columns, $where);

                $index = 0;

                if (!empty($data["offset"])) {
                    $output['loaded']->offset = $data["offset"];
                }


                if (count($shared_links) < 10) {
                    unset($output['loaded']->load_more);
                }

                foreach ($shared_links as $shared_link) {

                    $attachments = json_decode($shared_link['attachments']);
                    $output['loaded']->offset[] = $shared_link['group_message_id'];

                    if (isset($attachments->title)) {

                        $output['content'][$index] = new stdClass();
                        $output['content'][$index]->image = Registry::load('config')->site_url.'assets/files/defaults/video_thumb.jpg';

                        if (!empty($attachments->image)) {
                            $output['content'][$index]->image = $attachments->image;
                        }

                        $output['content'][$index]->attributes = [
                            'class' => 'open_link',
                            'link' => $attachments->url,
                            'target' => '_blank',
                        ];


                        $output['content'][$index]->heading = $attachments->title;
                        $output['content'][$index]->description = $attachments->description;

                        $index++;
                    }
                }
            }
        }
    }
}