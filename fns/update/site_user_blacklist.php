<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

$current_user_id = Registry::load('current_user')->id;
$ignore = $block = $blacklist_user_id = 0;

if (isset($data['ignore_user_id']) && role(['permissions' => ['site_users' => 'ignore_users']])) {
    $blacklist_user_id = filter_var($data["ignore_user_id"], FILTER_SANITIZE_NUMBER_INT);
    $ignore = 1;
} else if (isset($data['block_user_id']) && role(['permissions' => ['site_users' => 'block_users']])) {
    $blacklist_user_id = filter_var($data["block_user_id"], FILTER_SANITIZE_NUMBER_INT);
    $block = 1;
} else if (isset($data['unignore_user_id']) && role(['permissions' => ['site_users' => 'ignore_users']])) {
    $blacklist_user_id = filter_var($data["unignore_user_id"], FILTER_SANITIZE_NUMBER_INT);
} else if (isset($data['unblock_user_id']) && role(['permissions' => ['site_users' => 'block_users']])) {
    $blacklist_user_id = filter_var($data["unblock_user_id"], FILTER_SANITIZE_NUMBER_INT);
}

if (!empty($blacklist_user_id) && (int)$blacklist_user_id !== (int)$current_user_id) {

    $columns = $join = $where = null;
    $columns = ['site_roles.site_role_attribute'];

    $join["[>]site_roles"] = ["site_users.site_role_id" => "site_role_id"];

    $where["site_users.user_id"] = $blacklist_user_id;
    $where["LIMIT"] = 1;
    $user_info = DB::connect()->select('site_users', $join, $columns, $where);

    if (isset($user_info[0]) && $user_info[0]['site_role_attribute'] === 'administrators') {
        $result = array();
        $result['success'] = false;
        $result['error_message'] = Registry::load('strings')->blacklist_user_permission_denied;
        $result['error_key'] = 'permission_denied';

    } else {

        $columns = $join = $where = null;
        $columns = ['user_blacklist_id'];

        $where["user_id"] = $current_user_id;
        $where["blacklisted_user_id"] = $blacklist_user_id;

        $verify_blacklist = DB::connect()->select('site_users_blacklist', $columns, $where);

        if (isset($verify_blacklist[0])) {

            if (isset($data['ignore_user_id']) || isset($data['unignore_user_id'])) {
                DB::connect()->update("site_users_blacklist", ["ignore" => $ignore], $where);
            } else if (isset($data['block_user_id']) || isset($data['unblock_user_id'])) {
                DB::connect()->update("site_users_blacklist", ["block" => $block], $where);
            }

        } else {

            DB::connect()->insert("site_users_blacklist", [
                "user_id" => $current_user_id,
                "blacklisted_user_id" => $blacklist_user_id,
                "ignore" => $ignore,
                "block" => $block,
                "updated_on" => Registry::load('current_user')->time_stamp,
            ]);
        }

        $result = array();
        $result['success'] = true;
        $result['todo'] = 'refresh';
    }
}

?>