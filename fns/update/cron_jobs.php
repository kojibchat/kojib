<?php

$result = array();
$result['success'] = false;
$result['error_message'] = Registry::load('strings')->something_went_wrong;
$result['error_key'] = 'something_went_wrong';
$noerror = true;
$cron_job_id = 0;
$cron_jobs = ["delete_group_messages", "delete_private_messages", "delete_site_users", "delete_user_files"];

if (role(['permissions' => ['super_privileges' => 'cron_jobs']])) {

    if (!isset($data['cron_job']) || empty(trim($data['cron_job'])) || !in_array($data['cron_job'], $cron_jobs)) {
        $noerror = false;
        $result = array();
        $result['success'] = false;
        $result['error_message'] = Registry::load('strings')->invalid_value;
        $result['error_key'] = 'invalid_value';
        $result['error_variables'][] = ['cron_job'];
    }

    if (isset($data['cron_job_id'])) {
        $cron_job_id = filter_var($data["cron_job_id"], FILTER_SANITIZE_NUMBER_INT);
    }

    if ($noerror && !empty($cron_job_id)) {

        include 'fns/filters/load.php';

        $disabled = 0;
        $remove = ['update', 'process', 'add', 'cron_job', 'cron_job_identifier', 'cron_job_url', 'command'];
        $parameters = sanitize_array($data);
        $parameters = array_diff_key($parameters, array_flip($remove));
        $parameters = json_encode($parameters);
        $access_code = random_string('5');

        DB::connect()->update("cron_jobs", [
            "cron_job" => $data['cron_job'],
            "cron_job_parameters" => $parameters,
            "updated_on" => Registry::load('current_user')->time_stamp,
        ], ["cron_job_id" => $cron_job_id]);

        if (!DB::connect()->error) {
            $result = array();
            $result['success'] = true;
            $result['todo'] = 'reload';
            $result['reload'] = 'cron_jobs';
        } else {
            $result['error_message'] = Registry::load('strings')->something_went_wrong;
            $result['error_key'] = 'something_went_wrong';
        }

    }
}
?>