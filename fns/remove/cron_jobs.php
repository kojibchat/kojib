<?php
$result = array();
$noerror = true;

$result['success'] = false;
$result['error_message'] = Registry::load('strings')->went_wrong;
$result['error_key'] = 'something_went_wrong';
$cron_job_ids = $string_constants = array();

if (role(['permissions' => ['super_privileges' => 'cron_jobs']])) {

    if (isset($data['cron_job_id'])) {
        if (!is_array($data['cron_job_id'])) {
            $data["cron_job_id"] = filter_var($data["cron_job_id"], FILTER_SANITIZE_NUMBER_INT);
            $cron_job_ids[] = $data["cron_job_id"];
        } else {
            $cron_job_ids = array_filter($data["cron_job_id"], 'ctype_digit');
        }
    }

    if (!empty($cron_job_ids)) {

        DB::connect()->delete("cron_jobs", ["cron_job_id" => $cron_job_ids]);

        if (!DB::connect()->error) {

            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'cron_jobs';
        } else {
            $result['error_message'] = Registry::load('strings')->went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }
    }
}
?>