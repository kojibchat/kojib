<?php

if (role(['permissions' => ['super_privileges' => 'view_statistics']])) {

    update_online_statuses();

    include 'fns/files/load.php';
    $index = 1;

    $output = array();

    $child_index = 0;
    $output['module'][$index] = new stdClass();
    $output['module'][$index]->type = 'numbers';

    $items = array();
    $items[$child_index]['title'] = Registry::load('strings')->total_users;
    $items[$child_index]['result'] = DB::connect()->count('site_users');
    $items[$child_index]['attributes'] = [
        'class' => 'load_aside',
        'load' => 'site_users',
        'role' => 'button'
    ];
    $child_index++;


    $offline_time = Registry::load('settings')->change_to_offline_status_after;

    if (empty($offline_time)) {
        $offline_time = 10;
    }

    $time_from = get_date();
    $time_from = strtotime($time_from);
    $time_from = $time_from - ($offline_time * 60);
    $time_from = date("Y-m-d H:i:s", $time_from);

    DB::connect()->update("site_users", ["online_status" => 0], [
        "last_seen_on[<]" => $time_from,
        "OR" => ["online_status" => 1, "online_status" => 2]
    ]);

    $items[$child_index]['title'] = Registry::load('strings')->online_users;
    $items[$child_index]['result'] = DB::connect()->count('site_users', ["OR" => ["online_status(online)" => 1, "online_status(idle)" => 2]]);
    $items[$child_index]['attributes'] = [
        'class' => 'load_aside',
        'load' => 'online',
        'role' => 'button'
    ];
    $child_index++;

    $items[$child_index]['title'] = Registry::load('strings')->total_groups;
    $items[$child_index]['result'] = DB::connect()->count('groups');
    $items[$child_index]['attributes'] = [
        'class' => 'load_aside',
        'load' => 'groups',
        'role' => 'button'
    ];
    $child_index++;

    $items[$child_index]['title'] = Registry::load('strings')->storage_usage;
    $items[$child_index]['result'] = files('getsize', ['getsize_of' => 'assets/files/storage/', 'real_path' => true]);
    $items[$child_index]['attributes'] = [
        'class' => 'load_aside',
        'load' => 'storage',
        'role' => 'button'
    ];
    $child_index++;

    $items[$child_index]['title'] = Registry::load('strings')->complaints;
    $items[$child_index]['result'] = DB::connect()->count('complaints', ['complaint_status' => 0]);
    $items[$child_index]['attributes'] = [
        'class' => 'load_aside',
        'load' => 'complaints',
        'role' => 'button'
    ];
    $child_index++;

    $items[$child_index]['title'] = Registry::load('strings')->users_banned;
    $items[$child_index]['attributes'] = [
        'class' => 'load_aside',
        'load' => 'site_users',
        'filter' => 'banned',
        'skip_filter_title' => true,
        'role' => 'button'
    ];

    $columns = $where = $join = null;
    $columns = ['site_users.user_id'];
    $join["[>]site_roles"] = ["site_users.site_role_id" => "site_role_id"];
    $where = ['site_roles.site_role_attribute' => 'banned_users'];

    $items[$child_index]['result'] = DB::connect()->count('site_users', $join, $columns, $where);
    $child_index++;

    $items[$child_index]['title'] = Registry::load('strings')->pending_approval;
    $items[$child_index]['result'] = DB::connect()->count('site_users', ['approved' => 0]);
    $items[$child_index]['attributes'] = [
        'class' => 'load_aside',
        'load' => 'site_users',
        'filter' => 'pending_approval',
        'skip_filter_title' => true,
        'role' => 'button'
    ];
    $child_index++;



    $items[$child_index]['title'] = Registry::load('strings')->guest_users;
    $items[$child_index]['attributes'] = [
        'class' => 'load_aside',
        'load' => 'site_users',
        'filter' => 'guest_users',
        'skip_filter_title' => true,
        'role' => 'button'
    ];

    $columns = $where = $join = null;
    $columns = ['site_users.user_id'];
    $join["[>]site_roles"] = ["site_users.site_role_id" => "site_role_id"];
    $where = ['site_roles.site_role_attribute' => 'guest_users'];

    $items[$child_index]['result'] = DB::connect()->count('site_users', $join, $columns, $where);
    $child_index++;

    $items[$child_index]['title'] = Registry::load('strings')->unverified;
    $items[$child_index]['attributes'] = [
        'class' => 'load_aside',
        'load' => 'site_users',
        'filter' => 'unverified_users',
        'skip_filter_title' => true,
        'role' => 'button'
    ];

    $columns = $where = $join = null;
    $columns = ['site_users.user_id'];
    $join["[>]site_roles"] = ["site_users.site_role_id" => "site_role_id"];
    $where = ['site_roles.site_role_attribute' => 'unverified_users'];

    $items[$child_index]['result'] = DB::connect()->count('site_users', $join, $columns, $where);
    $child_index++;



    $output['module'][$index]->items = $items;
    $index++;



    $output['module'][$index] = new stdClass();
    $output['module'][$index]->title = Registry::load('strings')->recently_joined;
    $output['module'][$index]->type = 'list';

    $child_index = 0;
    $items = array();

    $columns = $where = $join = null;
    $columns = [
        'site_users.user_id', 'site_users.display_name', 'site_users.email_address',
        'site_roles.string_constant', 'site_users.created_on', 'site_users.username'
    ];

    $join["[>]site_roles"] = ["site_users.site_role_id" => "site_role_id"];

    $where["ORDER"] = ["site_users.user_id" => "DESC"];
    $where["LIMIT"] = 15;

    $site_users = DB::connect()->select('site_users', $join, $columns, $where);

    foreach ($site_users as $user) {
        $user_image = get_image(['from' => 'site_users/profile_pics', 'search' => $user['user_id'], 'gravatar' => $user['email_address']]);
        $created_on = array();
        $created_on['date'] = $user['created_on'];
        $created_on['auto_format'] = true;
        $created_on['include_time'] = true;
        $created_on['timezone'] = Registry::load('current_user')->time_zone;
        $created_on = get_date($created_on);

        $items[$child_index] = new stdClass();
        $items[$child_index]->items[1]['type'] = 'image';
        $items[$child_index]->items[1]['image'] = $user_image;

        $items[$child_index]->items[2]['type'] = 'info';
        $items[$child_index]->items[2]['bold_text'] = $user['display_name'];
        $items[$child_index]->items[2]['text'] = $user['username'];

        $site_role_name = $user['string_constant'];

        if (!isset(Registry::load('strings')->$site_role_name)) {
            Registry::load('strings')->$site_role_name = 'Unknown';
        }

        $items[$child_index]->items[3]['type'] = 'info';
        $items[$child_index]->items[3]['text'] = Registry::load('strings')->$site_role_name;

        $items[$child_index]->items[4]['type'] = 'info';
        $items[$child_index]->items[4]['text'] = $created_on['date'].'<br>'.$created_on['time'];

        $items[$child_index]->items[5]['type'] = 'button';
        $items[$child_index]->items[5]['text'] = Registry::load('strings')->view;
        $items[$child_index]->items[5]['attributes']['class'] = 'get_info';
        $items[$child_index]->items[5]['attributes']['user_id'] = $user['user_id'];
        $child_index++;
    }


    $output['module'][$index]->items = $items;

    $index++;

}
?>