<?php

if (role(['permissions' => ['custom_fields' => 'view']])) {

    $irremovable = [1, 2, 3, 4, 5, 6];
    $join = null;

    $columns = [
        'custom_fields.field_id', 'custom_fields.string_constant', 'custom_fields.field_category',
        'custom_fields.required', 'custom_fields.field_type'
    ];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["custom_fields.field_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"]) || $data["sortby"] === 'name_asc' || $data["sortby"] === 'name_desc') {
        $join["[>]language_strings(string)"] = ["custom_fields.string_constant" => "string_constant", "AND" => ["language_id" => Registry::load('current_user')->language]];
    }
    
    if (!empty($data["search"])) {
        $where["string.string_value[~]"] = $data["search"];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    $where["GROUP"] = ["custom_fields.field_id"];

    if ($data["sortby"] === 'name_asc') {
        $where["ORDER"] = ["string.string_value" => "ASC"];
    } else if ($data["sortby"] === 'name_desc') {
        $where["ORDER"] = ["string.string_value" => "DESC"];
    } else if ($data["sortby"] === 'type_asc') {
        $where["ORDER"] = ["custom_fields.field_type" => "ASC"];
    } else if ($data["sortby"] === 'type_desc') {
        $where["ORDER"] = ["custom_fields.field_type" => "DESC"];
    } else if ($data["sortby"] === 'category_asc') {
        $where["ORDER"] = ["custom_fields.field_category" => "ASC"];
    } else if ($data["sortby"] === 'category_desc') {
        $where["ORDER"] = ["custom_fields.field_category" => "DESC"];
    } else {
        $where["ORDER"] = ["custom_fields.field_id" => "DESC"];
    }

    if (!empty($join)) {
        $fields = DB::connect()->select('custom_fields', $join, $columns, $where);
    } else {
        $fields = DB::connect()->select('custom_fields', $columns, $where);
    }

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->fields;
    $output['loaded']->loaded = 'custom_fields';
    $output['loaded']->offset = array();

    if (role(['permissions' => ['custom_fields' => 'delete']])) {
        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'custom_fields';
        $output['multiple_select']->attributes['multi_select'] = 'field_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }

    if (role(['permissions' => ['custom_fields' => 'create']])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_form';
        $output['todo']->title = Registry::load('strings')->add_custom_field;
        $output['todo']->attributes['form'] = 'custom_fields';
    }

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'custom_fields';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->name;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'custom_fields';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->name;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'custom_fields';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    $output['sortby'][4] = new stdClass();
    $output['sortby'][4]->sortby = Registry::load('strings')->type;
    $output['sortby'][4]->class = 'load_aside sort_asc';
    $output['sortby'][4]->attributes['load'] = 'custom_fields';
    $output['sortby'][4]->attributes['sort'] = 'type_asc';

    $output['sortby'][5] = new stdClass();
    $output['sortby'][5]->sortby = Registry::load('strings')->type;
    $output['sortby'][5]->class = 'load_aside sort_desc';
    $output['sortby'][5]->attributes['load'] = 'custom_fields';
    $output['sortby'][5]->attributes['sort'] = 'type_desc';

    $output['sortby'][6] = new stdClass();
    $output['sortby'][6]->sortby = Registry::load('strings')->category;
    $output['sortby'][6]->class = 'load_aside sort_asc';
    $output['sortby'][6]->attributes['load'] = 'custom_fields';
    $output['sortby'][6]->attributes['sort'] = 'category_asc';

    $output['sortby'][7] = new stdClass();
    $output['sortby'][7]->sortby = Registry::load('strings')->category;
    $output['sortby'][7]->class = 'load_aside sort_desc';
    $output['sortby'][7]->attributes['load'] = 'custom_fields';
    $output['sortby'][7]->attributes['sort'] = 'category_desc';

    foreach ($fields as $field) {
        $output['loaded']->offset[] = $field['field_id'];

        $category = $field['field_category'];
        $type = $field['field_type'].'_field';
        $string_constant = $field['string_constant'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->alphaicon = true;
        $output['content'][$i]->identifier = $field['field_id'];
        $output['content'][$i]->title = Registry::load('strings')->$string_constant;
        $output['content'][$i]->class = "custom_field";
        $output['content'][$i]->subtitle = Registry::load('strings')->$category.' - '.Registry::load('strings')->$type;
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        if (role(['permissions' => ['custom_fields' => 'edit']])) {
            $output['options'][$i][1] = new stdClass();
            $output['options'][$i][1]->option = Registry::load('strings')->edit;
            $output['options'][$i][1]->class = 'load_form';
            $output['options'][$i][1]->attributes['form'] = 'custom_fields';
            $output['options'][$i][1]->attributes['data-field_id'] = $field['field_id'];
        }

        if (!in_array($field['field_id'], $irremovable)) {
            if (role(['permissions' => ['custom_fields' => 'delete']])) {
                $output['options'][$i][2] = new stdClass();
                $output['options'][$i][2]->option = Registry::load('strings')->delete;
                $output['options'][$i][2]->class = 'ask_confirmation';
                $output['options'][$i][2]->attributes['data-remove'] = 'custom_fields';
                $output['options'][$i][2]->attributes['data-field_id'] = $field['field_id'];
                $output['options'][$i][2]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][2]->attributes['cancel_button'] = Registry::load('strings')->no;
                $output['options'][$i][2]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            }
        }

        $output['options'][$i][3] = new stdClass();
        $output['options'][$i][3]->option = Registry::load('strings')->values;
        $output['options'][$i][3]->class = 'load_aside';
        $output['options'][$i][3]->attributes['load'] = 'custom_fields_values';
        $output['options'][$i][3]->attributes['data-field_id'] = $field['field_id'];

        $i++;
    }
}
?>