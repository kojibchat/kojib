<?php

$data["unresolved_complaints"] = filter_var($data["unresolved_complaints"], FILTER_SANITIZE_NUMBER_INT);

if (empty($data["unresolved_complaints"])) {
    $data["unresolved_complaints"] = 0;
}

$columns = $join = $where = null;

if (role(['permissions' => ['complaints' => 'review_complaints']])) {
    $where["complaints.complaint_id[!]"] = 0;
} else if (role(['permissions' => ['complaints' => 'track_status']])) {
    $where["complaints.complainant_user_id"] = Registry::load('current_user')->id;
}

$where["complaint_status"] = 0;

$unresolved_complaints = DB::connect()->count('complaints', $where);

if ((int)$unresolved_complaints !== (int)$data["unresolved_complaints"]) {
    $result['unresolved_complaints'] = $unresolved_complaints;
    $escape = true;
}