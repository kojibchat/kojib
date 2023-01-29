<?php

if (role(['permissions' => ['languages' => 'view']])) {

    $columns = [
        'languages.language_id', 'languages.name', 'languages.text_direction'
    ];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["languages.language_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {
        $where["languages.name[~]"] = $data["search"];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if ($data["sortby"] === 'name_asc') {
        $where["ORDER"] = ["languages.name" => "ASC"];
    } else if ($data["sortby"] === 'name_desc') {
        $where["ORDER"] = ["languages.name" => "DESC"];
    } else {
        $where["ORDER"] = ["languages.language_id" => "DESC"];
    }

    $languages = DB::connect()->select('languages', $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->languages;
    $output['loaded']->offset = array();
    $output['loaded']->loaded = 'languages';

    if (role(['permissions' => ['languages' => 'delete']])) {

        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'languages';
        $output['multiple_select']->attributes['multi_select'] = 'language_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }

    if (role(['permissions' => ['languages' => 'create']])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_form';
        $output['todo']->title = Registry::load('strings')->add_language;
        $output['todo']->attributes['form'] = 'languages';
    }


    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'languages';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->name;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'languages';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->name;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'languages';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    foreach ($languages as $language) {
        $output['loaded']->offset[] = $language['language_id'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = get_image(['from' => 'languages', 'search' => $language['language_id']]);
        $output['content'][$i]->title = $language['name'];
        $output['content'][$i]->class = "languages";
        $output['content'][$i]->identifier = $language['language_id'];

        if ((int)$language['language_id'] === (int)Registry::load('settings')->default_language) {
            $output['content'][$i]->subtitle = Registry::load('strings')->default_txt;
        } else {
            $output['content'][$i]->subtitle = Registry::load('strings')->language;
        }

        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        $output['options'][$i][1] = new stdClass();
        $output['options'][$i][1]->option = Registry::load('strings')->view;
        $output['options'][$i][1]->class = 'load_aside';
        $output['options'][$i][1]->attributes['load'] = 'language_strings';
        $output['options'][$i][1]->attributes['data-language_id'] = $language['language_id'];


        if (role(['permissions' => ['languages' => 'edit']])) {
            $output['options'][$i][2] = new stdClass();
            $output['options'][$i][2]->option = Registry::load('strings')->edit;
            $output['options'][$i][2]->class = 'load_form';
            $output['options'][$i][2]->attributes['form'] = 'languages';
            $output['options'][$i][2]->attributes['data-language_id'] = $language['language_id'];
        }

        if (role(['permissions' => ['languages' => 'export']])) {
            $output['options'][$i][3] = new stdClass();
            $output['options'][$i][3]->option = Registry::load('strings')->export;
            $output['options'][$i][3]->class = 'download_file';
            $output['options'][$i][3]->attributes['download'] = 'language';
            $output['options'][$i][3]->attributes['data-language_id'] = $language['language_id'];
        }

        if (role(['permissions' => ['languages' => 'delete']])) {
            if ((int)$language['language_id'] !== 1 && (int)$language['language_id'] !== (int)Registry::load('settings')->default_language) {
                $output['options'][$i][4] = new stdClass();
                $output['options'][$i][4]->option = Registry::load('strings')->delete;
                $output['options'][$i][4]->class = 'ask_confirmation';
                $output['options'][$i][4]->attributes['data-remove'] = 'languages';
                $output['options'][$i][4]->attributes['data-language_id'] = $language['language_id'];
                $output['options'][$i][4]->attributes['submit_button'] = Registry::load('strings')->yes;
                $output['options'][$i][4]->attributes['cancel_button'] = Registry::load('strings')->no;
                $output['options'][$i][4]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
            }
        }

        $i++;
    }
}
?>