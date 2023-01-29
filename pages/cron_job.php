<?php
include 'fns/firewall/load.php';
include 'fns/sql/load.php';
include 'fns/variables/load.php';
include 'fns/cron_jobs/load.php';

$cron_job_parameters = explode('/', get_url(['path' => true]));

if (isset($cron_job_parameters[2]) && !empty($cron_job_parameters[2])) {

    $cron_job = array();
    $cron_job['cron_job_id'] = $cron_job_parameters[1];
    $cron_job['access_code'] = $cron_job_parameters[2];
    $cron_job['return'] = true;

    $result = cron_job($cron_job);

    if (isset($result['success']) && $result['success']) {
        echo "[Cronjob Executed Sucessfully]";
    } else {
        echo "[Something Went Wrong]";
    }
    
} else {
    rt('404');
}
?>