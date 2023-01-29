<?php

if (role(['permissions' => ['social_login_providers' => 'view']])) {

    $columns = [
        'social_login_providers.social_login_provider_id', 'social_login_providers.identity_provider', 'social_login_providers.disabled'
    ];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["social_login_providers.station_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {
        $where["social_login_providers.identity_provider[~]"] = $data["search"];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if ($data["sortby"] === 'name_asc') {
        $where["ORDER"] = ["social_login_providers.identity_provider" => "ASC"];
    } else if ($data["sortby"] === 'name_desc') {
        $where["ORDER"] = ["social_login_providers.identity_provider" => "DESC"];
    } else if ($data["sortby"] === 'status_asc') {
        $where["ORDER"] = ["social_login_providers.disabled" => "ASC"];
    } else if ($data["sortby"] === 'status_desc') {
        $where["ORDER"] = ["social_login_providers.disabled" => "DESC"];
    } else {
        $where["ORDER"] = ["social_login_providers.social_login_provider_id" => "DESC"];
    }

    $providers = DB::connect()->select('social_login_providers', $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->social_login;
    $output['loaded']->loaded = 'social_login_providers';
    $output['loaded']->offset = array();

    if (role(['permissions' => ['social_login_providers' => 'delete']])) {

        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'social_login_providers';
        $output['multiple_select']->attributes['multi_select'] = 'social_login_provider_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }

    if (role(['permissions' => ['social_login_providers' => 'add']])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_form';
        $output['todo']->title = Registry::load('strings')->add_provider;
        $output['todo']->attributes['form'] = 'social_login_providers';
    }

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'social_login_providers';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->name;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'social_login_providers';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->name;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'social_login_providers';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    $output['sortby'][4] = new stdClass();
    $output['sortby'][4]->sortby = Registry::load('strings')->status;
    $output['sortby'][4]->class = 'load_aside sort_asc';
    $output['sortby'][4]->attributes['load'] = 'social_login_providers';
    $output['sortby'][4]->attributes['sort'] = 'status_asc';

    $output['sortby'][5] = new stdClass();
    $output['sortby'][5]->sortby = Registry::load('strings')->status;
    $output['sortby'][5]->class = 'load_aside sort_desc';
    $output['sortby'][5]->attributes['load'] = 'social_login_providers';
    $output['sortby'][5]->attributes['sort'] = 'status_desc';

    foreach ($providers as $provider) {
        $output['loaded']->offset[] = $provider['social_login_provider_id'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = get_image(['from' => 'social_login', 'search' => $provider['social_login_provider_id']]);
        $output['content'][$i]->title = stripslashes($provider['identity_provider']);
        $output['content'][$i]->identifier = $provider['social_login_provider_id'];
        $output['content'][$i]->class = "social_login_provider";

        if ((int)$provider['disabled'] === 1) {
            $output['content'][$i]->subtitle = Registry::load('strings')->disabled;
        } else {
            $output['content'][$i]->subtitle = Registry::load('strings')->enabled;
        }

        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        if (role(['permissions' => ['social_login_providers' => 'edit']])) {
            $output['options'][$i][1] = new stdClass();
            $output['options'][$i][1]->option = Registry::load('strings')->edit;
            $output['options'][$i][1]->class = 'load_form';
            $output['options'][$i][1]->attributes['form'] = 'social_login_providers';
            $output['options'][$i][1]->attributes['data-social_login_provider_id'] = $provider['social_login_provider_id'];
        }

        if (role(['permissions' => ['social_login_providers' => 'delete']])) {
            $output['options'][$i][2] = new stdClass();
            $output['options'][$i][2]->class = 'ask_confirmation';
            $output['options'][$i][2]->option = Registry::load('strings')->delete;
            $output['options'][$i][2]->attributes['data-remove'] = 'social_login_providers';
            $output['options'][$i][2]->attributes['data-social_login_provider_id'] = $provider['social_login_provider_id'];
            $output['options'][$i][2]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][2]->attributes['cancel_button'] = Registry::load('strings')->no;
            $output['options'][$i][2]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
        }

        $i++;
    }
}
?>