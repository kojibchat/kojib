<?php

$data["pending_friend_requests"] = filter_var($data["pending_friend_requests"], FILTER_SANITIZE_NUMBER_INT);

if (empty($data["pending_friend_requests"])) {
    $data["pending_friend_requests"] = 0;
}

$columns = $join = $where = null;

$where["friends.to_user_id"] = Registry::load('current_user')->id;
$where ["friends.relation_status"] = 0;

$pending_friend_requests = DB::connect()->count('friends', $where);

if ((int)$pending_friend_requests !== (int)$data["pending_friend_requests"]) {
    $result['pending_friend_requests'] = $pending_friend_requests;
    $escape = true;
}