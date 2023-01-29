<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

$user_ids = array();

if (role(['permissions' => ['site_users' => 'set_fake_online_users']])) {
    if (isset($data['user_id'])) {
        if (!is_array($data['user_id'])) {
            $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
            $user_ids[] = $data["user_id"];
        } else {
            $user_ids = array_filter($data["user_id"], 'ctype_digit');
        }
    }


    if (!empty($user_ids)) {
        DB::connect()->update("site_users", ["stay_online" => 0], ["user_id" => $user_ids]);

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'reload';
        $result['reload'] = 'fake_online_users';
    }
}