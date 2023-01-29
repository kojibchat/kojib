<?php
use Medoo\Medoo;

if (role(['permissions' => ['group_roles' => 'view']])) {

    $core_roles = ['default_group_role', 'administrators', 'moderators', 'banned_users'];
    $join = null;
    $columns = [
        'group_roles.group_role_id', 'group_roles.string_constant',
        'group_roles.disabled', 'group_roles.group_role_attribute(attribute)'
    ];

    if ($data["sortby"] === 'users_asc' || $data["sortby"] === 'users_desc') {
        $columns['total_users'] = Medoo::raw('COUNT(<total_users.group_member_id>)');
        $join["[>]group_members(total_users)"] = ["group_roles.group_role_id" => "group_role_id"];
    }

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["group_roles.group_role_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"]) || $data["sortby"] === 'name_asc' || $data["sortby"] === 'name_desc') {

        $join["[>]language_strings(string)"] = [
            "group_roles.string_constant" => "string_constant",
            "AND" => ["language_id" => Registry::load('current_user')->language]
        ];
    }

    if (!empty($data["search"])) {
        $where["string.string_value[~]"] = $data["search"];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if ($data["sortby"] === 'users_asc' || $data["sortby"] === 'users_desc') {
        $where["GROUP"] = ["group_roles.group_role_id"];
    }

    if ($data["sortby"] === 'name_asc') {
        $where["ORDER"] = ["string.string_value" => "ASC"];
    } else if ($data["sortby"] === 'name_desc') {
        $where["ORDER"] = ["string.string_value" => "DESC"];
    } else if ($data["sortby"] === 'users_asc') {
        $where["ORDER"] = ["total_users" => "ASC"];
    } else if ($data["sortby"] === 'users_desc') {
        $where["ORDER"] = ["total_users" => "DESC"];
    } else {
        $where["ORDER"] = ["group_roles.group_role_id" => "ASC"];
    }

    if (!empty($join)) {
        $group_roles = DB::connect()->select('group_roles', $join, $columns, $where);
    } else {
        $group_roles = DB::connect()->select('group_roles', $columns, $where);
    }

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->group_roles;
    $output['loaded']->loaded = 'group_roles';
    $output['loaded']->offset = array();


    if (role(['permissions' => ['group_roles' => 'delete']])) {

        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'group_roles';
        $output['multiple_select']->attributes['multi_select'] = 'group_role_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }

    if (role(['permissions' => ['group_roles' => 'create']])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_form';
        $output['todo']->title = Registry::load('strings')->create_group_role;
        $output['todo']->attributes['form'] = 'group_roles';
    }

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'group_roles';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->name;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'group_roles';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->name;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'group_roles';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    $output['sortby'][4] = new stdClass();
    $output['sortby'][4]->sortby = Registry::load('strings')->users;
    $output['sortby'][4]->class = 'load_aside sort_asc';
    $output['sortby'][4]->attributes['load'] = 'group_roles';
    $output['sortby'][4]->attributes['sort'] = 'users_asc';

    $output['sortby'][5] = new stdClass();
    $output['sortby'][5]->sortby = Registry::load('strings')->users;
    $output['sortby'][5]->class = 'load_aside sort_desc';
    $output['sortby'][5]->attributes['load'] = 'group_roles';
    $output['sortby'][5]->attributes['sort'] = 'users_desc';

    foreach ($group_roles as $group_role) {
        $output['loaded']->offset[] = $group_role['group_role_id'];

        $string_constant = $group_role['string_constant'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = get_image(['from' => 'group_roles', 'search' => $group_role['group_role_id']]);
        $output['content'][$i]->title = Registry::load('strings')->$string_constant;
        $output['content'][$i]->identifier = $group_role['group_role_id'];
        $output['content'][$i]->class = "group_roles";
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        $group_role_attribute = $group_role['attribute'];


        if ($data["sortby"] === 'users_asc' || $data["sortby"] === 'users_desc') {
            $output['content'][$i]->subtitle = $group_role['total_users'].' '.Registry::load('strings')->users;
        } else {

            if (isset(Registry::load('strings')->$group_role_attribute)) {
                $output['content'][$i]->subtitle = Registry::load('strings')->$group_role_attribute;
            } else if ((int)$group_role['disabled'] === 1) {
                $output['content'][$i]->subtitle = Registry::load('strings')->disabled;
            } else {
                $output['content'][$i]->subtitle = Registry::load('strings')->enabled;
            }
        }

        if (role(['permissions' => ['group_roles' => 'edit']])) {
            $output['options'][$i][2] = new stdClass();
            $output['options'][$i][2]->option = Registry::load('strings')->edit;
            $output['options'][$i][2]->class = 'load_form';
            $output['options'][$i][2]->attributes['form'] = 'group_roles';
            $output['options'][$i][2]->attributes['data-group_role_id'] = $group_role['group_role_id'];
        }

        if (!in_array($group_role_attribute, $core_roles)) {
            if (role(['permissions' => ['group_roles' => 'delete']])) {
                $output['options'][$i][3] = new stdClass();
                $output['options'][$i][3]->class = 'ask_confirmation';
                $output['options'][$i][3]->option = Registry::load('strings')->delete;
                $output['options'][$i][3]->attributes['data-remove'] = 'group_roles';
                $output['options'][$i][3]->attributes['data-group_role_id'] = $group_role['group_role_id'];
                $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;
                $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            }
        }


        $i++;
    }
}
?>