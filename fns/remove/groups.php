<?php

include 'fns/filters/load.php';
include 'fns/files/load.php';

$result = array();
$noerror = true;
$super_privileges = false;

if ($force_request || role(['permissions' => ['groups' => 'super_privileges']])) {
    $super_privileges = true;
}

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$group_ids = array();

if (isset($data['group_id'])) {
    if (!is_array($data['group_id'])) {
        $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
        $group_ids[] = $data["group_id"];
    } else {
        $group_ids = array_filter($data["group_id"], 'ctype_digit');
    }
}

if ($force_request) {
    if (isset($data['group'])) {
        $columns = $join = $where = null;

        $columns = ['groups.group_id'];
        $where["OR"] = ["groups.group_id" => $data['group'], "groups.slug" => $data['group']];
        $where["LIMIT"] = 1;

        $find_group = DB::connect()->select('groups', $columns, $where);
        $group_ids = array();

        if (isset($find_group[0])) {
            $group_ids[] = $find_group[0]['group_id'];
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

if (!$super_privileges) {
    $group_id = $group_ids[0];

    $columns = $where = $join = null;
    $columns = [
        'groups.group_id', 'group_members.group_role_id',
    ];

    $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => Registry::load('current_user')->id]];

    $where["groups.group_id"] = $group_id;
    $where["groups.suspended"] = 0;
    $where["LIMIT"] = 1;

    $group = DB::connect()->select('groups', $join, $columns, $where);

    if (!isset($group[0])) {
        return false;
    } else {
        $group = $group[0];
        $group_ids = array();
        $group_ids[] = $group_id;

        if (isset($group['group_role_id']) && !empty($group['group_role_id'])) {
            if (!role(['permissions' => ['group' => 'delete_group'], 'group_role_id' => $group['group_role_id']])) {
                return false;
            }
        } else {
            return false;
        }
    }
}

if (!empty($group_ids)) {
    DB::connect()->delete("groups", ["group_id" => $group_ids]);

    if (!DB::connect()->error) {
        foreach ($group_ids as $group_id) {

            $delete_audio_messages = [
                'delete' => 'assets/files/audio_messages/group_chat/'.$group_id,
                'real_path' => true,
            ];

            files('delete', $delete_audio_messages);

            foreach (glob("assets/files/groups/backgrounds/".$group_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                unlink($oldimage);
            }
            foreach (glob("assets/files/groups/cover_pics/".$group_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                unlink($oldimage);
            }
            foreach (glob("assets/files/groups/icons/".$group_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                unlink($oldimage);
            }
            
            $group_header_file = 'assets/group_headers/group_'.$group_id.'.php';

            if (file_exists($group_header_file)) {
                unlink($group_header_file);
            }


        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'refresh';
    } else {
        $result['error_message'] = Registry::load('strings')->went_wrong;
        $result['error_key'] = 'something_went_wrong';
    }
}