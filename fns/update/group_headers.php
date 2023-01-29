<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['super_privileges' => 'group_headers']])) {

    if (isset($data['group_id'])) {

        $data["group_id"] = filter_var($data["group_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data['group_id'])) {

            $columns = $where = $join = null;

            $columns = [
                'groups.name(group_name)', 'groups.group_header_status',
            ];

            $where['AND'] = ['groups.group_id' => $data['group_id']];
            $where['LIMIT'] = 1;

            $group_info = DB::connect()->select('groups', $columns, $where);

            if (isset($group_info[0])) {
                $group_info = $group_info[0];
                $group_header_status = 0;

                if (isset($data['disabled']) && $data['disabled'] === 'no') {
                    $group_header_status = 1;
                }

                if ((int)$group_info['group_header_status'] !== (int)$group_header_status) {

                    $update_data = [
                        "group_header_status" => $group_header_status,
                        "updated_on" => Registry::load('current_user')->time_stamp,
                    ];

                    DB::connect()->update('groups', $update_data, ['group_id' => $data["group_id"]]);
                }

                if (isset($data['header_content'])) {

                    if (!file_exists('assets/group_headers/')) {
                        mkdir('assets/group_headers/');
                    }

                    $group_header_file = 'assets/group_headers/group_'.$data["group_id"].'.php';
                    file_put_contents($group_header_file, $data['header_content']);
                }

                $result = array();
                $result['success'] = true;
                $result['todo'] = 'load_conversation';
                $result['identifier_type'] = 'group_id';
                $result['identifier'] = $data['group_id'];
            }
        }
    }

}