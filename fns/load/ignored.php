<?php

if (role(['permissions' => ['site_users' => 'ignore_users']])) {

    $user_id = Registry::load('current_user')->id;
    $columns = [
        'site_users.user_id', 'site_users.display_name', 'site_users.email_address',
        'site_users.username', 'site_users_blacklist.user_blacklist_id'
    ];

    $join["[>]site_users"] = ["site_users_blacklist.blacklisted_user_id" => "user_id"];
    $where["site_users_blacklist.user_id"] = $user_id;
    $where["site_users_blacklist.ignore"] = 1;

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["site_users_blacklist.user_blacklist_id[!]"] = $data["offset"];
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

    $site_users = DB::connect()->select('site_users_blacklist', $join, $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->ignored;
    $output['loaded']->offset = array();

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'ignored';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->name;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'ignored';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->name;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'ignored';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    foreach ($site_users as $user) {

        $output['loaded']->offset[] = $user['user_blacklist_id'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $user['user_id'], 'gravatar' => $user['email_address']]);
        $output['content'][$i]->title = $user['display_name'];
        $output['content'][$i]->class = "ignored_user";
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        $output['content'][$i]->subtitle = $user['username'];

        $output['options'][$i][1] = new stdClass();
        $output['options'][$i][1]->option = Registry::load('strings')->unignore;
        $output['options'][$i][1]->class = 'ask_confirmation';
        $output['options'][$i][1]->attributes['data-update'] = 'site_user_blacklist';
        $output['options'][$i][1]->attributes['data-unignore_user_id'] = $user['user_id'];
        $output['options'][$i][1]->attributes['confirmation'] = Registry::load('strings')->unignore_user_confirmation;
        $output['options'][$i][1]->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['options'][$i][1]->attributes['cancel_button'] = Registry::load('strings')->no;

        $output['options'][$i][2] = new stdClass();
        $output['options'][$i][2]->option = Registry::load('strings')->profile;
        $output['options'][$i][2]->class = 'get_info';
        $output['options'][$i][2]->attributes['user_id'] = $user['user_id'];

        $i++;
    }
}
?>