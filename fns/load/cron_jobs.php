<?php

if (role(['permissions' => ['super_privileges' => 'cron_jobs']])) {

    $columns = [
        'cron_jobs.cron_job_id', 'cron_jobs.cron_job',
    ];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["cron_jobs.cron_job_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {

        $id_search = filter_var($data["search"], FILTER_SANITIZE_NUMBER_INT);

        if (empty($id_search)) {
            $id_search = 0;
        }

        $where["AND #search_query"]["OR"] = [
            "cron_jobs.cron_job_id[~]" => $id_search,
            "cron_jobs.cron_job[~]" => $data["search"]
        ];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;
    $where["ORDER"] = ["cron_jobs.cron_job_id" => "DESC"];

    $cron_jobs = DB::connect()->select('cron_jobs', $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->cron_jobs;
    $output['loaded']->loaded = 'cron_jobs';
    $output['loaded']->offset = array();

    $output['todo'] = new stdClass();
    $output['todo']->class = 'load_form';
    $output['todo']->title = Registry::load('strings')->add_cron_job;
    $output['todo']->attributes['form'] = 'cron_jobs';

    $output['multiple_select'] = new stdClass();
    $output['multiple_select']->title = Registry::load('strings')->delete;
    $output['multiple_select']->attributes['class'] = 'ask_confirmation';
    $output['multiple_select']->attributes['data-remove'] = 'cron_jobs';
    $output['multiple_select']->attributes['multi_select'] = 'cron_job_id';
    $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
    $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
    $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;


    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    foreach ($cron_jobs as $cron_job) {
        $output['loaded']->offset[] = $cron_job['cron_job_id'];
        $output['content'][$i] = new stdClass();
        $output['content'][$i]->alphaicon = true;
        $output['content'][$i]->identifier = $cron_job['cron_job_id'];
        $output['content'][$i]->title = 'CRON#'.$cron_job['cron_job_id'];
        $output['content'][$i]->class = "group";
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        $output['content'][$i]->subtitle = Registry::load('strings')->cron_job;

        $scheduled_job = $cron_job['cron_job'];

        if (isset(Registry::load('strings')->$scheduled_job)) {
            $output['content'][$i]->subtitle = Registry::load('strings')->$scheduled_job;
        }


        $output['options'][$i][1] = new stdClass();
        $output['options'][$i][1]->option = Registry::load('strings')->edit;
        $output['options'][$i][1]->class = 'load_form';
        $output['options'][$i][1]->attributes['form'] = 'cron_jobs';
        $output['options'][$i][1]->attributes['data-cron_job_id'] = $cron_job['cron_job_id'];

        $output['options'][$i][2] = new stdClass();
        $output['options'][$i][2]->option = Registry::load('strings')->delete;
        $output['options'][$i][2]->class = 'ask_confirmation';
        $output['options'][$i][2]->attributes['data-remove'] = 'cron_jobs';
        $output['options'][$i][2]->attributes['data-cron_job_id'] = $cron_job['cron_job_id'];
        $output['options'][$i][2]->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['options'][$i][2]->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['options'][$i][2]->attributes['confirmation'] = Registry::load('strings')->confirm_action;

        $i++;
    }
}
?>