<?php

if (role(['permissions' => ['audio_player' => 'view']])) {

    $columns = [
        'audio_player.audio_content_id', 'audio_player.audio_title',
        'audio_player.disabled', 'audio_player.audio_type',
    ];

    if (!empty($data["offset"])) {
        $data["offset"] = array_map('intval', explode(',', $data["offset"]));
        $where["audio_player.audio_content_id[!]"] = $data["offset"];
    }

    if (!empty($data["search"])) {
        $where["audio_player.audio_title[~]"] = $data["search"];
    }

    $where["LIMIT"] = Registry::load('settings')->records_per_call;

    if ($data["sortby"] === 'name_asc') {
        $where["ORDER"] = ["audio_player.audio_title" => "ASC"];
    } else if ($data["sortby"] === 'name_desc') {
        $where["ORDER"] = ["audio_player.audio_title" => "DESC"];
    } else if ($data["sortby"] === 'status_asc') {
        $where["ORDER"] = ["audio_player.disabled" => "ASC"];
    } else if ($data["sortby"] === 'status_desc') {
        $where["ORDER"] = ["audio_player.disabled" => "DESC"];
    } else {
        $where["ORDER"] = ["audio_player.audio_content_id" => "DESC"];
    }

    $audio_files = DB::connect()->select('audio_player', $columns, $where);

    $i = 1;
    $output = array();
    $output['loaded'] = new stdClass();
    $output['loaded']->title = Registry::load('strings')->audio_player;
    $output['loaded']->loaded = 'audio_player';
    $output['loaded']->offset = array();

    if (role(['permissions' => ['audio_player' => 'delete']])) {
        $output['multiple_select'] = new stdClass();
        $output['multiple_select']->title = Registry::load('strings')->delete;
        $output['multiple_select']->attributes['class'] = 'ask_confirmation';
        $output['multiple_select']->attributes['data-remove'] = 'audio_player_contents';
        $output['multiple_select']->attributes['multi_select'] = 'audio_content_id';
        $output['multiple_select']->attributes['submit_button'] = Registry::load('strings')->yes;
        $output['multiple_select']->attributes['cancel_button'] = Registry::load('strings')->no;
        $output['multiple_select']->attributes['confirmation'] = Registry::load('strings')->confirm_action;
    }

    if (role(['permissions' => ['audio_player' => 'add']])) {
        $output['todo'] = new stdClass();
        $output['todo']->class = 'load_form';
        $output['todo']->title = Registry::load('strings')->add_audio;
        $output['todo']->attributes['form'] = 'audio_player_contents';
    }

    if (!empty($data["offset"])) {
        $output['loaded']->offset = $data["offset"];
    }

    $output['sortby'][1] = new stdClass();
    $output['sortby'][1]->sortby = Registry::load('strings')->sort_by_default;
    $output['sortby'][1]->class = 'load_aside';
    $output['sortby'][1]->attributes['load'] = 'audio_player_contents';

    $output['sortby'][2] = new stdClass();
    $output['sortby'][2]->sortby = Registry::load('strings')->name;
    $output['sortby'][2]->class = 'load_aside sort_asc';
    $output['sortby'][2]->attributes['load'] = 'audio_player_contents';
    $output['sortby'][2]->attributes['sort'] = 'name_asc';

    $output['sortby'][3] = new stdClass();
    $output['sortby'][3]->sortby = Registry::load('strings')->name;
    $output['sortby'][3]->class = 'load_aside sort_desc';
    $output['sortby'][3]->attributes['load'] = 'audio_player_contents';
    $output['sortby'][3]->attributes['sort'] = 'name_desc';

    $output['sortby'][4] = new stdClass();
    $output['sortby'][4]->sortby = Registry::load('strings')->status;
    $output['sortby'][4]->class = 'load_aside sort_asc';
    $output['sortby'][4]->attributes['load'] = 'audio_player_contents';
    $output['sortby'][4]->attributes['sort'] = 'status_asc';

    $output['sortby'][5] = new stdClass();
    $output['sortby'][5]->sortby = Registry::load('strings')->status;
    $output['sortby'][5]->class = 'load_aside sort_desc';
    $output['sortby'][5]->attributes['load'] = 'audio_player_contents';
    $output['sortby'][5]->attributes['sort'] = 'status_desc';

    foreach ($audio_files as $audio_file) {
        $output['loaded']->offset[] = $audio_file['audio_content_id'];

        $output['content'][$i] = new stdClass();
        $output['content'][$i]->image = get_image(['from' => 'audio_player/images', 'search' => $audio_file['audio_content_id']]);
        $output['content'][$i]->title = $audio_file['audio_title'];
        $output['content'][$i]->identifier = $audio_file['audio_content_id'];
        $output['content'][$i]->class = "audio_file";

        if ($data["sortby"] === 'status_desc' || $data["sortby"] === 'status_asc') {
            if ((int)$audio_file['disabled'] === 1) {
                $output['content'][$i]->subtitle = Registry::load('strings')->disabled;
            } else {
                $output['content'][$i]->subtitle = Registry::load('strings')->enabled;
            }
        } else {
            if ((int)$audio_file['audio_type'] === 1) {
                $output['content'][$i]->subtitle = Registry::load('strings')->radio_station;
            } else {
                $output['content'][$i]->subtitle = Registry::load('strings')->playlist;
            }
        }

        $output['content'][$i]->icon = 0;
        $output['content'][$i]->unread = 0;

        if ((int)$audio_file['audio_type'] === 2) {
            $output['options'][$i][1] = new stdClass();
            $output['options'][$i][1]->option = Registry::load('strings')->view;
            $output['options'][$i][1]->class = 'load_aside';
            $output['options'][$i][1]->attributes['load'] = 'playlist';
            $output['options'][$i][1]->attributes['data-audio_content_id'] = $audio_file['audio_content_id'];
        }

        if (role(['permissions' => ['audio_player' => 'edit']])) {
            $output['options'][$i][2] = new stdClass();
            $output['options'][$i][2]->option = Registry::load('strings')->edit;
            $output['options'][$i][2]->class = 'load_form';
            $output['options'][$i][2]->attributes['form'] = 'audio_player_contents';
            $output['options'][$i][2]->attributes['data-audio_content_id'] = $audio_file['audio_content_id'];
        }

        if (role(['permissions' => ['audio_player' => 'delete']])) {
            $output['options'][$i][3] = new stdClass();
            $output['options'][$i][3]->option = Registry::load('strings')->delete;
            $output['options'][$i][3]->class = 'ask_confirmation';
            $output['options'][$i][3]->attributes['data-remove'] = 'audio_player_contents';
            $output['options'][$i][3]->attributes['data-audio_content_id'] = $audio_file['audio_content_id'];
            $output['options'][$i][3]->attributes['submit_button'] = Registry::load('strings')->yes;
            $output['options'][$i][3]->attributes['cancel_button'] = Registry::load('strings')->no;
            $output['options'][$i][3]->attributes['confirmation'] = Registry::load('strings')->confirm_action;
        }

        $i++;
    }
}
?>