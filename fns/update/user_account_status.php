<?php
$user_id = 0;

if (role(['permissions' => ['site_users' => 'approve_users']])) {

    if (isset($data['user_id'])) {
        $user_id = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if (!empty($user_id)) {

        $update_data = ['updated_on' => Registry::load('current_user')->time_stamp];
        $update_data['approved'] = 1;

        if (isset($data['disapprove'])) {
            $update_data['approved'] = 0;
        }

        DB::connect()->update("site_users", $update_data, ["user_id" => $user_id]);
    }

    $result = array();
    $result['success'] = true;
    $result['todo'] = 'reload';
    $result['reload'] = 'site_users';
    $result['filter_data'] = 'pending_approval';
    
    if (isset($data['info_box']) && !empty($user_id)) {
        $result['info_box']['user_id'] = $user_id;
    }
    
} else {
    $result['error_message'] = Registry::load('strings')->went_wrong;
    $result['error_key'] = 'something_went_wrong';
}