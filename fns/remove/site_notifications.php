<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$notification_ids = array();
$user_id = Registry::load('current_user')->id;

if (role(['permissions' => ['site_notifications' => 'delete']])) {

    if (isset($data['notification_id'])) {
        if (!is_array($data['notification_id'])) {
            $data["notification_id"] = filter_var($data["notification_id"], FILTER_SANITIZE_NUMBER_INT);
            $notification_ids[] = $data["notification_id"];
        } else {
            $notification_ids = array_filter($data["notification_id"], 'ctype_digit');
        }
    }

    if (!empty($notification_ids)) {

        DB::connect()->delete("site_notifications", ["notification_id" => $notification_ids, "user_id" => $user_id]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'site_notifications';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }
    }
}
?>