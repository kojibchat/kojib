<?php
$user_id = 0;

if (isset(Registry::load('config')->load_user_profile) && !empty(Registry::load('config')->load_user_profile)) {
    $user_id = Registry::load('config')->load_user_profile;
} else if (isset(Registry::load('config')->load_private_conversation) && !empty(Registry::load('config')->load_private_conversation)) {
    $user_id = Registry::load('config')->load_private_conversation;
}

if (!empty($user_id)) {
    $columns = [
        'site_users.user_id', 'site_users.display_name', 'site_users.username', 'site_users.site_role_id',
        'custom_fields_values.field_value(about)'
    ];

    $columns[] = 'site_roles.site_role_attribute';
    $join["[>]site_roles"] = ["site_users.site_role_id" => "site_role_id"];
    $join["[>]custom_fields_values"] = ["site_users.user_id" => "user_id", "AND" => ["field_id" => 1]];

    $where["site_users.user_id"] = $user_id;
    $where["LIMIT"] = 1;


    $user = DB::connect()->select('site_users', $join, $columns, $where);

    if (isset($user[0])) {
        $meta_tags['title'] = $user[0]['display_name'].' - '.Registry::load('settings')->site_name;
        $meta_tags['url'] = Registry::load('config')->site_url.$user[0]['username'].'/';

        if (isset($user[0]['about']) && !empty($user[0]['about'])) {
            $meta_tags['description'] = $user[0]['about'];
        }

        if (get_image(['from' => 'site_users/cover_pics', 'search' => $user_id, 'exists' => true])) {
            $meta_tags['social_share_image'] = get_image(['from' => 'site_users/cover_pics', 'search' => $user_id]);
        }
    }
}
?>