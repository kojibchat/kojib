<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$no_error = false;

if (isset($data['ban_user_id']) && role(['permissions' => ['site_users' => 'ban_users_from_site']])) {
    $data["user_id"] = filter_var($data["ban_user_id"], FILTER_SANITIZE_NUMBER_INT);
    if (!empty($data['user_id'])) {
        $banned_role_id = DB::connect()->select('site_roles', ['site_role_id'], ['site_roles.site_role_attribute' => 'banned_users']);

        if (isset($banned_role_id[0])) {
            $data["site_role_id"] = $banned_role_id[0]['site_role_id'];
            $where = [
                'login_sessions.user_id' => $data["user_id"],
                'login_sessions.status' => 1,
            ];
            DB::connect()->update('login_sessions', ['status' => 2], $where);

            $update_status = [
                'online_status' => 0,
                "last_seen_on" => Registry::load('current_user')->time_stamp,
                "updated_on" => Registry::load('current_user')->time_stamp,
            ];
            DB::connect()->update('site_users', $update_status, ['user_id' => $data["user_id"]]);

            $no_error = true;
        }
    }
} else if (isset($data['unban_user_id']) && role(['permissions' => ['site_users' => 'unban_users_from_site']])) {
    $data["user_id"] = filter_var($data["unban_user_id"], FILTER_SANITIZE_NUMBER_INT);
    if (!empty($data['user_id'])) {
        $where = null;
        $previous_role_id = DB::connect()->select('site_users', ['previous_site_role_id'], ['user_id' => $data['user_id']]);

        if (isset($previous_role_id[0])) {
            $data["site_role_id"] = $previous_role_id[0]['previous_site_role_id'];
            $no_error = true;
        }
    }
}
if (!isset($data['ban_user_id']) && !isset($data['unban_user_id'])) {
    if (role(['permissions' => ['site_users' => 'edit_users']])) {
        $no_error = true;
    }
}

if ($no_error && isset($data['user_id']) && isset($data["site_role_id"])) {
    $data["user_id"] = filter_var($data["user_id"], FILTER_SANITIZE_NUMBER_INT);
    $data["site_role_id"] = filter_var($data["site_role_id"], FILTER_SANITIZE_NUMBER_INT);

    if (!empty($data['user_id']) && !empty($data['site_role_id'])) {

        $columns = $where = $join = null;

        $columns = ['site_users.site_role_id', 'site_roles.site_role_attribute'];
        $join["[>]site_roles"] = ["site_users.site_role_id" => "site_role_id"];

        $site_user = DB::connect()->select('site_users', $join, $columns, ['user_id' => $data['user_id']]);


        if (isset($site_user[0])) {

            $columns = $join = $where = null;

            if (isset($data['ban_user_id']) || isset($data['unban_user_id'])) {
                if ($site_user[0]['site_role_attribute'] === 'administrators') {
                    $result['error_message'] = Registry::load('strings')->permission_denied;
                    $result['error_key'] = 'permission_denied';
                    return;
                }
            }

            if ((int)$site_user[0]['site_role_id'] !== (int)$data['site_role_id']) {
                DB::connect()->update("site_users", [
                    "site_role_id" => $data['site_role_id'],
                    "previous_site_role_id" => $site_user[0]['site_role_id'],
                    "updated_on" => Registry::load('current_user')->time_stamp,
                ], ['user_id' => $data['user_id']]);
            }

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = ['site_users', 'online'];

            if (isset($data['info_box'])) {
                $result['info_box']['user_id'] = $data['user_id'];
            }

        }
    }
}
?>