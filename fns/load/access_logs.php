<?php
use Medoo\Medoo;

if (role(['permissions' => ['site_users' => 'manage_user_access_logs']])) {

    if (isset($data["user_id"])) {

        $columns = [
            'site_users_device_logs.access_log_id', 'site_users_device_logs.ip_address', 'site_users_device_logs.created_on'
        ];


        if (!empty($data["offset"])) {
            $data["offset"] = array_map('intval', explode(',', $data["offset"]));
            $where["site_users_device_logs.access_log_id[!]"] = $data["offset"];
        }

        if (!empty($data["search"])) {
            $where["AND #search_query"]["OR"] = ["site_users_device_logs.ip_address[~]" => $data["search"], "site_users_device_logs.user_agent[~]" => $data["search"]];
        }

        $where["site_users_device_logs.user_id"] = $data["user_id"];

        $where["LIMIT"] = Registry::load('settings')->records_per_call;
        $where["ORDER"] = [
            "site_users_device_logs.access_log_id" => "DESC",
            "site_users_device_logs.user_id"
        ];

        $device_logs = DB::connect()->select('site_users_device_logs', $columns, $where);

        $i = 1;
        $output = array();
        $output['loaded'] = new stdClass();
        $output['loaded']->title = Registry::load('strings')->access_logs;
        $output['loaded']->loaded = 'access_logs';
        $output['loaded']->offset = array();

        if (!empty($data["offset"])) {
            $output['loaded']->offset = $data["offset"];
        }


        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'access_logs';
        $output['multiple_select']->attributes['multi_select'] = 'access_log_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;


        foreach ($device_logs as $device_log) {
            $output['loaded']->offset[] = $device_log['access_log_id'];

            $output['content'][$i] = new stdClass();
            $output['content'][$i]->image = Registry::load('config')->site_url."assets/files/defaults/access_log.png";
            $output['content'][$i]->title = $device_log['ip_address'];
            $output['content'][$i]->identifier = $device_log['access_log_id'];
            $output['content'][$i]->class = "device_log square";
            $output['content'][$i]->icon = 0;
            $output['content'][$i]->unread = 0;

            $access_date = array();
            $access_date['date'] = $device_log['created_on'];
            $access_date['auto_format'] = true;
            $access_date['include_time'] = true;
            $access_date['timezone'] = Registry::load('current_user')->time_zone;
            $access_date = get_date($access_date);

            $output['content'][$i]->subtitle = $access_date['date'].' '.$access_date['time'];


            $output['options'][$i][2] = new stdClass();
            $output['options'][$i][2]->option = Registry::load('strings')->view;
            $output['options'][$i][2]->class = 'load_form';
            $output['options'][$i][2]->attributes['form'] = 'access_log';
            $output['options'][$i][2]->attributes['data-access_log_id'] = $device_log['access_log_id'];


            $output['options'][$i][3] = new stdClass();
            $output['options'][$i][3]->option = Registry::load('strings')->delete;
            $output['options'][$i][3]->class = 'ask_confirmation';
            $output['options'][$i][3]->attributes['data-info_box'] = true;
            $output['options'][$i][3]->attributes['data-remove'] = 'access_logs';
            $output['options'][$i][3]->attributes['data-access_log_id'] = $device_log['access_log_id'];
            $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->delete_access_log_confirmation;
            $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;


            $i++;
        }
    }
}
?>