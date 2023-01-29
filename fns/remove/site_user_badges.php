<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

$user_id = Registry::load('current_user')->id;
$badge_ids = array();

if (role(['permissions' => ['badges' => 'assign']])) {
    if (isset($data['user_id'])) {
        $user_id = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if (isset($data['badge_id'])) {
        if (!is_array($data['badge_id'])) {
            $data["badge_id"] = filter_var($data["badge_id"], FILTER_SANITIZE_NUMBER_INT);
            $badge_ids[] = $data["badge_id"];
        } else {
            $badge_ids = array_filter($data["badge_id"], 'ctype_digit');
        }
    }

    if (!empty($badge_ids) && !empty($user_id)) {

        DB::connect()->delete("badges_assigned", ["user_id" => $user_id, "badge_id" => $badge_ids]);
        $result = array();
        $result['success'] = true;
        $result['todo'] = 'reload';
        $result['reload'] = ['badges', 'badges_assigned'];
    }
}
?>