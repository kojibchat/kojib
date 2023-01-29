<?php

if (role(['permissions' => ['site_adverts' => 'view']])) {

    $columns = [
        'site_advertisements.site_advert_id', 'site_advertisements.site_advert_name',
        'site_advertisements.disabled', 'site_advertisements.site_advert_placement'
    ];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["site_advertisements.site_advert_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {
        $where["site_advertisements.site_advert_name[~]"] = $data["search"];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if ($data["sortby"] === 'name_asc') {
        $where["ORDER"] = ["site_advertisements.site_advert_name" => "ASC"];
    } else if ($data["sortby"] === 'name_desc') {
        $where["ORDER"] = ["site_advertisements.site_advert_name" => "DESC"];
    } else if ($data["sortby"] === 'status_asc') {
        $where["ORDER"] = ["site_advertisements.disabled" => "ASC"];
    } else if ($data["sortby"] === 'status_desc') {
        $where["ORDER"] = ["site_advertisements.disabled" => "DESC"];
    } else {
        $where["ORDER"] = ["site_advertisements.site_advert_id" => "DESC"];
    }

    $adverts = DB::connect()->select('site_advertisements', $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->site_adverts;
    $output['loaded']->loaded = 'site_adverts';
    $output['loaded']->offset = array();

    if (role(['permissions' => ['site_adverts' => 'delete']])) {
        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'site_adverts';
        $output['multiple_select']->attributes['multi_select'] = 'site_advert_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }


    if (role(['permissions' => ['site_adverts' => 'create']])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_form';
        $output['todo']->title = Registry::load('strings')->create_advert;
        $output['todo']->attributes['form'] = 'site_adverts';
        $output['todo']->attributes['enlarge'] = true;
    }

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'site_adverts';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->name;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'site_adverts';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->name;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'site_adverts';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    $output['sortby'][4] = new stdClass();
    $output['sortby'][4]->sortby = Registry::load('strings')->status;
    $output['sortby'][4]->class = 'load_aside sort_asc';
    $output['sortby'][4]->attributes['load'] = 'site_adverts';
    $output['sortby'][4]->attributes['sort'] = 'status_asc';

    $output['sortby'][5] = new stdClass();
    $output['sortby'][5]->sortby = Registry::load('strings')->status;
    $output['sortby'][5]->class = 'load_aside sort_desc';
    $output['sortby'][5]->attributes['load'] = 'site_adverts';
    $output['sortby'][5]->attributes['sort'] = 'status_desc';

    foreach ($adverts as $advert) {
        $output['loaded']->offset[] = $advert['site_advert_id'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->title = $advert['site_advert_name'];
        $output['content'][$i]->alphaicon = true;
        $output['content'][$i]->identifier = $advert['site_advert_id'];
        $output['content'][$i]->class = "site_advert";

        if ((int)$advert['disabled'] === 1) {
            $output['content'][$i]->subtitle = Registry::load('strings')->disabled;
        } else {
            $output['content'][$i]->subtitle = Registry::load('strings')->enabled;
        }

        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        if (role(['permissions' => ['site_adverts' => 'edit']])) {
            $output['options'][$i][1] = new stdClass();
            $output['options'][$i][1]->option = Registry::load('strings')->edit;
            $output['options'][$i][1]->class = 'load_form';
            $output['options'][$i][1]->attributes['form'] = 'site_adverts';
            $output['options'][$i][1]->attributes['enlarge'] = true;
            $output['options'][$i][1]->attributes['data-site_advert_id'] = $advert['site_advert_id'];
        }

        if (role(['permissions' => ['site_adverts' => 'delete']])) {
            $output['options'][$i][3] = new stdClass();
            $output['options'][$i][3]->class = 'ask_confirmation';
            $output['options'][$i][3]->option = Registry::load('strings')->delete;
            $output['options'][$i][3]->attributes['data-remove'] = 'site_adverts';
            $output['options'][$i][3]->attributes['data-site_advert_id'] = $advert['site_advert_id'];
            $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;
            $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
        }

        $i++;
    }
}
?>