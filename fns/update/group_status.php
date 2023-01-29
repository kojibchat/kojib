<?php

if (role(['permissions' => ['groups' => 'super_privileges']])) {

    if (isset($data['unsuspend_group_id'])) {
        $data["unsuspend_group_id"] = filter_var($data["unsuspend_group_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data['unsuspend_group_id'])) {
            $group_id = $data["unsuspend_group_id"];
            DB::connect()->update("groups", ["suspended" => 0, "updated_on" => Registry::load('current_user')->time_stamp], ['group_id' => $group_id]);
        }
    } else if (isset($data['suspend_group_id'])) {
        $data["suspend_group_id"] = filter_var($data["suspend_group_id"], FILTER_SANITIZE_NUMBER_INT);

        if (!empty($data['suspend_group_id'])) {
            $group_id = $data["suspend_group_id"];
            DB::connect()->update("groups", ["suspended" => 1, "updated_on" => Registry::load('current_user')->time_stamp], ['group_id' => $group_id]);
        }
    }

    $result = array();
    $result['success'] = true;
    $result['todo'] = 'reload';
    $result['reload'] = ['groups'];

    if (isset($data['info_box']) && !empty($group_id)) {
        $result['info_box']['group_id'] = $group_id;
    }

}