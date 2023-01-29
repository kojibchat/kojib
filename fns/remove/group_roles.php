<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$group_role_ids = array();

if (role(['permissions' => ['group_roles' => 'delete']])) {
    if (isset($data['group_role_id'])) {
        if (!is_array($data['group_role_id'])) {
            $data["group_role_id"] = filter_var($data["group_role_id"], FILTER_SANITIZE_NUMBER_INT);
            $group_role_ids[] = $data["group_role_id"];
        } else {
            $group_role_ids = array_filter($data["group_role_id"], 'ctype_digit');
        }
    }

    if (!empty($group_role_ids)) {

        $columns = $where = $join = null;
        $columns = [
            'group_roles.group_role_id'
        ];

        $where["group_roles.group_role_id"] = $group_role_ids;
        $where["group_roles.group_role_attribute[!]"] = ['default_group_role', 'administrators', 'moderators', 'banned_users'];

        $validate_group_roles = DB::connect()->select('group_roles', $columns, $where);
        $group_role_ids = array();

        foreach ($validate_group_roles as $valid_group_role) {
            $group_role_ids[] = $valid_group_role['group_role_id'];
        }

    }

    if (!empty($group_role_ids)) {

        $default_group_role_id = DB::connect()->select("group_roles", ["group_role_id"], ["group_role_attribute" => "default_group_role"]);

        if (isset($default_group_role_id[0])) {
            $default_group_role_id = $default_group_role_id[0]['group_role_id'];
        } else {
            $default_group_role_id = 4;
        }

        DB::connect()->update("group_members", ["group_role_id" => $default_group_role_id], ["group_role_id" => $group_role_ids]);

        DB::connect()->delete("group_roles", ["group_role_id" => $group_role_ids]);

        if (!DB::connect()->error) {

            foreach ($group_role_ids as $group_role_id) {
                $group_role_names[] = 'group_role_'.$group_role_id;

                foreach (glob("assets/files/group_roles/".$group_role_id.Registry::load('config')->file_seperator."*.*") as $oldimage) {
                    unlink($oldimage);
                }
            }

            language(['delete_string' => $group_role_names]);

            cache(['rebuild' => 'group_roles']);

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'group_roles';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }
    }
}
?>