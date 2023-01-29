<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$access_log_ids = array();

if (role(['permissions' => ['site_users' => 'manage_user_access_logs']])) {

    if (isset($data['access_log_id'])) {
        if (!is_array($data['access_log_id'])) {
            $data["access_log_id"] = filter_var($data["access_log_id"], FILTER_SANITIZE_NUMBER_INT);
            $access_log_ids[] = $data["access_log_id"];
        } else {
            $access_log_ids = array_filter($data["access_log_id"], 'ctype_digit');
        }
    }

    if (isset($data['access_log_id']) && !empty($data['access_log_id'])) {

        DB::connect()->delete("site_users_device_logs", ["access_log_id" => $access_log_ids]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'access_logs';
        } else {
            $result['errormsg'] = Registry::load('strings')->went_wrong;
        }
    }
}
?>