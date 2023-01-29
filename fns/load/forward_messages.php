<?php

if (Registry::load('current_user')->logged_in) {

    $columns = $join = $where = null;
    $sql_statement = '';

    $columns = [
        'groups.group_id', 'groups.name', 'groups.slug', 'groups.suspended',
        'group_members.group_role_id', 'group_roles.group_role_attribute', 'group_roles.string_constant(group_role)'
    ];

    $join["[>]group_members"] = ["groups.group_id" => "group_id", "AND" => ["user_id" => Registry::load('current_user')->id]];
    $join["[>]group_roles"] = ["group_members.group_role_id" => "group_role_id"];

    $where["group_members.group_role_id[!]"] = null;
    $where["group_roles.group_role_attribute[!]"] = 'banned_users';
    $where["groups.suspended"] = 0;


    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["groups.group_id[!]"] = $data["offset"];
    }


    $where["LIMIT"] = Registry::load('settings')->records_per_call;
    $where["ORDER"] = ["groups.name" => "ASC"];

    $groups = DB::connect()->select('groups', $join, $columns, $where);


    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->forward_message;
    $output['loaded']->offset = array();

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    if (!isset($data["message_id"]) || empty($data["message_id"])) {
        $data["message_id"] = 0;
    }

    $output['multiple_select'] = new stdClass();
    $output['multiple_select']->title = Registry::load('strings')->forward;
    $output['multiple_select']->icon = 'bi bi-chevron-right';
    $output['multiple_select']->attributes['class'] = 'ask_confirmation';
    $output['multiple_select']->attributes['data-add'] = 'forward_message';
    $output['multiple_select']->attributes['data-message_id'] = $data["message_id"];
    $output['multiple_select']->attributes['multi_select'] = 'group_id';
    $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
    $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
    $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;

    foreach ($groups as $group) {
        $output['loaded']->offset[] = $group['group_id'];

        $group_role = $group['group_role'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = get_image(['from' => 'groups/icons', 'search' => $group['group_id']]);
        $output['content'][$i]->title = $group['name'];
        $output['content'][$i]->class = "group_conversation select_result";
        $output['content'][$i]->subtitle = Registry::load('strings')->$group_role;

        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;
        $output['content'][$i]->identifier = $group['group_id'];
        $output['content'][$i]->attributes = ['group_id' => $group['group_id'], 'stopPropagation' => true];

        $i++;
    }

}
?>