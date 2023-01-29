<?php
if (isset($data['group_id'])) {

    $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);
    $user_id = Registry::load('current_user')->id;

    if (!empty($data['group_id'])) {

        $force_request = false;
        $group_id = $data['group_id'];

        if (role(['permissions' => ['groups' => 'super_privileges']])) {
            $force_request = true;
        }

        $columns = $join = $where = null;
        $columns = [
            'groups.secret_group', 'group_roles.group_role_attribute',
            'group_members.group_role_id', 'groups.password', 'groups.suspended', 'groups.group_header_status'
        ];

        $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => $user_id]];
        $join["[>]group_roles"] = ['group_members.group_role_id' => 'group_role_id'];

        $where["groups.group_id"] = $group_id;

        $where["LIMIT"] = 1;

        $group_info = DB::connect()->select('groups', $join, $columns, $where);

        if (isset($group_info[0])) {
            $group_info = $group_info[0];

            if (empty($group_info['secret_group']) && empty($group_info['password']) && empty($group_info['suspended'])) {
                $force_request = true;
            }

            if ($force_request || isset($group_info['group_role_id']) && !empty($group_info['group_role_id'])) {
                if ($force_request || isset($group_info['group_role_attribute']) && $group_info['group_role_attribute'] !== 'banned_users') {
                    if (!empty($group_info['group_header_status'])) {
                        $group_header_file = 'assets/group_headers/group_'.$data["group_id"].'.php';

                        if (file_exists($group_header_file)) {
                            include($group_header_file);
                        }
                    }
                }
            }
        }
    }
}

?>