<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$complaint_ids = array();

if (role(['permissions' => ['complaints' => 'review_complaints']])) {
    if (role(['permissions' => ['complaints' => 'delete_complaints']])) {

        if (isset($data['complaint_id'])) {
            if (!is_array($data['complaint_id'])) {
                $data["complaint_id"] = filter_var($data["complaint_id"], FILTER_SANITIZE_NUMBER_INT);
                $complaint_ids[] = $data["complaint_id"];
            } else {
                $complaint_ids = array_filter($data["complaint_id"], 'ctype_digit');
            }
        }

        if (!empty($complaint_ids)) {

            DB::connect()->delete("complaints", ["complaint_id" => $complaint_ids]);

            if (!DB::connect()->error) {
                $result = array();
                $result['success'] = true;
                $result['todo'] = 'reload';
                $result['reload'] = 'complaints';
            } else {
                $result['errormsg'] = Registry::load('strings')->went_wrong;
            }
        }
    }
}
?>