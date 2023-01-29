<?php
use Medoo\Medoo;

if (role(['permissions' => ['site_roles' => 'view']])) {

    $core_roles = ['default_site_role', 'guest_users', 'administrators', 'unverified_users', 'banned_users'];
    $join = null;

    $columns = [
        'site_roles.site_role_id', 'site_roles.string_constant',
        'site_roles.site_role_attribute', 'site_roles.disabled'
    ];



    if ($data["sortby"] === 'users_asc' || $data["sortby"] === 'users_desc') {
        $columns['total_users'] = Medoo::raw('COUNT(<total_users.user_id>)');
        $join["[>]site_users(total_users)"] = ["site_roles.site_role_id" => "site_role_id"];
    }

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["site_roles.site_role_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"]) || $data["sortby"] === 'name_asc' || $data["sortby"] === 'name_desc') {
        $join["[>]language_strings(string)"] = [
            "site_roles.string_constant" => "string_constant",
            "AND" => ["language_id" => Registry::load('current_user')->language]
        ];
    }

    if (!empty($data["search"])) {
        $where["string.string_value[~]"] = $data["search"];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if ($data["sortby"] === 'users_asc' || $data["sortby"] === 'users_desc') {
        $where["GROUP"] = ["site_roles.site_role_id"];
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
        $where["ORDER"] = ["site_roles.site_role_id" => "ASC"];
    }

    if (!empty($join)) {
        $site_roles = DB::connect()->select('site_roles', $join, $columns, $where);
    } else {
        $site_roles = DB::connect()->select('site_roles', $columns, $where);
    }

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->site_roles;
    $output['loaded']->loaded = 'site_roles';
    $output['loaded']->offset = array();


    if (role(['permissions' => ['site_roles' => 'delete']])) {

        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'site_roles';
        $output['multiple_select']->attributes['multi_select'] = 'site_role_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }

    if (role(['permissions' => ['site_roles' => 'create']])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_form';
        $output['todo']->title = Registry::load('strings')->create_site_role;
        $output['todo']->attributes['form'] = 'site_roles';
    }

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'site_roles';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->name;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'site_roles';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->name;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'site_roles';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    $output['sortby'][4] = new stdClass();
    $output['sortby'][4]->sortby = Registry::load('strings')->users;
    $output['sortby'][4]->class = 'load_aside sort_asc';
    $output['sortby'][4]->attributes['load'] = 'site_roles';
    $output['sortby'][4]->attributes['sort'] = 'users_asc';

    $output['sortby'][5] = new stdClass();
    $output['sortby'][5]->sortby = Registry::load('strings')->users;
    $output['sortby'][5]->class = 'load_aside sort_desc';
    $output['sortby'][5]->attributes['load'] = 'site_roles';
    $output['sortby'][5]->attributes['sort'] = 'users_desc';

    foreach ($site_roles as $site_role) {

        $site_role_attribute = $site_role['site_role_attribute'];

        $output['loaded']->offset[] = $site_role['site_role_id'];

        $string_constant = $site_role['string_constant'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = get_image(['from' => 'site_roles', 'search' => $site_role['site_role_id']]);
        $output['content'][$i]->title = Registry::load('strings')->$string_constant;
        $output['content'][$i]->identifier = $site_role['site_role_id'];
        $output['content'][$i]->class = "site_roles";
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;


        if ($data["sortby"] === 'users_asc' || $data["sortby"] === 'users_desc') {
            $output['content'][$i]->subtitle = $site_role['total_users'].' '.Registry::load('strings')->users;
        } else {

            if (isset(Registry::load('strings')->$site_role_attribute)) {
                $output['content'][$i]->subtitle = Registry::load('strings')->$site_role_attribute;
            } else if ((int)$site_role['disabled'] === 1) {
                $output['content'][$i]->subtitle = Registry::load('strings')->disabled;
            } else {
                $output['content'][$i]->subtitle = Registry::load('strings')->enabled;
            }
        }

        if (role(['permissions' => ['site_users' => 'view_site_users']])) {
            $output['options'][$i][1] = new stdClass();
            $output['options'][$i][1]->option = Registry::load('strings')->users;
            $output['options'][$i][1]->class = 'load_aside';
            $output['options'][$i][1]->attributes['load'] = 'site_users';
            $output['options'][$i][1]->attributes['data-site_role_id'] = $site_role['site_role_id'];
        }

        if (role(['permissions' => ['site_roles' => 'edit']])) {
            $output['options'][$i][2] = new stdClass();
            $output['options'][$i][2]->option = Registry::load('strings')->edit;
            $output['options'][$i][2]->class = 'load_form';
            $output['options'][$i][2]->attributes['form'] = 'site_roles';
            $output['options'][$i][2]->attributes['data-site_role_id'] = $site_role['site_role_id'];
        }

        if (!in_array($site_role_attribute, $core_roles)) {
            if (role(['permissions' => ['site_roles' => 'delete']])) {
                $output['options'][$i][3] = new stdClass();
                $output['options'][$i][3]->class = 'ask_confirmation';
                $output['options'][$i][3]->option = Registry::load('strings')->delete;
                $output['options'][$i][3]->attributes['data-remove'] = 'site_roles';
                $output['options'][$i][3]->attributes['data-site_role_id'] = $site_role['site_role_id'];
                $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;
                $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            }
        }


        $i++;
    }
}
?>