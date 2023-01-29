<?php

$data["last_realtime_log_id"] = filter_var($data["last_realtime_log_id"], FILTER_SANITIZE_NUMBER_INT);

if (empty($data["last_realtime_log_id"])) {
    $data["last_realtime_log_id"] = 0;
}


$time_from = Registry::load('current_user')->time_stamp;
$time_from = strtotime($time_from);
$time_from = $time_from - (5 * 60);
$time_from = date("Y-m-d H:i:s", $time_from);

$columns = $join = $where = null;

$columns = ['realtime_logs.realtime_log_id', 'realtime_logs.log_type', 'realtime_logs.related_parameters'];
$where["realtime_logs.realtime_log_id[>]"] = $data["last_realtime_log_id"];
$where["realtime_logs.created_on[>]"] = $time_from;
$where["LIMIT"] = 10;

$unread_realtime_logs = DB::connect()->select('realtime_logs', $columns, $where);

$total_unread = count($unread_realtime_logs);

if ($total_unread > 0) {
    $result['unread_realtime_logs'] = $unread_realtime_logs;

    $total_unread = $total_unread-1;
    $result['last_realtime_log_id'] = 0;

    if (isset($unread_realtime_logs[$total_unread])) {
        $result['last_realtime_log_id'] = $unread_realtime_logs[$total_unread]['realtime_log_id'];
    }

    $escape = true;
}