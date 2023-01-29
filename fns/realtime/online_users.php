<?php

$data["recent_online_user_id"] = filter_var($data["recent_online_user_id"], FILTER_SANITIZE_NUMBER_INT);

if (empty($data["recent_online_user_id"])) {
    $data["recent_online_user_id"] = 0;
}

if (empty($data["recent_online_user_online_status"])) {
    $data["recent_online_user_online_status"] = 0;
}

update_online_statuses();

$columns = $join = $where = null;
$order_by_last_seen = false;


$columns = ['site_users.user_id', 'site_users.online_status'];

$where["site_users.online_status[!]"] = 0;

if ($order_by_last_seen) {
    $where["ORDER"] = ["site_users.last_seen_on" => "DESC"];
} else {
    $where["ORDER"] = ["site_users.last_login_session" => "DESC"];
}


$total_online_users = DB::connect()->count('site_users', $where);

$where["LIMIT"] = 1;
$recent_online_user_id = DB::connect()->select('site_users', $columns, $where);

if (isset($recent_online_user_id[0])) {

    $recent_online_user_online_status = $recent_online_user_id[0]['online_status'];
    $recent_online_user_id = $recent_online_user_id[0]['user_id'];

    if ($total_online_users !== (int)$data["total_online_users"] || (int)$recent_online_user_id !== (int)$data["recent_online_user_id"] || (int)$recent_online_user_online_status !== (int)$data["recent_online_user_online_status"]) {
        $result['recent_online_user_id'] = $recent_online_user_id;
        $result['recent_online_user_online_status'] = $recent_online_user_online_status;
        $result['total_online_users'] = $total_online_users;
        $escape = true;
    }
} else {
    if ((int)$data["recent_online_user_id"] !== 0) {
        $result['recent_online_user_id'] = 0;
        $result['total_online_users'] = 0;
        $result['recent_online_user_online_status'] = 0;
        $escape = true;
    }
}