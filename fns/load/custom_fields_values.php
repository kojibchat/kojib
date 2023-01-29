<?php

if (role(['permissions' => ['custom_fields' => 'view']])) {

    $field_id = 0;

    if (isset($data['field_id'])) {
        $field_id = filter_var($data["field_id"], FILTER_SANITIZE_NUMBER_INT);
    }


    if (!empty($field_id)) {
        $custom_field = 'custom_field_'.$field_id;


        $columns = [
            'custom_fields_values.field_value_id', 'custom_fields_values.field_value',
            'custom_fields_values.user_id', 'custom_fields_values.group_id',
            'site_users.display_name', 'custom_fields.field_type', 'groups.name(group_name)'
        ];

        $join["[>]custom_fields"] = ["custom_fields_values.field_id" => "field_id"];
        $join["[>]site_users"] = ["custom_fields_values.user_id" => "user_id"];
        $join["[>]groups"] = ["custom_fields_values.group_id" => "group_id"];

        $where["custom_fields_values.field_id"] = $field_id;
        $where["custom_fields_values.field_value[!]"] = '';

        if (!empty($data["offset"])) {
            $data["offset"] = array_map('intval', explode(',', $data["offset"]));
            $where["custom_fields_values.field_value_id[!]"] = $data["offset"];
        }

        if (!empty($data["search"])) {
            $where["AND #search_query"]["OR"] = [
                "site_users.display_name[~]" => $data["search"],
                "custom_fields_values.field_value[~]" => $data["search"],
                "groups.name[~]" => $data["search"]
            ];
        }

        $where["LIMIT"] = Registry::load('settings')->records_per_call;

        if ($data["sortby"] === 'values_asc') {
            $where["ORDER"] = ["custom_fields_values.field_value" => "ASC"];
        } else if ($data["sortby"] === 'values_desc') {
            $where["ORDER"] = ["custom_fields_values.field_value" => "DESC"];
        }

        $values = DB::connect()->select('custom_fields_values', $join, $columns, $where);

        $i = 1;
        $output = array();
        $output['loaded'] = new stdClass();
        $output['loaded']->title = Registry::load('strings')->$custom_field;
        $output['loaded']->selectable = true;
        $output['loaded']->select = 'field_id';
        $output['loaded']->loaded = 'custom_fields';
        $output['loaded']->offset = array();


        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'custom_fields_values';
        $output['multiple_select']->attributes['multi_select'] = 'field_value_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;

        if (!empty($data["offset"])) {
            $output['loaded']->offset = $data["offset"];
        }

        $output['sortby'][1] = new stdClass();
        $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
        $output['sortby'][1]->class = 'load_aside';
        $output['sortby'][1]->attributes['load'] = 'custom_fields_values';
        $output['sortby'][1]->attributes['data-field_id'] = $field_id;

        $output['sortby'][2] = new stdClass();
        $output['sortby'][2]->sortby = Registry::load('strings')->values;
        $output['sortby'][2]->class = 'load_aside sort_asc';
        $output['sortby'][2]->attributes['load'] = 'custom_fields_values';
        $output['sortby'][2]->attributes['data-field_id'] = $field_id;
        $output['sortby'][2]->attributes['sort'] = 'values_asc';

        $output['sortby'][3] = new stdClass();
        $output['sortby'][3]->sortby = Registry::load('strings')->values;
        $output['sortby'][3]->class = 'load_aside sort_desc';
        $output['sortby'][3]->attributes['load'] = 'custom_fields_values';
        $output['sortby'][3]->attributes['data-field_id'] = $field_id;
        $output['sortby'][3]->attributes['sort'] = 'values_desc';

        foreach ($values as $value) {
            $output['loaded']->offset[] = $value['field_value_id'];

            $output['content'][$i] = new stdClass();
            $output['content'][$i]->class = "custom_field_value";
            $output['content'][$i]->identifier = $value['field_value_id'];

            if (!empty($value['user_id'])) {
                $output['content'][$i]->image = get_image(['from' => 'site_users/profile_pics', 'search' => $value['user_id']]);
                $output['content'][$i]->title = $value['display_name'];
            } else if (!empty($value['group_id'])) {
                $output['content'][$i]->image = get_image(['from' => 'groups/icons', 'search' => $value['group_id']]);
                $output['content'][$i]->title = $value['group_name'];
            } else {
                $output['content'][$i]->alphaicon = true;
            }


            $output['content'][$i]->icon = 0;
            $output['content'][$i]->unread = 0;

            if ($value['field_type'] === 'dropdown') {
                $dropdownoptions = $custom_field.'_options';

                if (isset(Registry::load('strings')->$dropdownoptions)) {

                    $field_options = json_decode(Registry::load('strings')->$dropdownoptions);
                    if (!empty($field_options)) {
                        $find = $value['field_value'];
                        if (isset($field_options->$find)) {
                            $output['content'][$i]->subtitle = $field_options->$find;
                        }
                    }

                }
            } else if ($value['field_type'] === 'date') {

                if (Registry::load('settings')->dateformat === 'mdy_format') {
                    $output['content'][$i]->subtitle = date("M-d-Y", strtotime($value['field_value']));
                } else if (Registry::load('settings')->dateformat === 'ymd_format') {
                    $output['content'][$i]->subtitle = date("Y-M-d", strtotime($value['field_value']));
                } else {
                    $output['content'][$i]->subtitle = date("d-M-Y", strtotime($value['field_value']));
                }
            } else {
                $output['content'][$i]->subtitle = $value['field_value'];
            }



            if (!empty($value['user_id']) && role(['permissions' => ['site_users' => 'edit_users']])) {
                $output['options'][$i][1] = new stdClass();
                $output['options'][$i][1]->option = Registry::load('strings')->edit;
                $output['options'][$i][1]->class = 'load_form';
                $output['options'][$i][1]->attributes['todo'] = 'update';
                $output['options'][$i][1]->attributes['form'] = 'site_users';
                $output['options'][$i][1]->attributes['data-user_id'] = $value['user_id'];
            } else if (!empty($value['group_id']) && role(['permissions' => ['groups' => 'super_privileges']])) {
                $output['options'][$i][1] = new stdClass();
                $output['options'][$i][1]->option = Registry::load('strings')->edit;
                $output['options'][$i][1]->class = 'load_form';
                $output['options'][$i][1]->attributes['todo'] = 'update';
                $output['options'][$i][1]->attributes['form'] = 'groups';
                $output['options'][$i][1]->attributes['data-group_id'] = $value['group_id'];
            }

            if (role(['permissions' => ['custom_fields' => 'delete']])) {
                $output['options'][$i][2] = new stdClass();
                $output['options'][$i][2]->option = Registry::load('strings')->delete;
                $output['options'][$i][2]->class = 'ask_confirmation';
                $output['options'][$i][2]->attributes['data-remove'] = 'custom_fields_values';
                $output['options'][$i][2]->attributes['data-field_value_id'] = $value['field_value_id'];
                $output['options'][$i][2]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][2]->attributes['cancel_button'] = Registry::load('strings')->no;
                $output['options'][$i][2]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            }

            $i++;
        }
    }
}
?>