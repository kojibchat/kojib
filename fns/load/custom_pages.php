<?php

if (role(['permissions' => ['custom_pages' => 'view']])) {

    $language_id = Registry::load('current_user')->language;
    $join = null;
    $columns = [
        'custom_pages.page_id', 'language_strings.string_value(page_title)', 'custom_pages.disabled',
        'custom_pages.slug', 'custom_pages.meta_title',
    ];

    $join["[>]language_strings"] = ["custom_pages.string_constant" => "string_constant", "AND" => ["language_id" => $language_id]];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["custom_pages.page_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {

        $where["AND #search_query"]["OR"] = [
            "custom_pages.meta_title[~]" => $data["search"],
            "language_strings.string_value[~]" => $data["search"],
            "custom_pages.slug[~]" => $data["search"]
        ];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if ($data["sortby"] === 'name_asc') {
        $where["ORDER"] = ["custom_pages.string_constant" => "ASC"];
    } else if ($data["sortby"] === 'name_desc') {
        $where["ORDER"] = ["custom_pages.string_constant" => "DESC"];
    } else if ($data["sortby"] === 'status_asc') {
        $where["ORDER"] = ["custom_pages.disabled" => "ASC"];
    } else if ($data["sortby"] === 'status_desc') {
        $where["ORDER"] = ["custom_pages.disabled" => "DESC"];
    } else {
        $where["ORDER"] = ["custom_pages.page_id" => "DESC"];
    }
    
    $pages = DB::connect()->select('custom_pages', $join, $columns, $where);


    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->pages;
    $output['loaded']->loaded = 'custom_pages';
    $output['loaded']->offset = array();

    if (role(['permissions' => ['custom_pages' => 'create']])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_form';
        $output['todo']->title = Registry::load('strings')->add_custom_page;
        $output['todo']->attributes['form'] = 'custom_pages';
        $output['todo']->attributes['enlarge'] = true;
    }

    if (role(['permissions' => ['custom_pages' => 'delete']])) {
        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'custom_pages';
        $output['multiple_select']->attributes['multi_select'] = 'page_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }


    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'custom_pages';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->name;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'custom_pages';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->name;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'custom_pages';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    $output['sortby'][6] = new stdClass();
    $output['sortby'][6]->sortby = Registry::load('strings')->status;
    $output['sortby'][6]->class = 'load_aside sort_asc';
    $output['sortby'][6]->attributes['load'] = 'custom_pages';
    $output['sortby'][6]->attributes['sort'] = 'status_asc';

    $output['sortby'][7] = new stdClass();
    $output['sortby'][7]->sortby = Registry::load('strings')->status;
    $output['sortby'][7]->class = 'load_aside sort_desc';
    $output['sortby'][7]->attributes['load'] = 'custom_pages';
    $output['sortby'][7]->attributes['sort'] = 'status_desc';

    foreach ($pages as $page) {

        $output['loaded']->offset[] = $page['page_id'];
        $output['content'][$i] = new stdClass();
        $output['content'][$i]->alphaicon = true;
        $output['content'][$i]->identifier = $page['page_id'];
        $output['content'][$i]->title = $page['page_title'];
        $output['content'][$i]->class = "group";
        $output['content'][$i]->subtitle = $page['slug'];
        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        if ($data["sortby"] === 'status_desc' || $data["sortby"] === 'status_asc') {
            if ($page['disabled'] === 1) {
                $output['content'][$i]->subtitle = Registry::load('strings')->disabled;
            } else {
                $output['content'][$i]->subtitle = Registry::load('strings')->enabled;
            }
        }

        if (role(['permissions' => ['custom_pages' => 'edit']])) {
            $output['options'][$i][1] = new stdClass();
            $output['options'][$i][1]->option = Registry::load('strings')->edit;
            $output['options'][$i][1]->class = 'load_form';
            $output['options'][$i][1]->attributes['enlarge'] = true;
            $output['options'][$i][1]->attributes['form'] = 'custom_pages';
            $output['options'][$i][1]->attributes['data-page_id'] = $page['page_id'];
        }

        $output['options'][$i][2] = new stdClass();
        $output['options'][$i][2]->option = Registry::load('strings')->view;
        $output['options'][$i][2]->class = 'load_page';
        $output['options'][$i][2]->attributes['page_id'] = $page['page_id'];

        if (role(['permissions' => ['custom_pages' => 'delete']])) {
            $output['options'][$i][3] = new stdClass();
            $output['options'][$i][3]->option = Registry::load('strings')->delete;
            $output['options'][$i][3]->class = 'ask_confirmation';
            $output['options'][$i][3]->attributes['data-remove'] = 'custom_pages';
            $output['options'][$i][3]->attributes['data-page_id'] = $page['page_id'];
            $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;
            $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
        }

        $i++;
    }
}
?>