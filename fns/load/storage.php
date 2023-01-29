<?php

if (role(['permissions' => ['storage' => 'super_privileges']])) {

    include 'fns/files/load.php';

    $columns = [
        'site_users.user_id', 'site_users.display_name', 'site_users.email_address'
    ];

    if (empty($data["search"]) && empty($data["sortby"])) {
        $you['user_id'] = Registry::load('current_user')->id;
        $you['display_name'] = Registry::load('current_user')->name;
        $you['email_address'] = Registry::load('current_user')->email_address;
        $where = [
            "site_users.user_id[!]" => Registry::load('current_user')->id
        ];
    }

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["site_users.user_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {
        $where["AND #search_query"]["OR"] = ["site_users.display_name[~]" => $data["search"], "site_users.username[~]" => $data["search"], "site_users.email_address[~]" => $data["search"]];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if ($data["sortby"] === 'name_asc' || !empty($data["search"])) {
        $where["ORDER"] = ["site_users.display_name" => "ASC"];
    } else if ($data["sortby"] === 'name_desc') {
        $where["ORDER"] = ["site_users.display_name" => "DESC"];
    } else {
        $where["ORDER"] = ["site_users.updated_on" => "DESC"];
    }

    $users = DB::connect()->select('site_users', $columns, $where);

    if (empty($data["search"]) && empty($data["offset"]) && empty($data["sortby"])) {
        array_unshift($users, $you);
    }

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->storage;
    $output['loaded']->offset = array();

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    if (role(['permissions' => ['storage' => 'delete_files']])) {
        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'site_user_files';
        $output['multiple_select']->attributes['data-delete_all'] = true;
        $output['multiple_select']->attributes['multi_select'] = 'user_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->delete_all_files_confirmation;
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'storage';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->name;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'storage';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->name;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'storage';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    foreach ($users as $user) {
        $output['loaded']->offset[] = $user['user_id'];

        $location = 'assets/files/storage/'.$user['user_id'].'/files/';
        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $user['user_id'], 'gravatar' => $user['email_address']]);
        $output['content'][$i]->title = $user['display_name'];
        $output['content'][$i]->class = "folder";
        $output['content'][$i]->subtitle = files('getsize', ['getsize_of' => $location, 'real_path' => true]);
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;
        $output['content'][$i]->identifier = $user['user_id'];

        $output['options'][$i][1] = new stdClass();
        $output['options'][$i][1]->option = Registry::load('strings')->view;
        $output['options'][$i][1]->class = 'load_aside';
        $output['options'][$i][1]->attributes['load'] = 'site_user_files';
        $output['options'][$i][1]->attributes['check_conversation_loaded'] = true;
        $output['options'][$i][1]->attributes['data-user_id'] = $user['user_id'];

        if (role(['permissions' => ['storage' => 'delete_files']])) {
            $output['options'][$i][2] = new stdClass();
            $output['options'][$i][2]->option = Registry::load('strings')->delete_all;
            $output['options'][$i][2]->class = 'ask_confirmation';
            $output['options'][$i][2]->attributes['data-remove'] = 'site_user_files';
            $output['options'][$i][2]->attributes['data-user_id'] = $user['user_id'];
            $output['options'][$i][2]->attributes['data-delete_all'] = true;
            $output['options'][$i][2]->attributes['confirmation'] = Registry::load('strings')->delete_all_files_confirmation;
            $output['options'][$i][2]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][2]->attributes['cancel_button'] = Registry::load('strings')->no;
        }

        $i++;
    }
}
?>