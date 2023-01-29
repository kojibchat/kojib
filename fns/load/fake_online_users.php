<?php

if (role(['permissions' => ['site_users' => 'set_fake_online_users']])) {

    $user_id = Registry::load('current_user')->id;
    $columns = [
        'site_users.user_id', 'site_users.display_name', 'site_users.email_address',
        'site_users.username', 'site_users.stay_online'
    ];

    if (isset($data["add_users"]) && !empty($data["add_users"])) {
        $where["site_users.stay_online"] = 0;
    } else {
        $where["site_users.stay_online[!]"] = 0;
    }

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["site_users.user_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {
        $where["AND #search_query"]["OR"] = ["site_users.display_name[~]" => $data["search"], "site_users.username[~]" => $data["search"]];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if ($data["sortby"] === 'name_asc') {
        $where["ORDER"] = ["site_users.display_name" => "ASC"];
    } else if ($data["sortby"] === 'name_desc') {
        $where["ORDER"] = ["site_users.display_name" => "DESC"];
    } else {
        $where["ORDER"] = ["site_users.user_id" => "DESC"];
    }

    $site_users = DB::connect()->select('site_users', $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->fake_users;
    $output['loaded']->offset = array();

    if (isset($data["add_users"]) && !empty($data["add_users"])) {
        $output['loaded']->title = Registry::load('strings')->add_fake_users;
    }

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['multiple_select'] = new stdClass();
    $output['multiple_select']->attributes['class'] = 'ask_confirmation';
    $output['multiple_select']->attributes['multi_select'] = 'user_id';
    $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
    $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
    $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;

    if (isset($data["add_users"]) && !empty($data["add_users"])) {
        $output['multiple_select']->icon = 'bi bi-plus';
        $output['multiple_select']->title = Registry::load('strings')->add;
        $output['multiple_select']->attributes['data-add'] = 'fake_online_users';
    } else {
        $output['multiple_select']->title = Registry::load('strings')->remove;
        $output['multiple_select']->attributes['data-remove'] = 'fake_online_users';
    }

    if (!isset($data["add_users"]) || empty($data["add_users"])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_aside';
        $output['todo']->title = Registry::load('strings')->add_users;
        $output['todo']->attributes['load'] = 'fake_online_users';
        $output['todo']->attributes['data-add_users'] = true;
    }


    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'fake_online_users';

    if (isset($data["add_users"]) && !empty($data["add_users"])) {
        $output['sortby'][1]->attributes['data-add_users'] = true;
    }

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->name;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'fake_online_users';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    if (isset($data["add_users"]) && !empty($data["add_users"])) {
        $output['sortby'][2]->attributes['data-add_users'] = true;
    }

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->name;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'fake_online_users';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    if (isset($data["add_users"]) && !empty($data["add_users"])) {
        $output['sortby'][3]->attributes['data-add_users'] = true;
    }


    foreach ($site_users as $user) {

        $output['loaded']->offset[] = $user['user_id'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $user['user_id'], 'gravatar' => $user['email_address']]);
        $output['content'][$i]->title = $user['display_name'];
        $output['content'][$i]->class = "fake_online_user";
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;
        $output['content'][$i]->identifier = $user['user_id'];

        $output['content'][$i]->subtitle = $user['username'];

        if (isset($data["add_users"]) && !empty($data["add_users"])) {
            $output['options'][$i][1] = new stdClass();
            $output['options'][$i][1]->option = Registry::load('strings')->add;
            $output['options'][$i][1]->class = 'ask_confirmation';
            $output['options'][$i][1]->attributes['data-add'] = 'fake_online_users';
            $output['options'][$i][1]->attributes['data-user_id'] = $user['user_id'];
            $output['options'][$i][1]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            $output['options'][$i][1]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][1]->attributes['cancel_button'] = Registry::load('strings')->no;
        } else {
            $output['options'][$i][1] = new stdClass();
            $output['options'][$i][1]->option = Registry::load('strings')->remove;
            $output['options'][$i][1]->class = 'ask_confirmation';
            $output['options'][$i][1]->attributes['data-remove'] = 'fake_online_users';
            $output['options'][$i][1]->attributes['data-user_id'] = $user['user_id'];
            $output['options'][$i][1]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            $output['options'][$i][1]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][1]->attributes['cancel_button'] = Registry::load('strings')->no;
        }

        $output['options'][$i][2] = new stdClass();
        $output['options'][$i][2]->option = Registry::load('strings')->profile;
        $output['options'][$i][2]->class = 'get_info';
        $output['options'][$i][2]->attributes['user_id'] = $user['user_id'];

        $i++;
    }
}
?>