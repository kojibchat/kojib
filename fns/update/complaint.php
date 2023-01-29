<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';

if (role(['permissions' => ['complaints' => 'review_complaints']])) {

    $noerror = true;
    $complaint_id = 0;

    $result['success'] = false;
    $result['error_message'] = Registry::load('strings')->invalid_value;
    $result['error_key'] = 'invalid_value';
    $result['error_variables'] = [];

    if (!isset($data['complaint_status']) || empty($data['complaint_status'])) {
        $result['error_variables'][] = ['complaint_status'];
        $noerror = false;
    }

    if (isset($data['complaint_id'])) {
        $complaint_id = filter_var($data["complaint_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if ($noerror && !empty($complaint_id)) {

        $complaint_status = 0;

        if ($data['complaint_status'] === 'action_taken') {
            $complaint_status = 1;
        } else if ($data['complaint_status'] === 'rejected') {
            $complaint_status = 2;
        }

        if (isset($data['comments'])) {
            $data['comments'] = htmlspecialchars($data['comments'], ENT_QUOTES, 'UTF-8');
        } else {
            $data['comments'] = '';
        }


        DB::connect()->update("complaints", [
            "complaint_status" => $complaint_status,
            "comments_by_reviewer" => $data['comments'],
            "reviewer_user_id" => Registry::load('current_user')->id,
            "updated_on" => Registry::load('current_user')->time_stamp,
        ], ["complaint_id" => $complaint_id]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'complaints';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }

    }
}

?>